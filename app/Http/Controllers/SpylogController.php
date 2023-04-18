<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Auth,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Card,
    Illuminate\Support\Facades\DB,
    Yajra\DataTables\Facades\DataTables,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    App\Utils\StrLib,
    App\Spylog\LogDataModel;

class SpylogController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('spylog.list')->with('actions', Spylog::getActionsList());
    }

    /**
     * возвращает данные лога
     * @param int $log_data_id идентификатор лога
     * @return type
     */
    public function viewLog($log_data_id) {
        $log = LogDataModel::find($log_data_id);
        return (!is_null($log)) ? $log->data : '';
    }

    /**
     * возвращает список логов для таблицы по аяксу 
     * @param Request $request
     * @return type
     */
    public function getList(Request $request)
    {
        $cols = [
            'logs.created_at as log_created_at',
            'users.name as username',
            'logs.table_id as log_table',
            'logs.doc_id as log_doc_id',
            'logs.action as log_action',
            'logs.id as log_id',
            'logs.data_id as log_data_id'
        ];
        $spylogs = DB::connection('spylogsDB')->table('logs')
            ->groupBy('logs.id')
            ->distinct()
            ->leftJoin('armf.users', 'logs.user_id', '=', 'users.id')
            ->select($cols)->limit(100);
        return Datatables::of($spylogs)
            ->editColumn('log_created_at', function ($spylog) {
                return $spylog->log_created_at ? with(new Carbon($spylog->log_created_at))->format('d.m.Y H:i') : '';
            })
            ->editColumn('log_action', function ($spylog) {
                return '<span class="label label-default">'
                    . (Spylog::getActionName($spylog->log_action))
                    . '</span>';
            })
            ->editColumn('log_table', function ($spylog) {
                return Spylog::getTableName($spylog->log_table);
            })
            ->addColumn('view', function ($spylog) {
                $list = [
                    Spylog::ACTION_CREATE,
                    Spylog::ACTION_STATUS_CHANGE,
                    Spylog::ACTION_UPDATE,
                    Spylog::ACTION_DELETE,
                    Spylog::ACTION_SUBDIV_CHANGE,
                    Spylog::ACTION_ID1C_CHANGE,
                    Spylog::ACTION_CALL1C,
                    Spylog::ACTION_ERROR,
                    Spylog::ACTION_ERROR_ARM,
                    Spylog::ACTION_CALC_ERROR,
                    Spylog::ACTION_TERMINAL_AUTH,
                    Spylog::ACTION_TERMINAL_CASHIN,
                    Spylog::ACTION_TERMINAL_CASHOUT,
                    Spylog::ACTION_TERMINAL_FILEINFO,
                    Spylog::ACTION_TERMINAL_INCASS,
                    Spylog::ACTION_TERMINAL_ORDER,
                    Spylog::ACTION_TERMINAL_PROMO,
                    Spylog::ACTION_TERMINAL_REFILL,
                ];
                if (in_array($spylog->log_action, $list)) {
                    $html = '<button class="btn btn-default btn-xs" '
                        . 'onclick="$.spylogCtrl.viewLog(' . $spylog->log_data_id . ',' . (($spylog->log_action == Spylog::ACTION_UPDATE) ? 'true' : 'false') . ',' . $spylog->log_action . '); return false;">'
                        . '<span class="glyphicon glyphicon-eye-open"></span>'
                        . '</button>';
                    $html .= '<button class="btn btn-default btn-xs" '
                        . 'onclick="$.spylogCtrl.repeatLog(' . $spylog->log_data_id . ');">'
                        . '<span class="glyphicon glyphicon-refresh"></span>'
                        . '</button>';
                    return $html;
                } else {
                    return '';
                }
            })
            ->removeColumn('log_id')
            ->removeColumn('log_data_id')
            ->filter(function ($query) use ($request) {
                if ($request->has('name')) {
                    $query->where('users.name', 'like', "%{$request->get('name')}%");
                }
                if ($request->has('table') && $request->table >= 0) {
                    $query->where('logs.table_id', $request->table);
                }
                $list = ['doc_id', 'action'];
                foreach ($list as $v) {
                    if ($request->has($v)) {
                        $query->where('logs.' . $v, '=', $request->get($v));
                    }
                }
                if ($request->has('date_from')) {
                    $query->where('logs.created_at', '>=', new Carbon($request->get('date_from')));
                }
                if ($request->has('date_to')) {
                    $query->where('logs.created_at', '<=', new Carbon($request->get('date_to')));
                }
            })
            ->rawColumns(['view'])
            ->toJson();
    }

}
