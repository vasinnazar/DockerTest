<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stepenrodstv extends Model {

    protected $table = 'stepenrodstv';
    
    
    /**
     * Возвращает степень родства по id
     * @param string $id
     * @return string
     */
    static function getStepenById($id) {
        $obj = Stepenrodstv::find($id);
        if (!$obj) {
            return '';
        }
        return $obj->name;
    }

}
