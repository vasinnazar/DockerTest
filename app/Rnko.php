<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rnko extends Model {

    protected $table = 'rnko';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'claim_date'];
    protected $fillable = ['card_number', 'passport_series', 'passport_number', 'fio', 'status', 'comment', 'check_status'];

    const STATUS_NEW = 0;
    const STATUS_CHECKED = 1;
    const STATUS_CHANGED = 2;
    const STATUS_NOTFOUND = 3;

    public static function getStatusList() {
        return [
            Rnko::STATUS_NEW => 'Новый',
            Rnko::STATUS_CHECKED => 'Сверено',
            Rnko::STATUS_CHANGED => 'Отредактировано',
            Rnko::STATUS_NOTFOUND => 'Нет фотографий',
        ];
    }

}
