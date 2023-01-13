<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DebtorEvent
 * @package App
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 */

class Role extends Model
{

    const ADMIN = 'admin';
    const SPEC = 'spec';
    const SEB = 'seb';
    const RUC = 'ruk';
    const DEBTORS_REMOTE = 12;
    const DEBTORS_PERSONAL = 13;
    const DEBTORS_CHIEF = 14;

    protected $table = 'roles';
    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany('App\Permission');
    }

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function hasPermission($name)
    {
        return ($this->permissions()->where('name', $name)->count() > 0);
    }

}
