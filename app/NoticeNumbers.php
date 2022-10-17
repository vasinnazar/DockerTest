<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Debtor;
use Auth;

class NoticeNumbers extends Model
{
    protected $table = 'debtors.notice_numbers';
    protected $fillable = ['date_sent', 'debtor_id_1c', 'customer_id_1c', 'loan_id_1c', 'str_podr', 'user_id_1c', 'is_ur_address'];
    
    /**
     * Осуществляет сохранение данных при печати уведомлений, если уже был распечатан сегодня, то возвращает существующий номер (id)
     * @return int
     */
    public static function addRecord($doc_id, $debtor_id, $factAddress, $user = false) {
        if (!$user) {
            $user = auth()->user();
        }
        
        $debtor = Debtor::find($debtor_id);
        
        if (is_null($debtor)) {
            return false;
        }
        
        $is_ur_address = ($factAddress == 1) ? 0 : 1;
        
        $notice_exists = NoticeNumbers::where('debtor_id_1c', $debtor->debtor_id_1c)->where('is_ur_address', $is_ur_address)
                ->where('created_at', '>=', date('Y-m-d', time()) . ' 00:00:00')
                ->where('created_at', '<=', date('Y-m-d', time()) . ' 23:59:59')
                ->orderBy('id', 'desc')
                ->first();
        
        if (!is_null($notice_exists)) {
            $notice_exists->new_notice = false;
            return $notice_exists;
        }
        
        $notice = new NoticeNumbers();
        $notice->debtor_id_1c = $debtor->debtor_id_1c;
        $notice->str_podr = $debtor->str_podr;
        $notice->user_id_1c = $user->id_1c;
        $notice->is_ur_address = $is_ur_address;
        $notice->save();
        
        $notice->new_notice = true;
        return $notice;
    }
}
