<?php

namespace App\Utils;

use App\StrUtils;
use App\RepaymentType;
use App\Loan;
use Carbon\Carbon;
use Auth;
/**
 * Расчет оплаты допника с комиссией
 */
class DopCommissionCalculation {
    public $money_to_return = 0;
    public $money_to_pay = 0;
    public $money_for_commission = 0;
    public $money_for_nds=0;
    public $msg = '';
    public $unused_days = 0;
    public $money_spent = 0;
    
    public function __construct($loan, $time = 15, $od = null, $lastRep = null, $dateOfCalculate = null) {
        $this->calculate($loan, $time, $od, $lastRep, $dateOfCalculate);
    }
    
    public function getMsg(){
        $text = 'Сумма, необходимая для заключения соглашения с комиссией: ' . StrUtils::kopToRub($this->money_to_pay) . ' руб.';
        if($this->money_to_return>0){
            $text .= ' Клиенту будет возвращено ' . StrUtils::kopToRub($this->money_to_return) . ' руб.';
        }
        return $text;
    }
    /**
     * заполняем расчет данными
     * в расчете есть старый процент (0.02 в день, 30% от долга) до 31.01.2017 
     * и новый процент 0.016 в день от основного долга
     * @param type $loan
     * @param type $time
     * @param type $od
     * @param type $lastRep
     * @param \Carbon\Carbon $dateOfCalculate
     * @return \App\Utils\DopCommissionCalculation
     */
    public function calculate($loan, $time = 15, $od = null, $lastRep = null, $dateOfCalculate = null){
        $oldDopComPercent = 0.02;
        $newDopComPercent = 0.016;
//        $dopComPercent = 0.016;
        $dopComPercent = $oldDopComPercent;
        $dopComRepType = RepaymentType::where('text_id', config('options.rtype_claim3'))->first();
        if(!is_null($dopComRepType)){
            $dopComPercent = $dopComRepType->percent / 100;
        }
        //заглушка на всякий
        if($dopComPercent==$oldDopComPercent && Carbon::now()->gte('2017-01-30')){
            $dopComPercent = $newDopComPercent;
        }
        if (is_null($lastRep)) {
            $lastRep = $loan->getLastRepayment();
        }
        if (is_null($od)) {
            $mDet = $loan->getRequiredMoneyDetails();
            $od = $mDet->od;
        }
        //сумма считается по тарифной сетке
        $tOD = floor($od/100000)*100000;
        if($tOD<=100000){
            $tOD = 100000;
        }
        $this->money_to_pay = round($time * $dopComPercent * $tOD);
        $this->money_for_nds = round($this->money_to_pay*18/118);
        $this->money_for_commission = $this->money_to_pay-$this->money_for_nds;
        if (!is_null($lastRep) && $lastRep->repaymentType->isDopCommission()) {
            $lastRepTime = $lastRep->time;
//            $lastRepPaidMoney = $lastRep->paid_money;
//            if ($lastRepPaidMoney == 0) {
//                if(!is_null(Auth::user()) && Auth::user()->id==5){
                    $lastRepPaidMoney = $lastRep->getPaidMoneyWithTypeAndPurpose([\App\OrderType::getPKOid()],[\App\Order::P_COMMISSION,  \App\Order::P_COMMISSION_NDS]);
//                    if($lastRepPaidMoney<=0){
//                        Synchronizer::updateOrders($lastRep->created_at->format('Y-m-d H:i:s'), $loan->claim->passport->series, $loan->claim->passport->number);
//                        $lastRepPaidMoney = $lastRep->getPaidMoneyWithTypeAndPurpose([\App\OrderType::getPKOid()],[\App\Order::P_COMMISSION,  \App\Order::P_COMMISSION_NDS]);
//                    }
//                } else {
//                    $lastRepPaidMoney = $lastRep->getPaidMoney();
//                }
//            }
            $lastRepEndDate = $lastRep->getEndDate();
            $now = (is_null($dateOfCalculate))?Carbon::now()->setTime(0, 0, 0):$dateOfCalculate->setTime(0,0,0);
            $unusedDays = 0;
            if ($now->lt($lastRepEndDate)) {
                $unusedDays = $now->diffInDays($lastRepEndDate);
            }
            $this->unused_days = $unusedDays;
            
            $lastDopComPercent = $oldDopComPercent;
            if($lastRep->created_at->gte(new Carbon('2017-01-30'))){
                $lastDopComPercent = $newDopComPercent;
            }
            $lastOD = $lastRepPaidMoney/($lastRepTime*$lastDopComPercent);
            //основной долг предыдущего допника
            $odRounded = floor($lastOD/100000)*100000;
            if($odRounded<=100000){
                $odRounded = 100000;
            }

            $this->money_to_return = round($odRounded * $unusedDays * $newDopComPercent);
            $this->money_spent = $lastRepPaidMoney-$this->money_to_return;
        }
        if (!is_null($lastRep) && $lastRep->repaymentType->isDopCommission() && HelperUtil::DateIsToday($lastRep->created_at)) {
            $this->msg = 'Внимание! Для создания еще одного соглашения необходимо подать на удаление предыдущее';
        } else {
            $this->msg = $this->getMsg();
        }
        return $this;
    }
}
