<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Spylog\Spylog;
use App\Spylog\SpylogModel;
use App\Subdivision;
use App\WorkTime;
use Auth;
use App\Utils\PermLib;
use App\Permission;

class ReportsController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
    }

    public function reports() {
        return view('reports.reports');
    }
    /**
     * Открывает отчет по отсутствующим подразделениям
     * Просматривает все учеты рабочего времени за сегодня и отбирает подразделения для которых учетов сегодня не было
     * Заодно исключаем терминалы, киви, телепорт
     * @return type
     */
    public function getAbsentSubdivisions() {
        if(!Auth::user()->hasPermission(Permission::makeName(PermLib::ACTION_OPEN, PermLib::SUBJ_NO_SUBDIVS_REPORT))){
            return $this->backWithErr(\App\Utils\StrLib::ERR_NOT_ADMIN);
        }
        $workTimes = WorkTime::whereBetween('date_start', [Carbon::today(), Carbon::tomorrow()])->lists('subdivision_id');
        $subdivs = Subdivision::whereNotIn('id', $workTimes)->where('closed',0)->where('is_terminal',0)->whereNotNull('address')->whereNotIn('name',['QIWI','Teleport'])->get();
        return view('reports.absent_subdivisions', ['subdivisions' => $subdivs, 'total'=>count($subdivs)]);
    }

}
