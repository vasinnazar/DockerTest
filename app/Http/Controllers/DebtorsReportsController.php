<?php

namespace App\Http\Controllers;

use App\DebtorsPayments;
use App\Export\Excel\DebtorsLoginSiteExport;
use App\Http\Requests\Ajax\PaymentUserRequest;
use App\MySoap;
use App\Services\ReportsService;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class DebtorsReportsController extends BasicController {

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

    public function getPaymentsForDay(MySoap $mySoap, Request $req)
    {
        $startDate = Carbon::parse($req['start_date']);
        $endDate = Carbon::parse($req['end_date']);
        $res = ['result' => 1, 'payments' => []];
        if ($endDate->lt($startDate) || $endDate->copy()->subMonth()->gt($startDate)) {
            return ['result' => 0];
        }
        $res1c = $mySoap->getPaymentsFrom1c($startDate, $endDate);
        if ((int) $res1c->result !== 1) {
            return $res;
        }
        foreach ($res1c->tab as $payment) {
            $res['payments'][] = $payment;
        }
        return response()->json($res);
    }

    public function getPaymentsForUser(ReportsService $reportsService, PaymentUserRequest $req)
    {
        $request = $req->validated();
        $startDate = Carbon::parse($request['start_date']);
        $endDate = Carbon::parse($request['end_date']);
        if ($startDate->month !== $endDate->month || $endDate->lt($startDate)) {
            return ['result' => 0];
        }
        return response()->json($reportsService->getPaymentsForUsers($startDate, $endDate, $request['user_id']));
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

    public function exportToExcelDebtorsLoginLog(Request $request)
    {
        $user = auth()->user();

        $dateStart = $request->get('dateStart', '');
        $dateEnd = $request->get('dateEnd', '');
        $mode = $request->get('mode', 'uv');

        if (!mb_strlen($dateStart) || !mb_strlen($dateEnd)) {
            $dateStart = Carbon::now()->startOfDay();
            $dateEnd = Carbon::now()->endOfDay();
        } else {
            $dateStart = Carbon::parse($dateStart)->startOfDay();
            $dateEnd = Carbon::parse($dateEnd)->endOfDay();
        }
        $debtors = \App\DebtorsSiteLoginLog::whereBetween('created_at', [$dateStart, $dateEnd]);

        if ($mode == 'lv') {
            $debtors->where('str_podr', '000000000007');
        } else {
            $debtors->where('str_podr', '000000000006');
        }

        if (!$user->hasRole('debtors_chief')) {
            $debtors->where('responsible_user_id', $user->id);
        }
        $debtors = $debtors->get();
        return Excel::download(
            new DebtorsLoginSiteExport($debtors),
            "report_site_login_" . $dateStart->format('dmY') . "_" . $dateEnd->format('dmY') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
