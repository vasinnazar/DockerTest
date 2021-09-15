<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Option extends Model {

    protected $table = 'options';
    protected $fillable = ['name', 'data'];
    
    /**
     * возвращает значение параметра настройки по имени
     * @param string $name имя параметра
     * @param mixed $def значение по умолчанию, возвращается в случае если параметр в базе пустой или нету
     * @return mixed
     */
    static function getByName($name, $def = null) {
        $data = Option::where('name', $name)->value('data');
        return (empty($data) && !is_null($def)) ? $def : $data;
    }

    static function updateByName($name, $data) {
        $opt = Option::where('name', $name)->first();
        if (is_null($opt)) {
            $opt = new Option();
            $opt->name = $name;
        }
        $opt->data = $data;
        return $opt->save();
    }

}
