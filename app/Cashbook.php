<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Support\Facades\DB;

class Cashbook extends Model {

    const MINUS = 0;
    const PLUS = 1;

    protected $fillable = ['money', 'subdivision_id', 'action', 'loan_id', 'balance', 'order_id'];
    protected $table = 'cashbook';

    static function make($params) {
        $subdiv_id = Auth::user()->subdivision_id;
        $cb = Cashbook::select('balance')
                ->where('subdivision_id', $subdiv_id)
                ->where('order_id','<>',$params['order_id'])
                ->orderBy('created_at', 'desc')
                ->first();
        $params['subdivision_id'] = $subdiv_id;
        $params['balance'] = ((!is_null($cb)) ? $cb->balance : 0) + $params['money'];

        DB::beginTransaction();
//        $existingCB = Cashbook::where('order_id',$params['order_id'])->first();
//        if(!is_null($existingCB)){
//            
//        }
        $cashbook = Cashbook::create($params);
        if (!is_null($cashbook)) {
            DB::commit();
            return $cashbook;
        } else {
            DB::rollback();
            return null;
        }
    }

    static function makeFromOrder($order) {
        $cb_params = ['money' => $order->money, 'action' => Cashbook::PLUS, 'order_id' => $order->id];
        \PC::debug($order);
        if (!$order->orderType->plus) {
            $cb_params['money']*=-1;
            $cb_params['action'] = Cashbook::MINUS;
        }
        return Cashbook::make($cb_params);
    }

}
