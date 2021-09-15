<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtGroup extends Model
{
    protected $table = 'debtors.debt_groups';
    protected $fillable = ['name'];
    
    /**
     * Возвращает массив всех групп должников
     * @return array
     */
    public static function getDebtGroups() {
        return DebtGroup::lists('name', 'id')->toArray();
    }
}
