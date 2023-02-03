<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorsEventType extends Model
{
    protected $table = 'debtors.debtors_event_types';
    protected $fillable = ['name'];

    const TYPE_INFORMATION = 9;
    const RESULT_TYPE_CONSENT_TO_PEACE = 19;
    
    /**
     * Возвращает массив всех типов мероприятий по должникам
     * @return array
     */
    public static function getEventTypes() {
        return DebtorsEventType::lists('name', 'id')->toArray();
    }
}
