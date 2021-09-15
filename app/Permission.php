<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Utils\PermLib;

class Permission extends Model {

    const ACTION_SELECT = 'select';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const COND_ALL = 'all';
    const COND_SUBDIV = 'subdivision';
    const COND_USER = 'user';
    const TIME_ALL = 'ever';
    const TIME_YEAR = 'year';
    const TIME_DAY = 'day';
    const TIME_HOUR = 'hour';

    protected $table = 'permissions';
    protected $fillable = ['name', 'description'];

    public function roles() {
        return $this->belongsToMany('App\Role', 'permission_role', 'permission_id', 'role_id');
    }

    public function getNameArray() {
        $ar = explode('.', $this->name);
        $res = [
            'action' => $ar[0],
            'subject' => $ar[1],
            'condition' => $ar[2],
            'time' => $ar[3]
        ];
        return $res;
    }

    static function makeName($action, $subject, $condition = 'all', $time = 'ever') {
        return $action . '.' . $subject . '.' . $condition . '.' . $time;
    }

    static function getTablesList() {
        $tableNames = Spylog\Spylog::$tablesNames;
        $res = [];
        foreach ($tableNames as $tn) {
            if ($tn != '') {
                $res[$tn] = $tn;
            }
        }
        return $res;
    }

    static function getActionsList() {
        return PermLib::getActions(true);
    }

    static function getConditionsList() {
        return PermLib::getConditions(true);
    }

    static function getTimeList() {
        return PermLib::getTime(true);
    }

    static function getSubjectsList() {
        return PermLib::getSubjects(true);
    }

}
