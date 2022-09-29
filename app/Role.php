<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {

    const ADMIN = 'admin';
    const SPEC = 'spec';
    const SEB = 'seb';
    const RUC = 'ruk';
    const DEBTORS_REMOTE = 12;
    const DEBTORS_PERSONAL = 13;

    protected $table = 'roles';
    protected $fillable = ['name','description'];

    public function permissions() {
        return $this->belongsToMany('App\Permission');
    }

    public function users() {
        return $this->belongsToMany('App\User');
    }
    
    public function hasPermission($name){
        return ($this->permissions()->where('name',$name)->count()>0);
    }

}
