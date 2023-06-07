<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DebtGroup extends Model
{
    /**
     * @groups debtors
     */
    const  DIFFICULT = 5;
    const HOPLESS = 6;

    protected $table = 'debtors.debt_groups';
    protected $fillable = ['name'];
    public $timestamps = false;

    /**
     * Возвращает массив всех групп должников
     * @return array
     */
    public static function getDebtGroups()
    {
        return DebtGroup::pluck('name', 'id')->toArray();
    }
}
