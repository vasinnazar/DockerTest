<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

//group_id: 0-admin, 1-user,-1 - superadmin
class DebtorUsersRef extends Model {
//    protected $connection = 'arm';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'debtors.debtor_users_ref';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['master_user_id', 'user_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Возвращает подчиненных пользователей
     * @param string $master_user_id
     * @return json
     */
    static function getDebtorSlaveUsers($master_user_id) {
        $slaves = DebtorUsersRef::select(array('debtor_users_ref.user_id as user_id'))
                ->where('master_user_id', $master_user_id);
        
        return json_encode($slaves->get()->toArray());
    }
    
    static function isMasterUserWithSlaves($master_user_id) {
        return (DebtorUsersRef::select(array('debtor_users_ref.user_id as user_id'))
                ->where('master_user_id', $master_user_id)
                ->count()>0);
    }
    
    
    /**
     * Сохранение подчиненных пользователей
     * @param array $data
     * @return boolean
     */
    static function saveRefs($data) {
        $saveSlaves = DebtorUsersRef::where('master_user_id', $data['master_user_id'])->delete();
        
        foreach ($data['masterTo'] as $slave_id) {
            $row = DebtorUsersRef::create();
            $row->master_user_id = $data['master_user_id'];
            $row->user_id = $slave_id;
            
            $row->save();
        }
        
        return true;
    }
    
    /**
     * Получает массив ID пользователей (ответственных), учитывая подчиненных
     * @param boolean $all
     * @return array
     */
    static function getUserRefs($all = false) {
        if ($all) {
            return [];
        }
        
        $currentUserId = Auth::id();
        
        // получение пользователей, которые подчинены текущему
        $debtorUsersRef = DebtorUsersRef::where('master_user_id', $currentUserId);
        $arDebtorUserRef = $debtorUsersRef->get()->toArray();
        
        $arIn = [];
        foreach ($arDebtorUserRef as $ref) {
            if (is_null($ref['user_id']) || !mb_strlen($ref['user_id'])) {
                continue;
            }
            $arIn[] = $ref['user_id'];
        }
        $arIn[] = $currentUserId;
        
        return $arIn;
    }
}
