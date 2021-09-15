<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeacePay extends Model {

    protected $table = 'peace_pays';
    protected $fillable = ['repayment_id','exp_pc','fine','money','closed','end_date','created_at','total','last_payday','last_payment_fine_left'];
//    protected $guarded = ['repayment_id','exp_pc','fine','money','closed','end_date','created_at'];
    
    public function repayment() {
        return $this->belongsTo('App\Repayment');
    }
    public function orders() {
        return $this->hasMany('App\Order','peace_pay_id');
    }
    public function getOrders(){
        $orders = $this->repayment->orders;
        $res = [];
        $tempMoney = $this->money;
        $freeOrders = [];
        foreach ($orders as $order){
            if(is_null($order->peace_pay_id)){
                $freeOrders[] = $order;
            }
        }
        $pays = $this->repayment->peacePays;
        
        foreach ($pays as $pay){
            if($this != $pay){                
                
            } else {
                
                break;
            }
        }
        return $res;
    }

}
