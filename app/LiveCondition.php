<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LiveCondition extends Model {

    protected $table = 'live_conditions';
    protected $id;
    protected $name;
    public $timestamps = false;

//    public function zhusl() {
//        return $this->morphTo('zhusl','id');
//    }
    
    /**
     * Возвращает жилищное условие по id
     * @param string $id
     * @return string
     */
    static function getLiveConditionById($id) {
        $obj = LiveCondition::find($id);
        if (!$obj) {
            return '';
        }
        return $obj->name;
    }
}
