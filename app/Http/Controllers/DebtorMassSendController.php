<?php

namespace App\Http\Controllers;

use App\Clients\ArmClient;
use App\DebtorEvent;
use App\Exceptions\DebtorException;
use App\Http\Requests\MassSendRequest;
use App\Repositories\DebtorEventSmsRepository;
use App\Repositories\DebtorEventsRepository;
use App\Repositories\DebtorSmsRepository;
use App\Services\DebtorEventService;
use App\Services\DebtorSmsService;
use App\Services\EmailService;
use App\Utils\SMSer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Debtor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\HtmlHelper;
use Carbon\Carbon;
use App\User;

class DebtorMassSendController extends BasicController
{
    public $emailService;
    public $debtorEventService;
    public $debtorSmsRepository;
    public $debtorEventSmsRepository;
    public $debtorEventsRepository;
    public $debtorSmsService;
    public $armClient;
    public function __construct(
        EmailService $emailService,
        DebtorEventService $debtorEventService,
        DebtorSmsRepository $debtorSmsRepository,
        DebtorEventSmsRepository $debtorEventSmsRepository,
        DebtorEventsRepository $debtorEventsRepository,
        DebtorSmsService $debtorSmsService,
        ArmClient $armClient
    )
    {
        $this->emailService = $emailService;
        $this->debtorEventService = $debtorEventService;
        $this->debtorSmsRepository = $debtorSmsRepository;
        $this->debtorEventSmsRepository = $debtorEventSmsRepository;
        $this->debtorEventsRepository = $debtorEventsRepository;
        $this->debtorSmsService = $debtorSmsService;
        $this->armClient = $armClient;
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
            'emailCollect' => $this->emailService->getListEmailsMessages(Auth::user()->id),
            'smsCollect' => $smsRepository->getSms($type),
            'nameGroup' => $nameGroup,
            'debtorTransferFilterFields' => self::getSearchFields(),
            'emailFilterFields' => self::getEmaiLFields()
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
            'debtors.str_podr' => 'debtors_str_podr',
            'about_clients.email' => 'about_clients_email',
            'about_clients.id' => 'about_clients_id'
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
            ->leftJoin('customers', 'customers.id_1c', '=', 'debtors.customer_id_1c')
            ->leftJoin('about_clients', function($join) {
                $join->on('about_clients.customer_id', '=', 'customers.id')
                    ->whereRaw('about_clients.id IN (select MAX(about_clients.id) from about_clients where customer_id = customers.id)');
            })
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

        if (isset($input['has_email']) && mb_strlen($input['has_email'])) {
            $debtors = $debtors->whereNotNull('about_clients.id');
            $debtors = $input['has_email'] ?
                $debtors
                    ->where('about_clients.email', 'regexp', '^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$') :
                $debtors
                    ->where(function ($query) {
                        $query
                            ->whereNull('about_clients.email')
                            ->orWhere('about_clients.email', 'not regexp', '^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
                    });
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
                $debtors->where($tableName . '.' . $colName, $condition, $v);
            }
        }
        $debtors = $debtors->get();

        return Datatables::of($debtors)
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
            ->addColumn('DT_RowId', function ($item) {
                return $item->debtors_id;
            }, 1)
            ->rawColumns(['links','passports_fact_address_city'])
            ->toJson();
    }

    public function sendMassSms(array $input)
    {
        try {
            $sms = $this->debtorSmsRepository->firstById((int)$input['templateId']);
            $respUser = User::findOrFail($input['responsibleUserId']);

        } catch (\Throwable $exception) {
            return response()->json([
                'error' => 'Не выбран шаблон смс или не удалось определить ответственного'
            ]);
        }

        $cnt = 0;
        $sendCustomers = [];
        $debtors = Debtor::whereIn('id', $input['debtorsIds'])->get();
        foreach ($debtors as $debtor) {

            if (in_array($debtor->customer_id_1c, $sendCustomers)) {
                continue;
            }

            try {
                $this->debtorEventService->checkLimitEventByCustomerId1c($debtor->customer_id_1c);

            } catch (DebtorException $e) {
                Log::channel('exception')->error("$e->errorName:", [
                    'customer' => $debtor['customer_id_1c'],
                    'file' => __FILE__,
                    'method' => __METHOD__,
                    'line' => __LINE__,
                    'id' => $e->errorId,
                    'message' => $e->errorMessage,
                ]);
                continue;
            }
            if (!$this->debtorSmsService->hasSmsMustBeSentOnce($debtor, $sms->id)) {
                $sms = $this->debtorSmsRepository->firstById(3);
            }

            $sms->text_tpl = str_replace([
                '##sms_till_date##',
                '##spec_phone##',
            ], [
                $input['dateSmsTemplate'],
                $respUser->phone,
            ], $sms->text_tpl);
            $phone = $debtor->customer->getPhone();

            if (!$phone) {
                continue;
            }

            if (!SMSer::send($phone, $sms->text_tpl)) {
                continue;
            }
            // увеличиваем счетчик отправленных пользователем смс
            $respUser->increaseSentSms();
            // создаем мероприятие отправки смс
            $report = $phone . ' SMS: ' . $sms->text_tpl;
            $event = $this->debtorEventsRepository->createEvent(
                $debtor,
                $respUser,
                $report,
                DebtorEvent::SMS_EVENT,
                0,
                22,
                1
            );

            if (in_array($sms->id, [21, 45])) {
                $this->debtorEventSmsRepository->create($event->id, $sms->id, $debtor->customer_id_1c, $debtor->base);
            }
            $sendCustomers[] =  $debtor->customer_id_1c;
            $cnt++;

        }
        return response()->json([
            'error' => 'success',
            'cnt' => $cnt
        ]);
    }
    public function sendMassEmail(array $input)
    {
        try {
            $responsibleUser = User::findOrFail($input['responsibleUserId']);
            $userArm = $this->armClient->getUserById1c($responsibleUser->id_1c);
            $dataEmailUser = $userArm ? array_shift($userArm)['email_user'] : null;
            $userEmail = $dataEmailUser['email'] ?? $dataEmailUser;
            $userPassword = $dataEmailUser['password'] ?? $dataEmailUser;
            if (empty(trim($userEmail)) || empty($userPassword)) {
                throw new \Exception();
            }
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => 'Не удалось определить данные ответственного'
            ]);
        }

        $cnt = 0;
        $sendCustomers = [];
        $debtors = Debtor::whereIn('id', $input['debtorsIds'])->get();
        foreach ($debtors as $debtor) {
            $arrayParam = [
                'debtor_id' => $debtor->id,
                'email_id' => $input['templateId'],
                'dateAnswer' => Carbon::parse($input['dateAnswer'] ?? null)->format('d.m.Y'),
                'datePayment' => Carbon::parse($input['datePayment'] ?? null)->format('d.m.Y'),
                'discountPayment' => $input['discountPayment'] ?? null,
                'user' => $responsibleUser,
                'userEmail' => $userEmail,
                'userPassword' => $userPassword,
            ];
            if (in_array($debtor->customer_id_1c, $sendCustomers)) {
                continue;
            }
            try {
                $this->debtorEventService->checkLimitEventByCustomerId1c($debtor->customer_id_1c);
            } catch (DebtorException $e) {
                Log::channel('exception')->error("$e->errorName:", [
                    'customer' => $debtor['customer_id_1c'],
                    'file' => __FILE__,
                    'method' => __METHOD__,
                    'line' => __LINE__,
                    'id' => $e->errorId,
                    'message' => $e->errorMessage,
                ]);
                continue;
            }
            if (!($this->emailService->sendEmailDebtor($arrayParam))) {
                continue;
            }
            $sendCustomers[] =  $debtor->customer_id_1c;
            $cnt++;
        }
        return response()->json([
            'error' => 'success',
            'cnt' => $cnt
        ]);
    }
    public function sendMassMessage(MassSendRequest $request)
    {
        $input = $request->validated();
        if ($input['isSms']) {
            return $this->sendMassSms($input);
        }
        return $this->sendMassEmail($input);
    }

    static function getEmaiLFields()
    {
        return [
            true => 'Присутствует',
            false => 'Отсутствует'
        ];
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
