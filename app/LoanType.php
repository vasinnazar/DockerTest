<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoanType extends Model {

    const STATUS_ACTIVE = 0;
    const STATUS_CLOSED = 1;
    const STATUS_CREDITSTORY1 = 2;
    const STATUS_CREDITSTORY2 = 3;
    const STATUS_CREDITSTORY3 = 4;

    protected $table = 'loantypes';
    protected $guarded = ['id'];
    protected $fillable = [
        'name', 'money', 'time', 'percent', 'start_date', 'end_date',
        'contract_form_id', 'card_contract_form_id', 'basic', 'status', 'docs',
        'show_in_terminal', 'id_1c', 'exp_pc', 'exp_pc_perm', 'fine_pc', 'fine_pc_perm',
        'pc_after_exp', 'special_pc', 'additional_contract_id', 'additional_contract_perm_id',
        'perm_contract_form_id', 'perm_card_contract_form_id', 'additional_card_contract_id', 'additional_card_contract_perm_id',
        'terminal_promo_discount'
    ];

    public function conditions() {
        return $this->belongsToMany('App\Condition', 'loantypes_conditions', 'loantype_id');
    }

    static function getTerminalPromoLoantype($promo = null) {
        if (Carbon::now()->gte(config('options.new_rules_day_010117'))) {
            $loantype = LoanType::where('id_1c', 'ARM000024')->first();
        } else {
            $loantype = LoanType::where('id_1c', 'ARM000011')->first();
        }
    }
    public function isTerminal(){
        return (in_array($this->id_1c,['ARM000017','ARM000026','ARM000023','ARM000024']));
    }
    public function isTerminal0(){
        return (in_array($this->id_1c,['ARM000017','ARM000026']));
    }
    public function isTerminal_010117(){
        return (in_array($this->id_1c,['ARM000023','ARM000024','ARM000026']));
    }
    public function getPercent($date=null){
        if(is_null($date)){
            $date = Carbon::now()->format('Y-m-d H:i:s');
        }
        $loanrate = LoanRate::getByDate($date);
        if($this->isTerminal()){
            return $loanrate->pc;
        } else {
            return $this->pc;
        }
    }

}
