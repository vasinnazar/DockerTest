<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Spylog\Spylog;
use App\Spylog\SpylogModel;
use App\Subdivision;
use App\WorkTime;
use Auth;
use App\Utils\PermLib;
use App\Permission;
use App\Order;
use DB;
use App\User;
use App\DebtorsPayments;
use App\MySoap;

class DebtorsReportsController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
    }

    public function dzcollect(Request $req) {
        $start_date = false;
        $end_date = false;

        $arReturn = [];

        $input = $req->input();
        if (isset($input['start_date']) && !empty($input['start_date']) && isset($input['end_date']) && !empty($input['end_date'])) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];
            $arTodayPays = $this->getPaymentsForDay($req);

            if ($arTodayPays['result'] == 1) {
                foreach ($arTodayPays['payments'] as $payment) {
                    $date = date('Y-m-d 00:00:00', strtotime($payment->date));
                    if (DebtorsPayments::paymentExists($date, $payment->customer_id_1c, $payment->money)) {
                        continue;
                    }

                    $newPayment = new DebtorsPayments();
                    $newPayment->date = $date;
                    $newPayment->responsible_user_id_1c = $payment->responsible_user_id_1c;
                    $newPayment->money = $payment->money;
                    $newPayment->customer_id_1c = $payment->customer_id_1c;
                    $newPayment->loan_data = $payment->loan_id_1c;
                    $newPayment->save();
                }
            }

            $payments = DebtorsPayments::leftJoin('users', 'users.id_1c', '=', 'debtors_payments.responsible_user_id_1c')
                    ->whereBetween('date', array(with(new Carbon($start_date))->setTime(0, 0, 0)->format('Y-m-d H:i:s'), with(new Carbon($end_date))->setTime(23, 59, 59)->format('Y-m-d H:i:s')))
                    ->get();

            $arData = [];

            if (!is_null($payments)) {
                foreach ($payments as $payment) {
                    if (!isset($arData[$payment->responsible_user_id_1c]['sum'])) {
                        $arData[$payment->responsible_user_id_1c]['sum'] = 0;
                    }
                    $arData[$payment->responsible_user_id_1c]['sum'] += $payment->money;
                }
            }

            foreach ($arData as $rUser => $v) {
                $user = User::where('id_1c', $rUser)->first();
                if (!is_null($user)) {
                    $user_group = User::find($user->user_group_id);
                    if (!is_null($user_group)) {
                        $arData[$rUser]['group'] = $user_group->name;
                    } else {
                        $arData[$rUser]['group'] = 'Группа не определена';
                    }

                    $user_region = User::find($user->region_id);
                    if (!is_null($user_region)) {
                        $arData[$rUser]['region'] = $user_region->name;
                    } else {
                        $arData[$rUser]['region'] = 'Без региона';
                    }

                    $arData[$rUser]['fio'] = $user->name;
                } else {
                    $arData[$rUser]['group'] = 'Группа не определена';
                    $arData[$rUser]['region'] = 'Без региона';
                    $arData[$rUser]['fio'] = $rUser;
                }
            }

            foreach ($arData as $uid => $userdata) {
                if (isset($userdata['group'])) {
                    $arReturn[$userdata['group']][$uid] = $arData[$uid];
                }
            }

            \PC::debug($arReturn);
        }

        return view('debtorsreports.dzcollect', [
            'data' => $arReturn,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * Календарь планов
     * @param Request $req
     */
    public function planCalend(Request $req) {
        if (!$req->has('start_date') || !$req->has('end_date')) {
            return view('reports.debtorsreports.plancalend_form');
        }
        $start_date = $req->get('start_date', Carbon::today()->format('Y-m-d H:i:s'));
        $end_date = $req->get('end_date', Carbon::tomorrow()->setTime(0, 0, 1)->format('Y-m-d H:i:s'));
        $cols = [
            'users.name as users_name',
            'passports.fio as passports_fio',
            'debtor_events.completed as completed',
            'debtor_events.date as plan_date',
            'debtor_events.created_at as fact_date',
            'debtor_events.event_type_id',
            'debtor_events.event_result_id',
            'debtor_events.debt_group_id',
            'debtor_events.report',
            'debt_groups.name as debt_group_name'
        ];
        $debtorEvents = DB::table('debtor_events')
                ->select($cols)
                ->leftJoin('debtors', 'debtors.id', '=', 'debtor_events.debtor_id')
                ->leftJoin('debtors.passports', function($join) {
                    $join->on('debtors.passports.series', '=', 'debtors.debtors.passport_series');
                    $join->on('debtors.passports.number', '=', 'debtors.debtors.passport_number');
                })
                ->leftJoin('users', 'users.id', '=', 'debtor_events.user_id')
                ->leftJoin('debtor_users_ref', 'debtor_users_ref.master_user_id', '=', 'users.id')
                ->leftJoin('debt_groups', 'debt_groups.id', '=', 'debtors.debt_group_id')
                ->whereBetween('debtor_events.date', [$start_date, $end_date])
                ->whereNotNull('debtors.passports.fio')
//                ->groupBy('debtor_events.debtor_id_1c')
                ->orderBy('users.name', 'asc')
                ->orderBy('debtor_events.completed', 'asc')
                ->orderBy('debtor_events.date', 'asc')
                ->distinct('debtor_events.id')
                ->get();
        $data['events'] = $debtorEvents;
        if ($req->has('to_excel')) {
            $html = view('reports.debtorsreports.plancalend', $data)->render();
            return \App\Utils\HelperUtil::htmlToExcel($html, 'report.xlsx');
        } else {
            return view('reports.debtorsreports.plancalend', $data);
        }
    }

    public function getPaymentsForDay(Request $req) {
        $now = Carbon::now();
        $startDate = new Carbon($req->get('start_date', $now->format('Y-m-d')));
        $endDate = new Carbon($req->get('end_date', $now->format('Y-m-d')));
        $res = ['result' => 1, 'payments' => []];

        if ($endDate->copy()->subMonth()->gt($startDate)) {
            return ['result' => 0];
        }
        if ($endDate->lt($startDate)) {
            return ['result' => 0];
        }
        $xml = \App\MySoap::createXML([
                    'type' => 'GetDebtorPayment',
                    'start_date' => $startDate->setTime(0, 0, 0)->format('YmdHis'),
                    'end_date' => $endDate->setTime(23, 59, 59)->format('YmdHis'),
        ]);
        $res1c = MySoap::sendXML($xml, false, 'Main', config('1c.exchange_arm'), ['url' => '192.168.35.56:8080/111SPD']);

        if ((int) $res1c->result == 1) {
            $obj = json_decode(json_encode($res1c));
            foreach ($obj->tab as $payment) {
                $res['payments'][] = $payment;
            }
        }
        return $res;
    }

    /** Получает зачет оплат из 1С и возвращает json на вывод
     * @param Request $req
     * @return type
     */
    public function getPaymentsForUser(Request $req) {
        $now = Carbon::now();
        $startDate = new Carbon($req->get('start_date', $now->format('Y-m-d')));
        $endDate = new Carbon($req->get('end_date', $now->format('Y-m-d')));
        if ($startDate->month != $endDate->month) {
            return ['result' => 0];
        }
        if ($endDate->lt($startDate)) {
            return ['result' => 0];
        }
        $user_ids = $req->get('debtor_id_1c');
        $users = User::whereIn('id', $user_ids)->get();
        $res = ['result' => 1, 'payments' => []];
        foreach ($users as $user) {
            $xml = \App\MySoap::createXML([
                                'type' => 'GetDebtorPayment',
                                'start_date' => $startDate->setTime(0, 0, 0)->format('YmdHis'),
                                'end_date' => $endDate->setTime(23, 59, 59)->format('YmdHis'),
                                'debtor_id_1c' => $user->id_1c
            ]);
            $res1c = MySoap::sendXML($xml, false, 'Main', config('1c.exchange_arm'), ['url' => '192.168.35.56:8080/111SPD']);
            if ((int) $res1c->result == 1) {
                $obj = json_decode(json_encode($res1c));
                foreach ($obj->tab as $payment) {
                    
                    if (isset($payment->loan_id_1c)) {
                        $arLoanId1c = explode(' ', $payment->loan_id_1c);
                        if ($arLoanId1c[0] == 'Продление') {
                            $loan_id_1c = str_replace('№', '', $arLoanId1c[1]);
                        } else {
                            $loan_id_1c = $arLoanId1c[0];
                        }
                        $debtor = \App\Debtor::where('customer_id_1c', $payment->customer_id_1c)->where('loan_id_1c', $loan_id_1c)->first();
                        /*if (!$debtor) {
                            \PC::debug($payment);
                            continue;
                        }*/
                        $payment->debtor_id = $debtor->id;
                    }
                    $res['payments'][] = $payment;
                }
            }
        }
        return $res;
    }

    public function jobsDoneAct(Request $req) {
        if (!$req->has('user_id')) {
            return view('reports.debtorsreports.jobs_done_act_form');
        }
        $data = [];
        $startDate = new Carbon($req->get('start_date', Carbon::today()->format('Y-m-d')));
        $endDate = new Carbon($req->get('end_date', Carbon::tomorrow()->format('Y-m-d')));
        $user = User::find($req->get('user_id'));

        $cols = [
            'passports.fio as passports_fio',
        ];
        $payments = DebtorsPayments::where('responsible_user_id_1c', $user->id_1c)
                ->where('date', '>=', $startDate)
                ->where('date', '<', $endDate)
                ->leftJoin('customers', 'customers.id_1c', '=', 'debtors_payments.customer_id_1c')
                ->leftJoin('passports', 'passports.customer_id', '=', 'customers.id')
                ->select($cols)
                ->get();
        $data['items'] = $payments;
        $data['user'] = $user;
        return view('reports.debtorsreports.jobs_done_act', $data);
    }

    public function ovz(Request $req) {
        if (!$req->has('start_date') || !$req->has('end_date')) {
            return view('reports.debtorsreports.ovz_form');
        }
        $startDate = new Carbon($req->get('start_date', Carbon::today()->format('Y-m-d')));
        $endDate = new Carbon($req->get('end_date', Carbon::tomorrow()->format('Y-m-d')));
        $res1c = \App\MySoap::sendExchangeArm(\App\MySoap::createXML(['type' => 'OVZ', 'start_date' => $startDate->format('Ymd'), 'end_date' => $endDate->format('Ymd')]));
        $items = [];
        if ((int) $res1c->result == 1) {
            foreach ($res1c->tab->children() as $item) {
                $temp = [];
                foreach ($item as $k => $v) {
                    $temp[$k] = strval($v);
                }
                $items[] = $temp;
            }
        }
        $data = ['items' => json_decode(json_encode($items)), 'user' => Auth::user(), 'start_date' => $startDate->format('d.m.Y'), 'end_date' => $endDate->format('d.m.Y')];
        \PC::debug($data['items']);
        return view('reports.debtorsreports.ovz', $data);
    }
    
    /**
     * Количество контрагентов на ответственных
     */
    public function countDebtCustomersForRespUser(Request $req) {
        $str_podr = $req->get('str_podr', false);
        if (!$str_podr) {
            return redirect()->back();
        } else if ($str_podr == 'olv') {
            $user_group_id = 1536;
        } else if ($str_podr == 'ouv') {
            $user_group_id = 1541;
        } else {
            return redirect()->back();
        }
        
        $resp_users = User::where('user_group_id', $user_group_id)->get();
        
        $arData = [];
        $i = 0;
        foreach ($resp_users as $user) {
            $arData[$i]['username'] = $user->name;
            $arData[$i]['count'] = \App\Debtor::where('responsible_user_id_1c', $user->id_1c)->distinct('customer_id_1c')->count('customer_id_1c');
            $i++;
        }
        
        return view('debtorsreports.customers_count', [
            'arData' => $arData
        ]);
    }
    
    public function exportToExcelDebtorsLoginLog(Request $request) {
        $user = auth()->user();
        
        $dateStart = $request->get('dateStart', '');
        $dateEnd = $request->get('dateEnd', '');
        $mode = $request->get('mode', 'uv');
        
        if (!mb_strlen($dateStart) || !mb_strlen($dateEnd)) {
            $dateStart = date('Y-m-d 00:00:00', time());
            $dateEnd = date('Y-m-d 23:59:59', time());
        } else {
            $dateStart = date('Y-m-d 00:00:00', strtotime($dateStart));
            $dateEnd = date('Y-m-d 23:59:59', strtotime($dateEnd));
        }
        
        $debtGroups = \App\DebtGroup::get()->toArray();
        
        $html = '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>ФИО</th><th>Код контрагента</th><th>Ответственный</th><th>Общая сумма задолженности</th><th>Кол-во договоров</th><th>Группа долга</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        $debtorsLog = \App\DebtorsSiteLoginLog::whereBetween('created_at', [$dateStart, $dateEnd]);
        
        if ($mode == 'lv') {
            $debtorsLog->where('str_podr', '000000000007');
        } else {
            $debtorsLog->where('str_podr', '000000000006');
        }
        
        if (!$user->hasRole('debtors_chief')) {
            $debtorsLog->where('responsible_user_id', $user->id);
        }
        
        $debtorsLog = $debtorsLog->get();
        
        foreach ($debtorsLog as $logRow) {
            $customer = \App\Customer::where('id_1c', $logRow->customer_id_1c)->first();
            if (is_null($customer)) {
                continue;
            }
            
            $passport = \App\Passport::where('customer_id', $customer->id)->first();
            if (is_null($passport)) {
                continue;
            }
            
            $debtor = \App\Debtor::where('customer_id_1c', $customer->id_1c)->where('is_debtor', 1)->first();
            if (is_null($debtor)) {
                continue;
            }
            
            $responsible = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
            
            $html .= '<tr>';
            $html .= '<td>' . $passport->fio . '</td>';
            $html .= '<td>' . $customer->id_1c . '</td>';
            $html .= '<td>' . (!is_null($responsible) ? $responsible->name : '') . '</td>';
            $html .= '<td>' . number_format($logRow->sum_loans_debt / 100, 2, '.', '') . '</td>';
            $html .= '<td>' . $logRow->debt_loans_count . '</td>';
            $html .= '<td>' . (isset($debtGroups[$logRow->debt_group_id]) ? $debtGroups[$logRow->debt_group_id]['name'] : '-') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';

        $file = "report_site_login_" . date('dmY', strtotime($dateStart)) . "_" . date('dmY', strtotime($dateEnd)) . ".xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
                        ->header("Content-type", "application/vnd.ms-excel")
                        ->header("Content-Disposition", "attachment; filename=$file");
    }

}
