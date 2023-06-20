<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

//group_id: 0-admin, 1-user,-1 - superadmin
/**
 * Class Debtor
 * @package App
 * @method bySort(string $type = null,int $sort = null)
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class DebtorSmsTpls extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'debtor_sms_tpls';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['sort'];

    public function scopeBySort($query, string $type = null, int $sort = null)
    {
        if ($type == 'remote' && $sort) {
            return $query->where('sort', 1);
        }
        return $query->where('sort', null);
    }
}
