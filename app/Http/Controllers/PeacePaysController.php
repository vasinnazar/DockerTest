<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PeacePay;
use App\StrUtils;
use Carbon\Carbon;
use App\Order;
use App\Repayment;
use App\Spylog\Spylog;
use App\MySoap;
use Illuminate\Support\Facades\DB;

class PeacePaysController extends Controller {

    public function __construct() {
        
    }

    public function getPeacePay($id) {
        return PeacePay::find($id);
    }

    /**
     * редактирование платежа по мировому
     * @param Request $req
     * @return type
     */
    public function update(Request $req) {
        if ($req->has('id')) {
            $pay = PeacePay::find((int) $req->get('id'));
            if (is_null($pay)) {
                return redirect()->back()->with('msg', 'Платёж не найден.')->with('class', 'alert-danger');
            }
        } else {
            return redirect()->back()->with('msg', 'Платёж не найден.')->with('class', 'alert-danger');
        }
        $input = $req->all();
        foreach ($input as $k => $v) {
            if (in_array($k, ['od', 'pc', 'exp_pc', 'fine', 'paid_money', 'req_money', 'money', 'total'])) {
                $input[$k] = StrUtils::parseMoney($v);
            }
        }
        if (!$req->has('closed') || is_null($req->get('closed'))) {
            $pay->closed = 0;
        }
        $pay->fill($input);

        $rep1cParams = [
            'created_at' => with(new Carbon($pay->repayment->created_at))->format('YmdHis'),
            'Number' => $pay->repayment->id_1c,
            'passport_series' => $pay->repayment->loan->claim->passport->series,
            'passport_number' => $pay->repayment->loan->claim->passport->number,
            'money' => $pay->repayment->req_money,
            'loan_id_1c' => $pay->repayment->loan->id_1c,
            'subdivision_id_1c' => (is_null($pay->repayment->subdivision)) ? "" : $pay->repayment->subdivision->name_id,
            'user_id_1c' => (is_null($pay->repayment->user)) ? "" : $pay->repayment->user->id_1c,
            'od' => $pay->repayment->od,
            'pc' => $pay->repayment->pc,
            'exp_pc' => $pay->repayment->exp_pc,
            'fine' => $pay->repayment->fine,
            'comment' => $pay->repayment->comment,
            'time' => $pay->repayment->time,
            'pays' => ''
        ];
        $pays = $pay->repayment->peacePays;
        $paysList = [];
        foreach ($pays as $p) {
            $paysList[] = ($p->id == $pay->id) ? $pay : $p;
        }
        $rep1cParams['pays'] = json_encode($paysList);
        DB::beginTransaction();
        if (!$pay->save()) {
            return redirect()->back()->with('msg', 'Платёж не сохранён.')->with('class', 'alert-danger');
        } else {
            Spylog::logModelAction(Spylog::ACTION_UPDATE, 'peace_pays', $pay);
            return redirect()->back()->with('msg', 'Платёж сохранён.')->with('class', 'alert-success');
        }
        $res1c = MySoap::createPeaceRepayment($rep1cParams);
        if (!$res1c['res']) {
            DB::rollback();
            return redirect()->back()->with('msg', 'Платёж не сохранён. Ошибка связи с 1С')->with('class', 'alert-danger');
        }
    }

    /**
     * возвращает массив платежей с подсчитанной задолжностью по ним на текущую дату для договора с переданным идентификатором договора
     * @param int $repayment_id идентификатор договора
     * @return type
     */
    static function getPeacePays($repayment_id) {
        $rep = Repayment::find($repayment_id);
        if (is_null($rep)) {
            return [];
        }
//        $nextRep = Repayment::where('loan_id', $rep->loan_id)->where('id', '<>', $repayment_id)->where('created_at', '>=', $rep->created_at)->orderBy('created_at')->first();
//        $diffDate1 = (!is_null($nextRep)) ? (new Carbon($nextRep->created_at)) : Carbon::now();
//        \PC::debug($diffDate1,'diffdate1');
//        $pc = $rep->loan->getPercents();
//        $lastPayday = with(new Carbon($rep->loan->last_payday))->setTime(0,0,0);
        $pays = $rep->peacePays;
//        $monthsNum = count($pays);
//        for ($m = 0; $m < $monthsNum; $m++) {
//            $pays[$m]->total = $pays[$m]->total;
//            $endDate = with(new Carbon($pays[$m]->end_date))->setTime(0, 0, 0);
//            $updday = with(new Carbon($pays[$m]->last_payday))->setTime(0, 0, 0);

            //начисляем проценты на платежи у которых сегодняшняя дата больше даты платежа
//            if (Carbon::now()->gt(new Carbon($pays[$m]->end_date)) && $pays[$m]->total > 0) {
////                $days = $diffDate1->setTime(0,0,0)->diffInDays($endDate);
//                $days = $diffDate1->setTime(0,0,0)->diffInDays(($lastPayday->gt($endDate)) ? $lastPayday : $endDate);
//                \PC::debug($days,'days');
//                if($rep->loan->claim->about_client->postclient || $rep->loan->claim->about_client->pensioner){
//                    $exp_percent = config('options.peace_pay_exp_percent_perm');
//                } else {
//                    $exp_percent = config('options.peace_pay_exp_percent');
//                }
//                $exp_pc = round($pays[$m]->money * ($exp_percent / 100) * $days);
////                $pays[$m]->exp_pc += $exp_pc;
//                \PC::debug($pays[$m]->money + $pays[$m]->exp_pc,'m'.$m.'| '.$days);
//                $fine = round(((($pays[$m]->money) * ($pc['fine_pc'] / 100)) / (365 + date("L"))) * $days);
////                $fine = round(((($pays[$m]->money + $pays[$m]->exp_pc) * ($pc['fine_pc'] / 100)) / 365) * $days) + $pays[$m]->last_payment_fine_left;
//                $pays[$m]->fine += $fine;
//                $pays[$m]->fine += $exp_pc;
//                $pays[$m]->total += $exp_pc + $fine;
//            }
//        }
        return $pays;
    }

}
