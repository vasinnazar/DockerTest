<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtorsEventType extends Model
{
    protected $table = 'debtors.debtors_event_types';
    protected $fillable = ['name'];
    
    /**
     * Возвращает массив всех типов мероприятий по должникам
     * @return array
     */
    public static function getEventTypes() {
        return DebtorsEventType::lists('name', 'id')->toArray();
    }
}
