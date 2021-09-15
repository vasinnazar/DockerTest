<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model {

    protected $table = 'promocodes';
    protected $fillable = ['number'];

    public function isAvailable($toClaim = true, $claim_id = null) {
        if ($toClaim) {
            $claims = Claim::where('promocode_id', $this->id)->count();
            if ($claims > config('options.promocode_activate_num')) {
                return false;
            }
            if (!is_null($claim_id)) {
                $loan = Loan::where('claim_id', $claim_id)->first();
                if (!is_null($loan) && $loan->loanType->percent < 2) {
                    return false;
                }
            }
            return true;
        } else {
            $loan = Loan::where('promocode_id', $this->id)->first();
            if (!is_null($loan) && Claim::where('promocode_id', $this->id)->count() == 1) {
                return true;
            }
            return false;
        }
//        if(!is_null($claim_id)){
//            $claim  = Claim::find($claim_id);
//            if(is_null($claim)){
//                return true;
//            }
//            $loan = Loan::where('claim_id',$claim_id)->first();
//            
//        }
    }

    public function usedByCustomer($customer_id) {
        return (Claim::where('customer_id', $customer_id)->where('promocode_id', $this->id)->count() > 0);
    }
    
    static function generateNumber(){
        return rand(100000,999999);
    }

}
