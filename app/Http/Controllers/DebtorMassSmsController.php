<?php

namespace App\Http\Controllers;

use App\Customer;
use App\DebtorEvent;
use App\Exceptions\DebtorException;
use App\Model\DebtorEventSms;
use App\Repositories\DebtorSmsRepository;
use App\Services\DebtorEventService;
use App\Utils\SMSer;
use Illuminate\Http\Request;
use App\Utils\PermLib;
use App\Permission;
use App\Utils\StrLib;
use Illuminate\Support\Facades\Auth;
use App\Debtor;
use Illuminate\Support\Facades\Log;
use Yajra\Datatables\Facades\Datatables;
use App\Utils\HtmlHelper;
use Carbon\Carbon;
use App\User;

class DebtorMassSmsController extends BasicController
{

    public $debtorEventService;

    public function __construct(DebtorEventService $service)
    {
        if (!Auth::user()->hasPermission(Permission::makeName(PermLib::ACTION_OPEN, PermLib::SUBJ_DEBTOR_TRANSFER))) {
            return redirect('/')->with('msg_err', StrLib::ERR_NOT_ADMIN);
        }
        $this->debtorEventService = $service;
    }

    public function index(DebtorSmsRepository $smsRepository)
    {
        if (!Auth::user()) {
            return redirect()->json([
                'title' => 'Ошибка',
                'msg' => 'Не авторизированный пользователь'
            ]);
        }
        if (Auth::user()->isDebtorsPersonal()) {
            $type = 'personal';
            $nameGroup = 'Личное взыскание';
        }
        if (Auth::user()->isDebtorsRemote()) {
            $type = 'remote';
            $nameGroup = 'Удаленное взыскание';
        }
        return view('debtormasssms.index', [
            'smsCollect' => $smsRepository->getSms($type),
            'nameGroup' => $nameGroup,
            'debtorTransferFilterFields' => self::getSearchFields()
        ]);
    }

    function getDebtorsTableColumns()
    {
        return [
            'debtors.passports.fio' => 'passports_fio',
            'debtors.od' => 'debtors_od',
            'debtors.base' => 'debtors_base',
            'debtors.passports.fact_address_city' => 'passports_fact_address_city',
            'debtors.passports.fact_address_city1' => 'passports_fact_address_city1',
            'debtors.passports.fact_address_district' => 'passports_fact_address_district',
            'debtors.passports.fact_address_street' => 'passports_fact_address_street',
            'debtors.passports.fact_address_house' => 'passports_fact_address_house',
            'debtors.fixation_date' => 'debtors_fixation_date',
            'debtors.qty_delays' => 'debtors_qty_delays',
            'debtors.responsible_user_id_1c' => 'debtors_responsible_user_id_1c',
            'debtors.debt_group_id' => 'debtors_debt_group_id',
            'debtors.id' => 'debtors_id',
            'debtors.str_podr' => 'debtors_str_podr'
        ];
    }

    /**
     * Проверяет заполнены ли поля поиска в таблице
     * @param array $input
     * @return boolean
     */
    function hasFilterFilled($input)
    {
        $filled = false;
        foreach ($input as $k => $v) {
            if (((strpos($k, 'search_field_') === 0 && strpos($k,
                            '_condition') === false) || $k == 'users@login' || $k == 'debtors@base') && !empty($v)) {
                return true;
            }
        }
        return $filled;
    }

    public function ajaxList(Request $req)
    {
        $cols = [];
        $tCols = $this->getDebtorsTableColumns();
        foreach ($tCols as $k => $v) {
            $cols[] = $k . ' as ' . $v;
        }

        $input = $req->input();
        $debtors = Debtor::select($cols)
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debt_group_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c')
            ->leftJoin('struct_subdivisions', 'struct_subdivisions.id_1c', '=', 'debtors.str_podr')
            ->groupBy('debtors.id');

        if (!$this->hasFilterFilled($input)) {
            $debtors->where('debtors.debtors.id', 0);
        }

        if (isset($input['fixation_date']) && mb_strlen($input['fixation_date'])) {
            $debtors->whereBetween('fixation_date', [
                Carbon::parse($input['fixation_date'])->startOfDay(),
                Carbon::parse($input['fixation_date'])->endOfDay()
            ]);
        }
        if (isset($input['overdue_from']) && mb_strlen($input['overdue_from'])) {
            $debtors->where('qty_delays', '>=', $input['overdue_from']);
        }
        if (isset($input['overdue_till']) && mb_strlen($input['overdue_till'])) {
            $debtors->where('qty_delays', '<=', $input['overdue_till']);
        }
        if (isset($input['passports@fact_address_region']) && mb_strlen($input['passports@fact_address_region'])) {
            $debtors->where('passports.fact_address_region', 'like',
                '%' . $input['passports@fact_address_region'] . '%');
        }

        $collection = Datatables::of($debtors)
            ->editColumn('debtors_fixation_date', function ($item) {
                return (!is_null($item->debtors_fixation_date)) ? Carbon::parse($item->debtors_fixation_date)->format('d.m.Y') : '-';
            })
            ->editColumn('debtors_od', function ($item) {
                return number_format($item->debtors_od / 100, 2, '.', '');
            })
            ->editColumn('debtors_debt_group_id', function ($item) {
                return (array_key_exists($item->debtors_debt_group_id,
                    config('debtors.debt_groups'))) ? config('debtors.debt_groups')[$item->debtors_debt_group_id] : '';
            })
            ->editColumn('passports_fact_address_city', function ($item) {
                $tmpCity = (empty($item->passports_fact_address_city)) ? $item->passports_fact_address_city1 : $item->passports_fact_address_city;
                $tmpDistrict = (empty($item->passports_fact_address_district) || is_null($item->passports_fact_address_district)) ? '' : $item->passports_fact_address_district . '<br>';
                return $tmpCity . '<br><span style="font-size: 80%; color: #555555; font-style: italic;">' . $tmpDistrict . $item->passports_fact_address_street . ', д. ' . $item->passports_fact_address_house . '</span>';
            })
            ->editColumn('debtors_str_podr', function ($item) {
                switch ($item->debtors_str_podr) {
                    case '000000000007':
                        $struct_podr = 'Отдел личного взыскания';
                        break;

                    case '000000000006':
                        $struct_podr = 'Отдел удаленного взыскания';
                        break;

                    case '0000000000001':
                        $struct_podr = 'СБиВЗ';
                        break;

                    default:
                        $struct_podr = $item->debtors_str_podr;
                        break;
                }

                return $struct_podr;
            })
            ->addColumn('links', function ($item) {
                $html = '';
                $html .= HtmlHelper::Buttton(url('debtors/debtorcard/' . $item->debtors_id),
                    ['glyph' => 'eye-open', 'size' => 'xs', 'target' => '_blank']);
                return $html;
            }, 1)
            ->removeColumn('debtors_id')
            ->removeColumn('passports_fact_address_city1')
            ->removeColumn('passports_fact_address_district')
            ->removeColumn('passports_fact_address_street')
            ->removeColumn('passports_fact_address_house')
            ->filter(function ($query) use ($req) {
                $input = $req->input();
                foreach ($input as $k => $v) {
                    if (strpos($k, 'search_field_') === 0 && strpos($k, '_condition') === false && !empty($v)) {
                        $fieldName = str_replace('search_field_', '', $k);
                        $tableName = substr($fieldName, 0, strpos($fieldName, '@'));
                        $colName = substr($fieldName, strlen($tableName) + 1);
                        $condColName = $k . '_condition';
                        $condition = (array_key_exists($condColName, $input)) ? $input[$condColName] : '=';
                        if ($condition == 'like') {
                            $v = '%' . $v . '%';
                        }
                        $query->where($tableName . '.' . $colName, $condition, $v);
                    }
                }
            })
            ->setTotalRecords(1000)
            ->make();
        return $collection;
    }

    public function sendMassSms(DebtorSmsRepository $repository, Request $request)
    {
        set_time_limit(0);

        $input = $request->input();

        if (!isset($input['search_field_users@id']) || $input['search_field_users@id'] == '') {
            return response()->json([
                'error' => 'Не указан пользователь.'
            ]);
        }

        if (!isset($input['sms_tpl_id']) || $input['sms_tpl_id'] == '') {
            return response()->json([
                'error' => 'Не выбран шаблон смс.'
            ]);
        }

        $resp_user = User::find($input['search_field_users@id']);

        if (is_null($resp_user)) {
            return response()->json([
                'error' => 'Не найден выбранный ответственный.'
            ]);
        }

        try {
            $tpl = $repository->firstById($input['sms_tpl_id']);

        }catch (\Throwable $exception) {
            return response()->json([
                'error' => 'Не найден смс шаблон.'
            ]);
        }


        $debtorsCustomers = Debtor::select('debtors.customer_id_1c')
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debt_group_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c');

        $debtorsCustomers->where('responsible_user_id_1c', $resp_user->id_1c);
        $debtorsCustomers->where('is_debtor', 1);

        if (isset($input['overdue_from']) && mb_strlen($input['overdue_from'])) {
            $debtorsCustomers->where('qty_delays', '>=', $input['overdue_from']);
        }
        if (isset($input['overdue_till']) && mb_strlen($input['overdue_till'])) {
            $debtorsCustomers->where('qty_delays', '<=', $input['overdue_till']);
        }
        if (isset($input['passports@fact_address_region']) && mb_strlen($input['passports@fact_address_region'])) {
            $debtorsCustomers->where('passports.fact_address_region', 'like',
                '%' . $input['passports@fact_address_region'] . '%');
        }
        if (isset($input['search_field_debtors@base']) && mb_strlen($input['search_field_debtors@base'])) {
            $debtorsCustomers->where('base', $input['search_field_debtors@base']);
        }
        if (isset($input['search_field_debt_groups@id']) && mb_strlen($input['search_field_debt_groups@id'])) {
            $debtorsCustomers->where('debt_group_id', $input['search_field_debt_groups@id']);
        }
        if (isset($input['fixation_date']) && mb_strlen($input['fixation_date'])) {
            $debtorsCustomers->whereBetween('fixation_date', [
                Carbon::parse($input['fixation_date'])->startOfDay(),
                Carbon::parse($input['fixation_date'])->endOfDay()
            ]);
        }
        foreach ($input as $k => $v) {
            if (strpos($k, 'search_field_') === 0 && strpos($k, '_condition') === false && !empty($v)) {
                $fieldName = str_replace('search_field_', '', $k);
                $tableName = substr($fieldName, 0, strpos($fieldName, '@'));
                $colName = substr($fieldName, strlen($tableName) + 1);
                $condColName = $k . '_condition';
                $condition = (array_key_exists($condColName, $input)) ? $input[$condColName] : '=';
                if ($condition == 'like') {
                    $v = '%' . $v . '%';
                }
                $debtorsCustomers->where($tableName . '.' . $colName, $condition, $v);
            }
        }

        $customersIds = $debtorsCustomers->groupBy('customer_id_1c')->get();
        $customers = $this->getCustomersToArray($customersIds);

        $cnt = 0;
        foreach ($customers as $customer) {

            try {
                $debtors = Debtor::where('customer_id_1c', $customer->id_1c)->get();
                foreach ($debtors as $debtor) {
                    $this->debtorEventService->checkLimitEvent($debtor);
                }
            } catch (DebtorException $e) {
                Log::error("$e->errorName:", [
                    'customer' => $debtor->customer_id_1c,
                    'file' => __FILE__,
                    'method' => __METHOD__,
                    'line' => __LINE__,
                    'id' => $e->errorId,
                    'message' => $e->errorMessage,
                ]);
                continue;
            }

            if ($tpl->id == 21) {
                $isSendOnce = $repository->checkSmsOnce($debtor, 21);
                $isFirstCondition = ($debtor->qty_delays != 80 || !in_array($debtor->base, [
                        'Б-3',
                        'Б-риски',
                        'КБ-график',
                        'Б-график'
                    ])
                );
                $isSecondCondition = ($debtor->qty_delays != 20 || !in_array($debtor->base, ['Б-МС']));
                if ($isFirstCondition && $isSecondCondition && !$isSendOnce) {
                    $tpl = $repository->firstById(3);
                }
            }
            if ($tpl->id == 45) {
                $isSendOnce = $repository->checkSmsOnce($debtor, 45);
                $isFirstCondition = ($debtor->qty_delays != 95 || !in_array($debtor->base, [
                        'Б-3',
                        'Б-риски',
                        'КБ-график',
                        'Б-график'
                    ])
                );
                $isSecondCondition = ($debtor->qty_delays != 25 || !in_array($debtor->base, ['Б-МС']));
                if ($isFirstCondition && $isSecondCondition && !$isSendOnce) {
                    $tpl = $repository->firstById(3);
                }
            }

            $phone = $customer->telephone;
            if (isset($phone[0]) && $phone[0] == '8') {
                $phone[0] = '7';
            }

            if (mb_strlen($phone) == 11) {


                $smsText = str_replace([
                    '##sms_till_date##',
                    '##spec_phone##',
                ], [
                    $input['sms_tpl_date'],
                    $resp_user->phone,
                ], $tpl->text_tpl);

                if (SMSer::send($phone, $smsText)) {
                    // увеличиваем счетчик отправленных пользователем смс
                    $resp_user->increaseSentSms();
                    // создаем мероприятие отправки смс
                    $debt = Debtor::where('customer_id_1c', $customer->id_1c)
                        ->where('is_debtor', 1)
                        ->first();
                    $report = $phone . ' SMS: ' . $smsText;
                    $event = $this->createEventSms($debt, $resp_user, $report);

                    if($tpl->id == 21 || $tpl->id == 45) {
                        DebtorEventSms::create([
                            'event_id'=> $event->id,
                            'sms_id' => $tpl->id ,
                            'customer_id_1c' => $debtor->customer_id_1c,
                            'debtor_base' => $debtor->base
                        ]);
                    }

                    $cnt++;
                }
            }
        }

        return response()->json([
            'error' => 'success',
            'cnt' => $cnt
        ]);
    }

    public function getCustomersToArray($customersIds)
    {
        foreach ($customersIds as $customerId) {
            $customers[] = $customerId['customer_id_1c'];
        }
        return Customer::whereIn('id_1c', $customers)->get();

    }

    /**
     * @param Debtor $debt
     * @param User $respUser
     * @param string $report
     */
    public function createEventSms($debt, $respUser, $report)
    {
       return DebtorEvent::create([
            'debtor_id' => $debt->id,
            'debtor_id_1c' => $debt->debtor_id_1c,
            'customer_id_1c' => $debt->customer_id_1c,
            'loan_id_1c' => $debt->loan_id_1c,
            'debt_group_id' => $debt->debt_group_id,
            'user_id' => $respUser->id,
            'user_id_1c' => $respUser->id_1c,
            'event_type_id' => 12,
            'report' => $report,
            'refresh_date' => Carbon::now(),
            'overdue_reason_id' => 0,
            'event_result_id' => 22,
            'completed' => 1,
        ]);
    }

    static function getSearchFields()
    {
        return [

            [
                'name' => 'users@login',
                'input_type' => 'text',
                'label' => 'Ответственный',
                'hidden_value_field' => 'users@id',
                'field_id' => 'old_user_id'
            ],
            [
                'name' => 'passports@fact_address_region',
                'input_type' => 'text',
                'label' => 'Регион',
                //'hidden_value_field' => 'passports@fact_address_region',
                'field_id' => ''
            ],
            [
                'name' => 'debtors@base',
                'input_type' => 'text',
                'label' => 'База',
                'hidden_value_field' => 'debtors@base',
                'field_id' => ''
            ],
            [
                'name' => 'debt_groups@name',
                'input_type' => 'text',
                'label' => 'Группа долга',
                'hidden_value_field' => 'debt_groups@id',
                'field_id' => ''
            ],
        ];
    }

}
