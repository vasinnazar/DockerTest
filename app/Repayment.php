<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MySoap;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;

class Repayment extends Model {

    protected $table = 'repayments';
    protected $fillable = ['loan_id', 'repayment_type_id', 'time', 'fine', 'exp_pc', 'pc', 'req_money', 'created_at', 'paid_money', 'discount', 'od', 'comment', 'id_1c', 'tax', 'was_exp_pc', 'was_od', 'was_fine', 'was_pc', 'was_tax'];

    public function repaymentType() {
        return $this->belongsTo('App\RepaymentType', 'repayment_type_id');
    }

    public function orders() {
        return $this->hasMany('App\Order', 'repayment_id');
    }

    public function paysavings() {
        return $this->hasMany('App\PaySaving', 'repayment_id');
    }

    public function getPlusPaysavings() {
        return PaySaving::where('repayment_id', $this->id)->where('plus', 1)->get();
    }

    public function getPaysavingsMoney() {
        $res = ['pc' => 0, 'exp_pc' => 0, 'od' => 0, 'fine' => 0];
        $ps = $this->getPlusPaysavings();
        foreach ($ps as $item) {
            $res['pc']+=$item->pc;
            $res['exp_pc']+=$item->exp_pc;
            $res['od']+=$item->od;
            $res['fine']+=$item->fine;
        }
        return $res;
    }

    public function getNotPeacePaysOrders() {
        
    }

    public function loan() {
        return $this->belongsTo('App\Loan', 'loan_id');
    }

    public function peacePays() {
        return $this->hasMany('App\PeacePay', 'repayment_id');
    }

    /**
     * возвращает сумму оплаченную по договору
     * @return int
     */
    public function getPaidMoney() {
        $orders = $this->orders;
        $money = 0;
        foreach ($orders as $order) {
            if ($order->created_at->between(with(new Carbon($this->created_at))->subMinutes(30), with(new Carbon($this->created_at))->addMinutes(30))) {
                $money += $order->money;
            }
        }
        return $money;
    }
    public function getPaidMoneyWithTypeAndPurpose($types,$purposes) {
        $orders = $this->orders;
        $money = 0;
        foreach ($orders as $order) {
            if ($order->created_at->between(with(new Carbon($this->created_at))->subMinutes(30), with(new Carbon($this->created_at))->addMinutes(30))) {
                if(is_array($types) && in_array($order->type, $types) && is_array($purposes) && in_array($order->purpose, $purposes)){
                    $money += $order->money;
                }
            }
        }
        return $money;
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision', 'subdivision_id');
    }

    public function deleteThrough1c() {
        DB::beginTransaction();
        $orders = $this->orders;
        foreach ($orders as $order) {
            if (!$order->deleteThrough1c()) {
                DB::rollback();
                Log::error('repayment.deletethrough1c 1', ['$order' => $order]);
                return false;
            }
        }
        //ищем и удаляем заодно расходник, который иногда создается с заявлением
        if ($this->repaymentType->isDopCommission()) {
            $rko = Order::where('loan_id', $this->loan_id)
                            ->where('type', OrderType::getRKOid())
                            ->where('money', '<', $this->loan->money)
                            ->whereBetween('created_at', [
                                with(new Carbon($this->created_at))->subMinutes(30),
                                with(new Carbon($this->created_at))->addMinutes(30)
                            ])->first();
            if (!is_null($rko)) {
                if (!$rko->deleteThrough1c()) {
                    DB::rollback();
                    Log::error('repayment.deletethrough1c 4', ['rko' => $rko]);
                    return false;
                }
            }
        }

        $docType = $this->repaymentType->getMySoapItemID();
        if (!isset($docType)) {
            return false;
        }

        $res1c = MySoap::removeItem($docType, $this->id_1c, $this->loan->claim->customer->id_1c);
        if ((int) $res1c->result == 0) {
            DB::rollback();
            Log::error('repayment.deletethrough1c 2', ['rep' => $this, 'res' => $res1c]);
            return false;
        }
        if (!$this->delete()) {
            DB::rollback();
            Log::error('repayment.deletethrough1c 3', ['rep' => $this]);
            return false;
        }
        DB::commit();
        return true;
    }

    /**
     * количество дней просрочки
     * @return int
     */
    public function getOverdueDays($beforeNextRep = false) {
        $endDate = $this->created_at->setTime(0, 0, 0)->addDays($this->time);
        if (Carbon::now()->gt($endDate)) {
            if ($beforeNextRep) {
                $nextRep = Repayment::where('loan_id', $this->loan_id)->where('id_1c', '<>', $this->id_1c)->where('created_at', '>', $this->created_at->format('Y-m-d H:i:s'))->first();
                if (!is_null($nextRep) && $nextRep->created_at->gt($endDate)) {
                    return $nextRep->created_at->setTime(0, 0, 0)->diffInDays($endDate);
//                    $overdueDays = $nextRep->created_at->setTime(0, 0, 0)->diffInDays($endDate);
//                    if ($this->repaymentType->isClaim() && !$this->repaymentType->isDopCommission() && $overdueDays > 0) {
//                        $prevRep = Repayment::where('created_at', '<', $this->created_at->format('Y-m-d H:i:s'))->orderBy('created_at', 'desc')->first();
//                        if (is_null($prevRep)) {
//                            $prevRep = $this->loan;
//                        }
//                        $overdueDays += $prevRep->time;
//                    }
//                    return $overdueDays;
                } else {
                    return 0;
                }
            }
            return Carbon::now()->setTime(0, 0, 0)->diffInDays($endDate);
        } else {
            return 0;
        }
    }

    /**
     * дата завершения действия договора
     * @return \Carbon\Carbon
     */
    public function getEndDate() {
        if ($this->repaymentType->isPeace()) {
            $lastpay = PeacePay::where('repayment_id', $this->id)->orderBy('created_at', 'desc')->first();
            if (!is_null($lastpay)) {
                return new Carbon($lastpay->end_date);
            }
        }
        return with(new Carbon($this->created_at))->setTime(0, 0, 0)->addDays($this->time);
    }

    /**
     * возвращает ложь если хоть один платеж по мировому с типом 3 просрочен
     * в любых других случаях пока возвращает истину
     * @return boolean
     */
    public function isActive() {
        if ($this->repaymentType->text_id == config('options.rtype_peace3') || $this->repaymentType->text_id == config('options.rtype_peace4')) {
            foreach ($this->peacePays as $pp) {
                if (!$pp->closed && Carbon::now()->setTime(0, 0, 0)->gte(with(new Carbon($pp->end_date))->setTime(0, 0, 0)->addDay())) {
                    return false;
                }
            }
            return true;
        } else if ($this->repaymentType->isClaim()) {
            if ($this->getOverdueDays(true) > 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Получить айдишник печатной формы для данного документа
     * @return type
     */
    public function getPrintFormId() {
        if ($this->repaymentType->isSUZ()) {
            if (is_null($this->getSchedule())) {
                return $this->repaymentType->contract_form_id;
            } else {
                return $this->repaymentType->perm_contract_form_id;
            }
        }

        if ($this->loan->claim->about_client->postclient || $this->loan->claim->about_client->pensioner) {
            if ($this->loan->in_cash) {
                return $this->repaymentType->perm_contract_form_id;
            } else {
                if ($this->repaymentType->text_id == config('options.rtype_dopnik6') && (is_null($this->loan->tranche_number) || $this->loan->tranche_number == 0)) {
                    return $this->repaymentType->perm_contract_form_id;
                } else {
                    return (!is_null($this->repaymentType->card_perm_contract_form_id)) ? $this->repaymentType->card_perm_contract_form_id : $this->repaymentType->perm_contract_form_id;
                }
            }
        } else {
            if ($this->loan->in_cash) {
                return $this->repaymentType->contract_form_id;
            } else {
                if ($this->repaymentType->text_id == config('options.rtype_dopnik6') && (is_null($this->loan->tranche_number) || $this->loan->tranche_number == 0)) {
                    return $this->repaymentType->contract_form_id;
                } else {
                    return (!is_null($this->repaymentType->card_contract_form_id)) ? $this->repaymentType->card_contract_form_id : $this->repaymentType->contract_form_id;
                }
            }
        }
    }

    /**
     * Получить айдишник печатной формы для заявления о возврате средств для доп комиссии
     * @return type
     */
    public function getDopCommissionCashbackPrintFormId() {
        return ContractForm::where('text_id', 'dop_commission_cashback')->first()->id;
    }

    public function getData($json = true, $assoc = false) {
        return json_decode($this->data, $assoc);
    }

    public function updateDataParams($params) {
        $data = $this->getData();
        foreach ($params as $k => $v) {
            $data->{$k} = $v;
        }
        $this->data = json_encode($data);
        $this->save();
    }

    public function isArhivUbitki() {
        return ($this->repaymentType->isSUZ() && $this->getData()->stock_type == config('options.suz_arhiv_ub'));
    }

    public function getSchedule() {
        $data = $this->getData();
        if (isset($data->pays)) {
            return $data->pays;
        } else {
            return null;
        }
    }

    public function updatePaidMoney() {
        $paidMoney = $this->getPaidMoney();
        if ($paidMoney > 0) {
            $this->paid_money = $paidMoney;
            $this->save();
        }
    }

    public function getDopCommissionOrders() {
        $result = ['commission' => null, 'nds' => null];
        $orders = Order::where('repayment_id', $this->id)->orderBy('created_at', 'asc')->limit(2)->get();
        if (count($orders) == 2) {
            $result['commission'] = ($orders[0]->money > $orders[1]->money) ? $orders[0] : $orders[1];
            $result['nds'] = ($orders[0]->money < $orders[1]->money) ? $orders[0] : $orders[1];
        }
        return $result;
    }

    public function canBeClaimedForRemove() {
        if(!is_null($this->claimed_for_remove)){
            return false;
        }
        if ($this->created_at->isToday()) {
            return true;
        } else {
            //проверка на допник прошлым днем
            if ($this->repaymentType->isDopnik()) {
                foreach ($this->orders as $order) {
                    if (!$order->created_at->isToday()) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
        return false;
    }

}
