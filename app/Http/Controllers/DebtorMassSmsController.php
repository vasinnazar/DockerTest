<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Utils\PermLib;
use App\Permission;
use App\Utils\StrLib;
use Auth;
use App\Debtor;
use App\Order;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\DB;
use App\StrUtils;
use App\DebtorEvent;
use App\Utils\HtmlHelper;
use Carbon\Carbon;
use App\Photo;
use Illuminate\Support\Facades\Storage;
use App\Passport;
use App\DebtorUsersRef;
use App\DebtorSmsTpls;
use App\User;
use App\Utils;
use App\MySoap;

class DebtorMassSmsController extends BasicController {

    public function __construct() {
        if (!Auth::user()->hasPermission(Permission::makeName(PermLib::ACTION_OPEN, PermLib::SUBJ_DEBTOR_TRANSFER))) {
            return redirect('/')->with('msg_err', StrLib::ERR_NOT_ADMIN);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('debtormasssms.index', [
            'debtorTransferFilterFields' => self::getSearchFields()
        ]);
    }

    function getDebtorsTableColumns() {
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
    function hasFilterFilled($input) {
        $filled = false;
        foreach ($input as $k => $v) {
            if (((strpos($k, 'search_field_') === 0 && strpos($k, '_condition') === FALSE) || $k == 'users@login' || $k == 'debtors@base') && !empty($v)) {
                $filled = true;
                return $filled;
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
            ->leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
            ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
            ->leftJoin('debtors.customers', 'debtors.claims.customer_id', '=', 'debtors.customers.id')
            ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debt_group_id')
            ->leftJoin('debtors.passports', function ($join) {
                $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c')
            ->leftJoin('struct_subdivisions', 'struct_subdivisions.id_1c', '=', 'debtors.str_podr')
//                ->where('armf.passports.fio', '<>', '')
//                ->whereNotNull('armf.passports.fio')
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

        $is_bigmoney = (isset($input['search_field_debtors@is_bigmoney']) && $input['search_field_debtors@is_bigmoney'] == 1) ? 1 : 0;
        $is_pledge = (isset($input['search_field_debtors@is_pledge']) && $input['search_field_debtors@is_pledge'] == 1) ? 1 : 0;
        $is_pos = (isset($input['search_field_debtors@is_pos']) && $input['search_field_debtors@is_pos'] == 1) ? 1 : 0;

        if ($is_bigmoney || $is_pledge || $is_pos) {
            $debtors->where(function ($query) use ($is_bigmoney, $is_pledge, $is_pos) {
                if ($is_bigmoney) {
                    $query->where('debtors.debtors.is_bigmoney', 1);
                    if ($is_pledge) {
                        $query->orWhere('debtors.debtors.is_pledge', 1);
                    }
                    if ($is_pos) {
                        $query->orWhere('debtors.debtors.is_pos', 1);
                    }
                } else {
                    if ($is_pledge) {
                        $query->where('debtors.debtors.is_pledge', 1);
                        if ($is_pos) {
                            $query->orWhere('debtors.debtors.is_pos', 1);
                        }
                    } else {
                        if ($is_pos) {
                            $query->where('debtors.debtors.is_pos', 1);
                        }
                    }
                }
            });
        }

        $collection = Datatables::of($debtors)
            ->editColumn('debtors_fixation_date', function ($item) {
                return (!is_null($item->debtors_fixation_date)) ? date('d.m.Y',
                    strtotime($item->debtors_fixation_date)) : '-';
            })
            ->editColumn('debtors_od', function ($item) {
                return number_format($item->debtors_od / 100, 2, '.', '');
            })
            ->editColumn('debtors_debt_group_id', function ($item) {
                return (array_key_exists($item->debtors_debt_group_id,
                    config('debtors')['debt_groups'])) ? config('debtors')['debt_groups'][$item->debtors_debt_group_id] : '';
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
    
    public function sendMassSms(Request $request) {
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
        
        $tpl = \App\DebtorSmsTpls::find($input['sms_tpl_id']);
        
        if (is_null($tpl)) {
            return response()->json([
                'error' => 'Не найден смс шаблон.'
            ]);
        }
        
        $debtors = Debtor::leftJoin('debtors.loans', 'debtors.loans.id_1c', '=', 'debtors.loan_id_1c')
                ->leftJoin('debtors.claims', 'debtors.claims.id', '=', 'debtors.loans.claim_id')
                ->leftJoin('debtors.customers', 'debtors.claims.customer_id', '=', 'debtors.customers.id')
                ->leftJoin('debtors.debt_groups', 'debtors.debt_groups.id', '=', 'debtors.debt_group_id')
                ->leftJoin('debtors.passports', function($join) {
                    $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                    $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
                })
                ->leftJoin('users', 'users.id_1c', '=', 'debtors.responsible_user_id_1c')
                ->leftJoin('struct_subdivisions', 'struct_subdivisions.id_1c', '=', 'debtors.str_podr')
//                ->where('armf.passports.fio', '<>', '')
//                ->whereNotNull('armf.passports.fio')
                ->groupBy('debtors.id');
                
        $debtors->where('responsible_user_id_1c', $resp_user->id_1c);
        $debtors->where('is_debtor', 1);
        
        if (isset($input['overdue_from']) && mb_strlen($input['overdue_from'])) {
            $debtors->where('qty_delays', '>=', $input['overdue_from']);
        }
        if (isset($input['overdue_till']) && mb_strlen($input['overdue_till'])) {
            $debtors->where('qty_delays', '<=', $input['overdue_till']);
        }
        if (isset($input['passports@fact_address_region']) && mb_strlen($input['passports@fact_address_region'])) {
            $debtors->where('passports.fact_address_region', 'like', '%' . $input['passports@fact_address_region'] . '%');
        }
        if (isset($input['search_field_debtors@base']) && mb_strlen($input['search_field_debtors@base'])) {
            $debtors->where('base', $input['search_field_debtors@base']);
        }
        if (isset($input['search_field_debt_groups@id']) && mb_strlen($input['search_field_debt_groups@id'])) {
            $debtors->where('debt_group_id', $input['search_field_debt_groups@id']);
        }
        if (isset($input['fixation_date']) && mb_strlen($input['fixation_date'])) {
            $debtors->whereBetween('fixation_date',[
                Carbon::parse($input['fixation_date'])->startOfDay(),
                Carbon::parse($input['fixation_date'])->endOfDay()
            ]);
        }
        
        $d = $debtors->get();
        
        $cnt = 0;
        
        foreach ($d as $debtor) {
            $loan = \App\Loan::where('id_1c', $debtor->loan_id_1c)->first();
            
            if (is_null($loan)) {
                continue;
            }
            
            $smsAlreadySent = \App\DebtorEvent::where('debtor_id_1c', $debtor->debtor_id_1c)
                ->where('created_at', '>=', date('Y-m-d 00:00:00', time()))
                ->where('created_at', '<=', date('Y-m-d 23:59:59', time()))
                ->where('event_type_id', 12)
                ->count();

            if ($smsAlreadySent >= 2) {
                continue;
            }
            
            $customer = \App\Customer::where('id_1c', $debtor->customer_id_1c)->first();
            
            if (is_null($customer)) {
                continue;
            }
            
            $phone = $customer->telephone;
            if (isset($phone[0]) && $phone[0] == '8') {
                $phone[0] = '7';
            }
            
            if (mb_strlen($phone) == 11) {
                
                $sms_text = $tpl->text_tpl;
                $sms_text = str_replace('##sms_till_date##', $input['sms_tpl_date'], $sms_text);
                $sms_text = str_replace('##spec_phone##', $resp_user->phone, $sms_text);
                $sms_text = str_replace('##sms_loan_info##', $debtor->loan_id_1c . ' от ' . date('d.m.Y', strtotime($loan->created_at)), $sms_text);
                
                if (\App\Utils\SMSer::send($phone, $sms_text)) {
                    // увеличиваем счетчик отправленных пользователем смс
                    $resp_user->increaseSentSms();

                    // создаем мероприятие отправки смс
                    $debtorEvent = new \App\DebtorEvent();
                    $data = [];
                    $data['created_at'] = date('Y-m-d H:i:s', time());
                    $data['event_type_id'] = 12;
                    $data['overdue_reason_id'] = 0;
                    $data['event_result_id'] = 22;
                    $data['debt_group_id'] = $debtor->debt_group_id;
                    $data['report'] = $phone . ' SMS: ' . $sms_text;
                    $data['completed'] = 1;
                    $debtorEvent->fill($data);
                    $debtorEvent->refresh_date = date('Y-m-d H:i:s', time());

                    if (!is_null($debtor)) {
                        $debtorEvent->debtor_id_1c = $debtor->debtor_id_1c;
                        $nd = Debtor::where('debtor_id_1c', $debtor->debtor_id_1c)->first();
                        if ($nd) {
                            $debtorEvent->debtor_id = $nd->id;
                        }
                    }
                    $debtorEvent->user_id = $resp_user->id;
                    $debtorEvent->user_id_1c = $resp_user->id_1c;
    //                $debtorEvent->id_1c = DebtorEvent::getNextNumber();
                    $debtorEvent->save();

                    //$debtorEvent->id_1c = 'М' . StrUtils::addChars(strval($debtorEvent->id), 9, '0', false);
                    //$debtorEvent->save();
                    
                    $cnt++;
                }
            }
        }
        
        return response()->json([
            'error' => 'success',
            'cnt' => $cnt
        ]);
    }

    static function getSearchFields() {
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
