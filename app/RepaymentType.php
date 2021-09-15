<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RepaymentType extends Model {

    const DEF_PAY_ORDER = ["0" => "exp_pc", "1" => "pc", "2" => "od", "3" => "fine"];

    protected $table = 'repayment_types';
    protected $fillable = ['name', 'od_money', 'percents_money', 'fine_money',
        'exp_percents_money', 'condition', 'freeze_days', 'exp_percent',
        'fine_percent', 'contract_form_id', 'text_id', 'add_after_freeze',
        'mandatory_percents', 'payments_order', 'default_time', 'percent',
        'perm_contract_form_id', 'pc_after_exp', 'card_contract_form_id',
        'card_perm_contract_form_id'];

    public function getPaymentsOrder() {
        //по-умолчанию
        $paymentsOrder = RepaymentType::DEF_PAY_ORDER;
        try {
            $json = json_decode($this->payments_order, true);
            if (!is_null($json)) {
                $paymentsOrder = $json;
            }
        } catch (Exception $exc) {
            \PC::debug($exc);
        }
        return $paymentsOrder;
    }

    public function isPeace() {
        return (in_array($this->text_id, [config('options.rtype_peace'), config('options.rtype_peace2'), config('options.rtype_peace3'), config('options.rtype_peace4')])) ? true : false;
    }

    public function isClaim() {
        return (in_array($this->text_id, [config('options.rtype_claim'), config('options.rtype_claim2'), config('options.rtype_claim3')])) ? true : false;
    }

    public function isDopnik() {
        return (in_array($this->text_id, [config('options.rtype_dopnik'), config('options.rtype_dopnik2'), config('options.rtype_dopnik3'), config('options.rtype_dopnik4'), config('options.rtype_dopnik5'), config('options.rtype_dopnik6'), config('options.rtype_dopnik7'), 'exdopnik'])) ? true : false;
    }

    public function isClosing() {
        return ($this->text_id == config('options.rtype_closing')) ? true : false;
    }

    public function isSUZ() {
        return ($this->text_id == config('options.rtype_suz1') || $this->text_id == config('options.rtype_suz2')) ? true : false;
    }
    /**
     * является ли документ соглашением по акции
     * @return type
     */
    public function isSuzStock(){
        return ($this->text_id == config('options.rtype_suzstock1') || $this->text_id == config('options.rtype_suzstock2')) ? true : false;
    }
    /**
     * является ли документ допником с комиссией 30% от од
     * @return type
     */
    public function isDopCommission(){
        return ($this->text_id==config('options.rtype_claim3'));
    }
    
    public function isExDopnik(){
        return ($this->text_id=='exdopnik');
    }

    static function getClosingID() {
        $rep = RepaymentType::where('text_id', config('options.rtype_closing'))->select('id')->first();
        return (is_null($rep)) ? null : $rep->id;
    }

    public function getMySoapItemID() {
        if ($this->isClaim()) {
            return MySoap::ITEM_REP_CLAIM;
        } else if ($this->isClosing()) {
            return MySoap::ITEM_REP_CLOSING;
        } else if ($this->isDopnik()) {
            return MySoap::ITEM_REP_DOP;
        } else if ($this->isPeace()) {
            return MySoap::ITEM_REP_PEACE;
        } else if ($this->isSUZ()) {
            return MySoap::ITEM_REP_SUZ;
        } else {
            return null;
        }
    }

    public function getType() {
        if ($this->isClaim()) {
            return 'claim';
        } else if ($this->isClosing()) {
            return 'closing';
        } else if ($this->isDopnik()) {
            return 'dopnik';
        } else if ($this->isPeace()) {
            return 'peace';
        } else if ($this->isSUZ()) {
            return 'suz';
        } else {
            return '';
        }
    }

}
