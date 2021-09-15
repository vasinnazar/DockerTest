<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\StrUtils;

class PlannedDeparture extends Model {

    protected $table = 'debtors.planned_departures';
    protected $fillable = ['created_at', 'debtor_id'];
    
    /**
     * Создает метку плана выезда к должнику
     * @param string $debtor_id
     * @return void
     */
    static function addPlanDeparture($debtor_id) {
        $row = PlannedDeparture::create();
        $row->debtor_id = $debtor_id;
        $row->save();
        
        return;
    }
    
    /**
     * Проверяет запланирован ли выезд к должнику
     * @param string $debtor_id
     * @return boolean
     */
    static function isPlanned($debtor_id) {
        //$today = with(new Carbon())->today();
        //$row = PlannedDeparture::where('debtor_id', $debtor_id)->where('created_at', '>=', $today)->first();
        
        $row = PlannedDeparture::where('debtor_id', $debtor_id)->first();
        
        if (is_null($row)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Удаляет метку плана выезда к должнику
     * @param type $debtor_id
     * @return void
     */
    static function removePlanDeparture($debtor_id) {
        //$today = with(new Carbon())->today();
        //PlannedDeparture::where('debtor_id', $debtor_id)->where('created_at', '>=', $today)->delete();
        
        PlannedDeparture::where('debtor_id', $debtor_id)->delete();
        
        return;
    }
    
    /**
     * Возвращает кол-во запланированных выездов для пользователя
     * @return int
     */
    static function getCountPlanned() {
        $today = with(new Carbon())->today();
        //return PlannedDeparture::whereBetween('created_at', array($today->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $today->setTime(23, 59, 59)->format('Y-m-d H:i:s')))->groupBy('debtor_id')->count();
        $arResponsibleUserIds = DebtorUsersRef::getUserRefs();
        $usersDebtors = User::select('users.id_1c')
                ->whereIn('id', $arResponsibleUserIds);

        $arUsersDebtors = $usersDebtors->get()->toArray();
        $arIn = [];
        foreach ($arUsersDebtors as $tmpUser) {
            $arIn[] = $tmpUser['id_1c'];
        }
        
        $departures = PlannedDeparture::select(DB::raw('*'))
                ->leftJoin('debtors', 'debtors.id', '=', 'planned_departures.debtor_id')
                ->whereBetween('planned_departures.created_at', array($today->setTime(0, 0, 0)->format('Y-m-d H:i:s'), $today->setTime(23, 59, 59)->format('Y-m-d H:i:s')))
                ->groupBy('debtor_id');
        
        if (count($arIn)) {
            $departures->whereIn('debtors.responsible_user_id_1c', $arIn);
        }
        
        return count($departures->get());
    }
}
