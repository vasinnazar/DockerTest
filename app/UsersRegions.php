<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class UsersRegions extends Model {
//    protected $connection = 'arm';
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_regions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
    
    /**
     * Возвращает массив регионов пользователей с ключом по id
     * @return array
     */
    public static function getRegions() {
        
        $uRegions = UsersRegions::get();
        $arReturn = [];
        foreach ($uRegions as $region) {
            $arReturn[$region->id] = $region->name;
        }
        
        return $arReturn;
    }
}
