<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorLog extends Model
{
    protected $table='debtor_logs';
    
    public function user(){
        return $this->belongsTo('\App\User','user_id');
    }
    
    public function debtor(){
        return $this->belongsTo('\App\Debtor','debtor_id');
    }
    public function debtor_event(){
        return $this->belongsTo('\App\DebtorEvent','debtor_event_id');
    }
    
    public function getBeforeAttribute($value){
        return json_decode($value);
    }
    public function getAfterAttribute($value){
        return json_decode($value);
    }
    static function getFieldName($field,$doctype){
        $debtorFields = Debtor::getFields();
        $debtorEventFields = DebtorEvent::getFields();
        if($doctype==1){
            return (array_key_exists($field, $debtorEventFields))?$debtorEventFields[$field]:'-';
        }
        if($doctype==0){
            return (array_key_exists($field, $debtorFields))?$debtorFields[$field]:'-';
        }
        return '-';
    }
    # id, customer_id_1c, loan_id_1c, is_debtor, od, pc, exp_pc, fine, tax, created_at, updated_at, last_doc_id_1c, base, responsible_user_id_1c, fixation_date, 
    # refresh_date, qty_delays, sum_indebt, debt_group, debtor_id_1c, last_user_id

}
