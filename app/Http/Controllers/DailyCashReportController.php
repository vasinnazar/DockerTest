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
    App\Order,
    Illuminate\Support\Facades\DB,
    Yajra\DataTables\Facades\DataTables,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    App\DailyCashReport,
    App\Cashbook,
    App\ContractForm,
    mikehaertl\wkhtmlto\Pdf,
    Log,
    App\MySoap,
    App\Utils\StrLib,
    App\OrderType,
    App\Http\Controllers\DocsRegisterController,
    App\Subdivision,
    App\StrUtils;

class DailyCashReportController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * показывает страницу со списком ежедневных отчетов
     * @return type
     */
    public function getListView() {
        return view('reports.list')
                        ->with('report', DailyCashReport::whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()])
                                ->where('subdivision_id', Auth::user()->subdivision_id)->first());
    }

    /**
     * возвращает список ежедневных отчетов для таблицы по аяксу
     * @return type
     */
    public function getList(Request $req)
    {
        $tablename = (new DailyCashReport())->getTable();

        $reports = DB::table($tablename)
            ->leftJoin('users', $tablename . '.user_id', '=', 'users.id')
            ->leftJoin('subdivisions', $tablename . '.subdivision_id', '=', 'subdivisions.id')
            ->select($tablename . '.id as id', 'matches', $tablename . '.created_at as created_at',
                'start_balance as sb', 'end_balance as eb', 'report_start_balance as rsb', 'report_end_balance as reb',
                'users.name as username', 'subdivisions.name as subdiv_name', 'subdivisions.id as subdiv_id',
                $tablename . '.id_1c as report_id_1c');
        if (!Auth::user()->isAdmin()) {
            $reports = $reports->where($tablename . '.subdivision_id', Auth::user()->subdivision_id);
        }
        return Datatables::of($reports)
            ->removeColumn('id')
            ->editColumn('matches', function ($report) {
                return ((bool)$report->matches) ? '<span class="glyphicon glyphicon-ok"></span>' : '<span class="glyphicon glyphicon-remove"></span>';
            })
            ->editColumn('sb', function ($report) {
                $html = ($report->rsb / 100) . ' руб.';
                if (Auth::user()->id == 5) {
                    $html .= '<br><small style="color:#A9A9A9;">' . ($report->sb / 100) . ' руб.' . '</small>';
                }
                return $html;
            })
            ->editColumn('eb', function ($report) {
                $html = ($report->reb / 100) . ' руб.';
                if (Auth::user()->id == 5) {
                    $html .= '<br><small style="color:#A9A9A9;">' . ($report->eb / 100) . ' руб.' . '</small>';
                }
                return $html;
            })
            ->editColumn('created_at', function ($report) {
                $html = with(new Carbon($report->created_at))->format('d.m.Y');
                if (Auth::user()->isAdmin()) {
                    $html .= '<br><small class="text-muted">№' . $report->report_id_1c . '</small>';
                }
                return $html;
            })
            ->editColumn('username', function ($report) {
                if (Auth::user()->isAdmin()) {
                    return $report->username . '<br>(<small>' . $report->subdiv_name . '</small>)';
                } else {
                    return $report->username;
                }
            })
            ->addColumn('actions', function ($report) {
                $html = '<div class="btn-group btn-group-sm">';
                $html .= '<a href="' . url('reports/pdf/dailycashreport/' . $report->id) . '" target="_blank" class="btn btn-default"><span class="glyphicon glyphicon-print"></span> Кассовая книга</a>';
                $html .= '<a title="Редактировать" class="btn btn-default" href="' . url('reports/dailycashreport/' . $report->id) . '"><span class="glyphicon glyphicon-pencil"></span></a>';
                if (Auth::user()->isAdmin()) {
                    $html .= '<a title="Удалить" class="btn btn-default" href="' . url('reports/remove') . '?id=' . $report->id . '"><span class="glyphicon glyphicon-remove"></span></a>';
                    $html .= '<a title="Синхронизировать кассовую книгу" class="btn btn-default" href="' . url('reports/cashbook/sync2') .
                        '?date=' . Carbon::now()->setTime(0, 0, 0)->format('Y-m-d') .
                        '&subdivision_id=' . $report->subdiv_id . '"><span class="glyphicon glyphicon-refresh"></span></a>';
                    $html .= '<a title="Сверить с кассовой книгой" class="btn btn-default" href="#" onclick="$.reportsListCtrl.matchWithCashbook(' . $report->id . '); return false;"><span class="glyphicon glyphicon-check"></span></a>';
                    $html .= '<a title="Поставить галочку вручную" class="btn btn-default" href="' . url('reports/setmatch?id=' . $report->id) . '"><span class="glyphicon glyphicon-ok"></span></a>';
                    $html .= '<a title="Дать доступ к редактированию" class="btn btn-default" href="' . url('reports/dailycashreport/editable/toggle?id=' . $report->id) . '"><span class="glyphicon glyphicon-pencil"></span></a>';
                } else {
                    $html .= '<button disabled class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->filter(function ($query) use ($req, $tablename) {
                if ($req->has('subdiv_name')) {
                    $query->where('subdivisions.name', 'like', "%" . $req->get('subdiv_name') . "%");
                }
                if ($req->has('subdivision_id')) {
                    $query->where('subdivisions.id', $req->subdivision_id);
                }
                if ($req->has('subdivision_code')) {
                    $query->where('subdivisions.name_id', $req->subdivision_code);
                }
                if ($req->has('created_at')) {
                    $query->where($tablename . '.created_at', '>=',
                        with(new Carbon($req->get('created_at')))->setTime(0, 0, 0)->format('Y-m-d H:i:s'))
                        ->where($tablename . '.created_at', '<=',
                            with(new Carbon($req->get('created_at')))->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
                }
            })
            ->removeColumn('subdiv_name')
            ->removeColumn('subdiv_id')
            ->removeColumn('report_id_1c')
            ->removeColumn('rsb')
            ->removeColumn('reb')
            ->rawColumn(['actions','matches','sb','eb','created_at','username'])
            ->toJson();
    }
    /**
     * Установить галочку что отчет совпадает вручную
     * @param Request $req
     * @return type
     */
    public function setMatch(Request $req) {
        if (!$req->has('id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $rep = DailyCashReport::find($req->id);
        if (is_null($rep)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        $rep->matches = 1;
        $rep->save();
        return redirect()->back()->with('msg_suc', StrLib::SUC_SAVED);
    }

    /**
     * открывает редактор ежедневного отчета. либо пустой либо для отчета с переданным идентификатором
     * @param int $report_id идентификатор отчета
     * @return type
     */
    public function getView($report_id = null) {
        $report = DailyCashReport::find($report_id);
        if (is_null($report_id)) {
            $report = DailyCashReport::where('subdivision_id', Auth::user()->subdivision_id)
                    ->whereBetween('created_at', [Carbon::now()->setTime(0, 0, 0)->format('Ymd H:i:s'), Carbon::now()->setTime(0, 0, 0)->format('Ymd H:i:s')])
                    ->first();
            if (is_null($report)) {
                $report = new DailyCashReport();
            }
            $report = $this->updateReportFrom1c(Carbon::now()->format('Ymd'), Auth::user()->subdivision->name_id);
            $res1c = MySoap::getCashbookBalance(Carbon::now()->format('Ymd'), Auth::user()->subdivision->name_id);
            $this->cashbookSyncer(Carbon::now(), Auth::user()->subdivision, $res1c);
        } else {
            $report = $this->updateReportFrom1c(with(new Carbon($report->created_at))->format('Ymd'), $report->subdivision->name_id, $report);
            \PC::debug($report, 'dcr report');
            \PC::debug($report->subdivision, 'dcr subdiv');
            $res1c = MySoap::getCashbookBalance(with(new Carbon($report->created_at))->format('Ymd'), $report->subdivision->name_id);
            $this->cashbookSyncer(with(new Carbon($report->created_at)), $report->subdivision, $res1c);
        }
        if (!is_null($res1c) && $res1c['res']) {
            $report->start_balance = $res1c['cashStart'] * 100;
            $report->end_balance = $res1c['cashEnd'] * 100;
        }
        return view('reports.dailyCashReport')->with('report', $report);
    }
    /**
     * Загрузить отчет по дате из 1с
     * @param type $date
     * @param type $subdivision_id_1c
     * @param type $report
     * @return DailyCashReport
     */
    public function updateReportFrom1c($date = null, $subdivision_id_1c = null, $report = null) {
        if (is_null($date)) {
            $date = Carbon::now()->format('Ymd');
        }
        if (is_null($subdivision_id_1c)) {
            $subdivision_id_1c = Auth::user()->subdivision->name_id;
        }
        $res1c = MySoap::getDailyCashReport($date, $subdivision_id_1c);
        if ($res1c['res']) {
            $actions = [
                'Приход',
                'Расход',
                'ВыдачаЗаймаНаКарту',
                'ПеремещеноИзОфиса',
                'ПеремещеноВОфис',
                'ИнкассацияСТочекБанк'
            ];
            $doctypes = [
                "Основной договор",
                "Дополнительный договор",
                "Соглашение об урегулировании задолженноти",
                "Соглашение о приостановке начисления процентов",
                "Судебное урегулирование задолженности"
            ];
            $xml = new \SimpleXMLElement($res1c['value']);
            $reports = $xml->children();
            $json_data = [];
            $dreports = [];
            foreach ($reports as $rep) {
                $dreport = DailyCashReport::where('id_1c', $rep["id"])->first();
                if (is_null($dreport)) {
                    $dreport = new DailyCashReport();
                    $dreport->user_id = Auth::user()->id;
                    $dreport->subdivision_id = Auth::user()->subdivision_id;
                    $dreport->id_1c = $rep["id"];
                }
                foreach ($rep as $item) {
                    $json_data[] = [
                        'fio' => $item['fio'],
                        'action' => array_search($item['action'], $actions),
                        'doctype' => array_search($item['doctype'], $doctypes),
                        'doc' => $item['doc'],
//                        'money' => ((strstr($item['money'], '.')===FALSE)?($item['money'].'.00'):$item['money']),
                        'money' => ($item['money'] == '') ? 0 : $item['money'],
                        'comment' => $item->comment
                    ];
                }
                if (count($json_data) > 0) {
                    $dreport->data = json_encode($json_data);
                } else {
//                $dreport->data = '{}';
                }
                if ($dreport->save()) {
                    $dreports[] = $dreport;
                } else {
                    $dreports[] = new DailyCashReport();
                }
            }
            if (count($dreports) > 0) {
                return $dreports[count($dreports) - 1];
            } else {
                return (is_null($report)) ? (new DailyCashReport()) : $report;
            }
        } else {
            return (is_null($report)) ? (new DailyCashReport()) : $report;
        }
    }

    /**
     * По переданному айдишнику находит отчёт, сравнивает его с кассовой книгой и перезаписывает данные в отчёте
     * @param int $report_id
     * @return int возвращает 0 или 1
     */
    public function matchWithCashbookById($report_id) {
        $report = DailyCashReport::find($report_id);
        if (is_null($report)) {
            return 0;
        } else {
            $balance = $this->getCashbookDayBalance($report->subdivision_id, $report->created_at);
            $matches = $this->matchWithCashbook($report, $balance);
            $report->matches = $matches;
            $report->start_balance = $balance['start'];
            $report->end_balance = $balance['end'];
            \PC::debug(['matches' => $matches, 'start_bal' => $balance['start'], 'end_Bal' => $balance['end']], 'matchwithcashbookid');
            return ($report->save()) ? $report->matches : 0;
        }
    }

    /**
     * Сравнивает переданный отчёт с кассовой книгой
     * @param \App\DailyCashReport $report отчёт
     * @param Array $balance массив со стартовым и конечным балансом кассовой книги за заданный период (не обязательный параметр, передаётся для сокращения количества запросов)
     * @return int
     */
    public function matchWithCashbook($report, $balance = null) {
        if (is_null($report)) {
            return 0;
        }
        if(is_null($balance)){
            return ($report->matchWithCashbook()) ? 1 : 0;
        } else {
            return ($report->matchWithCashbook($balance['start'],$balance['end'])) ? 1 : 0;
        }
    }

    /**
     * Обновляет 
     * @param Request $request
     * @return type
     */
    public function update(Request $request) {
        $report = DailyCashReport::find((int) $request->id);
        if (is_null($report)) {
            $report = DailyCashReport::where('subdivision_id', Auth::user()->subdivision_id)
                            ->whereBetween('created_at', [Carbon::today(), Carbon::today()->setTime(23, 59, 59)])->first();
            if (is_null($report)) {
                $report = new DailyCashReport();
            }
        }
        if (!is_null($report->id) && $report->created_at->lt(Carbon::today()) && !$report->edit_enabled && !Auth::user()->isAdmin()) {
            return redirect()->back()->with('msg_err', 'Невозможно отредактировать отчёт за предыдущие даты.');
        }
        $report->fill(Input::all());
        if (Auth::user()->isAdmin()) {
            if ($request->has('user_id')) {
                $report->user_id = $request->user_id;
            } else {
                $report->user_id = (is_null($report->user_id)) ? Auth::user()->id : $report->user_id;
            }
            if ($request->has('subdivision_id')) {
                $report->subdivision_id = $request->subdivision_id;
            } else {
                $report->subdivision_id = (is_null($report->subdivision_id)) ? Auth::user()->subdivision_id : $report->subdivision_id;
            }
            if ($request->has('created_at')) {
                $report->created_at = with(new Carbon($request->created_at))->format('Y-m-d H:i:s');
            }
        } else {
            $report->user_id = Auth::user()->id;
            $report->subdivision_id = Auth::user()->subdivision_id;
        }
        $balance = $this->getCashbookDayBalance($report->subdivision_id, $report->created_at, true);
        $report->start_balance = $balance['start'];
        $report->end_balance = $balance['end'];

        $report->matches = $this->matchWithCashbook($report, $balance);
        $res1c = MySoap::addDailyCashReport([
                    'date' => (is_null($report->created_at)) ? Carbon::now()->format('YmdHis') : $report->created_at->format('YmdHis'),
                    'subdivision_id_1c' => $report->subdivision->name_id,
                    'user_id_1c' => $report->user->id_1c,
                    'report' => html_entity_decode($this->generateReportXML($report->data)),
                    'id_1c' => (!is_null($report->id_1c)) ? $report->id_1c : ''
        ]);
        if ($res1c['res']) {
            $report->id_1c = $res1c['value'];
        } else {
            return redirect()->back()->with('msg_err', StrLib::ERR_1C);
        }
        $reportBalance = $report->fillReportBalance();
        
        if ($report->save()) {
            return redirect('reports/dailycashreportslist')->with('msg_suc', StrLib::SUC_SAVED);
        } else {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
    }

    public function generateReportXML($data) {
        $json = json_decode($data, true);
        $xml = new \SimpleXMLElement('<root/>');
        $actions = [
            'Приход',
            'Расход',
            'ВыдачаЗаймаНаКарту',
            'ПеремещеноИзОфиса',
            'ПеремещеноВОфис',
            'ИнкассацияСТочекБанк'
        ];
        $doctypes = [
            "Основной договор",
            "Дополнительный договор",
            "Соглашение об урегулировании задолженноти",
            "Соглашение о приостановке начисления процентов",
            "Судебное урегулирование задолженности"
        ];
        $fields = ['fio', 'action', 'doctype', 'doc', 'money'];
        foreach ($json as $item) {
            $skiprow = false;
            foreach ($fields as $f) {
                if (!array_key_exists($f, $item) || is_null($item[$f]) || $item[$f] == '') {
                    if ($f == 'doc') {
                        $item[$f] = '-';
                    } else
                    if ($f == 'money') {
                        $item[$f] = 0;
                    } else
                    if ($f == 'fio') {
                        $item[$f] = '-';
                    } else
                    if ($f == 'doctype') {
                        $item[$f] = $doctypes[0];
                    } else
                    if ($f == 'action') {
                        $item[$f] = $actions[0];
                    } else {
                        $skiprow = true;
                    }
                }
            }
            if ($skiprow) {
                continue;
            }
            $xmlItem = $xml->addChild('item');
            $xmlItem->addAttribute('fio', (string) $item['fio']);
            $xmlItem->addAttribute('action', ((array_key_exists($item['action'], $actions)) ? $actions[$item['action']] : $actions[0]));
            $xmlItem->addAttribute('doctype', ((array_key_exists($item['doctype'], $doctypes)) ? $doctypes[$item['doctype']] : $doctypes[0]));
            $xmlItem->addAttribute('doc', $item['doc']);
            $xmlItem->addAttribute('money', $item['money']);
            $xmlItem->addAttribute('comment', str_replace('"', "'", $item['comment']));
        }
        return $xml->asXML();
    }

    /**
     * Возвращает баланс для подразделения за день
     * @param int $subdiv_id идентификатор подразделения
     * @param type $date дата строкой
     * @return Array["start"=>"","end"=>""]
     */
    public function getCashbookDayBalance($subdiv_id, $date = null, $from1c = false) {
//        if ($from1c) {
//            $res1c = $this->getCashbookBalanceFrom1c(Carbon::now()->format('Ymd'), Auth::user()->subdivision->name_id);
//            if (!is_null($res1c)) {
//                return ['start' => $res1c['cashStart'] * 100, 'end' => $res1c['cashEnd'] * 100];
//            }
//        }
        $dates = $this->getDailyDates($date);
        $queries = $this->getDailyOrdersQueries($subdiv_id, $dates);
        $startBalance = $queries['start']['plus']->sum('orders.money') - $queries['start']['minus']->sum('orders.money');
//        $endBalance = $queries['end']['plus']->sum('orders.money') - $queries['end']['minus']->sum('orders.money');
        $cardTypeID = \App\OrderType::getCARDid();
        $income = Order::where('subdivision_id', $subdiv_id)
                        ->whereBetween('created_at', [$dates['end'][0]->format('Y-m-d H:i:s'), $dates['end'][1]->format('Y-m-d H:i:s')])
                        ->leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                        ->where('orders.type', '<>', $cardTypeID)
                        ->where('order_types.plus', 1)->sum('orders.money');
        $outcome = Order::where('subdivision_id', $subdiv_id)
                        ->whereBetween('created_at', [$dates['end'][0]->format('Y-m-d H:i:s'), $dates['end'][1]->format('Y-m-d H:i:s')])
                        ->leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                        ->where('orders.type', '<>', $cardTypeID)
                        ->where('order_types.plus', 0)->sum('orders.money');
        $endBalance = $startBalance + $income - $outcome;
        return ['start' => $startBalance, 'end' => $endBalance];
    }

    public function getCashbookPeriodBalance($subdiv_id, $dateStart, $dateEnd) {
        $datesStart = [
            with(new Carbon($dateStart))->subDay()->setTime(0, 0, 0),
            with(new Carbon($dateStart))->subDay()->setTime(23, 59, 59)
        ];
        $datesEnd = [
            with(new Carbon($dateEnd))->addDays(1)->setTime(0, 0, 0),
            with(new Carbon($dateEnd))->addDays(1)->setTime(23, 59, 59)
        ];
        $queries = $this->getDailyOrdersQueries($subdiv_id, ['start' => $datesStart, 'end' => $datesEnd]);
        $startBalance = $queries['start']['plus']->sum('orders.money') - $queries['start']['minus']->sum('orders.money');
        $endBalance = $queries['end']['plus']->sum('orders.money') - $queries['end']['minus']->sum('orders.money');
        Log::info('cashbook period balance', ['start' => $datesStart, 'end' => $datesEnd, 'queries' => $queries]);
        $res = ['start' => $startBalance, 'end' => $endBalance];
        return $res;
    }

    /**
     * возвращает запросы ордеров к базе для переданных дат и подразделения
     * @param int $subdiv_id идентификатор подразделения
     * @param array $dates массив дат
     * @return type
     */
    function getDailyOrdersQueries($subdiv_id, $dates) {
        $cardTypeID = \App\OrderType::getCARDid();
        return [
            'start' => [
                'minus' => Order::where('subdivision_id', $subdiv_id)
                        ->where('created_at', '<', $dates['start'][1])
                        ->leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                        ->where('orders.type', '<>', $cardTypeID)
                        ->where('order_types.plus', 0),
                'plus' => Order::leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                        ->where('created_at', '<', $dates['start'][1])
                        ->where('subdivision_id', $subdiv_id)
                        ->where('orders.type', '<>', $cardTypeID)
                        ->where('order_types.plus', 1)
            ],
            'end' => [
                'minus' => Order::where('subdivision_id', $subdiv_id)
                        ->where('created_at', '<', $dates['end'][1])
                        ->leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                        ->where('orders.type', '<>', $cardTypeID)
                        ->where('order_types.plus', 0),
                'plus' => Order::leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                        ->where('created_at', '<', $dates['end'][1])
                        ->where('subdivision_id', $subdiv_id)
                        ->where('orders.type', '<>', $cardTypeID)
                        ->where('order_types.plus', 1)
            ]
        ];
    }

    /**
     * возвращает массив ордеров для подразделения на переданную дату
     * @param int $subdiv_id идентификатор подразделения
     * @param string $date дата строкой из базы
     * @return type
     */
    public function getDailyOrders($subdiv_id, $date = null) {
        $dates = $this->getDailyDates($date);
        $queries = $this->getDailyOrdersQueries($subdiv_id, $dates);
        return [
            'minus' => $queries['end']['minus']->where('created_at', '>=', $dates['end'][0])->get(),
            'plus' => $queries['end']['plus']->where('created_at', '>=', $dates['end'][0])->get()
        ];
    }

    /**
     * возвращает дату-время начала и дату-время завершения для переданной даты
     * @param type $date
     * @return array
     */
    public function getDailyDates($date = null) {
        if (!is_null($date)) {
            $datesStart = [
                with(new Carbon($date))->subDay()->setTime(0, 0, 0),
                with(new Carbon($date))->subDay()->setTime(23, 59, 59)
            ];
            $datesEnd = [
                with(new Carbon($date))->setTime(0, 0, 0),
                with(new Carbon($date))->setTime(23, 59, 59)
            ];
        } else {
            $datesStart = [Carbon::yesterday(), Carbon::today()];
            $datesEnd = [Carbon::today(), Carbon::tomorrow()];
        }
        return ['start' => $datesStart, 'end' => $datesEnd];
    }

    public function createPdf3($report_id = null, $dateFrom = null, $dateTo = null, $subdivision_id = null) {
        if (is_null($report_id) && (is_null($dateFrom) || is_null($dateTo))) {
            return 'Переданы не все обязательные параметры';
        }

        $contract = ContractForm::where('text_id', config('options.dailycashreport'))->first();
        if (is_null($contract)) {
            return 'Ошибка! Форма не найдена!';
        }
        $html = $contract->template;

        $tbody = '';
        $income = 0;
        $income_docs_num = 0;
        $outcome = 0;
        $outcome_docs_num = 0;
        $salary = 0;
        $report = null;
        $subdiv = null;
        if (!is_null($report_id)) {
            $report = DailyCashReport::find($report_id);
            $subdiv = $report->subdivision;
        } else if (!is_null($subdivision_id)) {
            $subdiv = \App\Subdivision::find($subdivision_id);
            \PC::debug($subdiv);
        }
        if (!is_null($report_id)) {
            $report = DailyCashReport::find($report_id);
            try {
                $data = json_decode($report->data, true);
            } catch (Exception $exc) {
                $data = null;
            }
            $this->cashbookSyncer(new Carbon($dateFrom), $report->subdivision);
//            $orders = $this->getDailyOrders($report->subdivision_id, $report->created_at);
//            $orders = Order::whereBetween('created_at', [$report->created_at, $report->created_at->addDay()->format('Y-m-d H:i:s')])->where('subdivision_id', $report->subdivision_id)->get();
            $orders = $this->getCashbookOrders($report->created_at, $report->created_at, $report->subdivision_id);
            $balance = $this->getCashbookPeriodBalance($report->subdivision_id, $report->created_at, $report->created_at);
            $reportStartBalance = $balance['start'];
            $reportEndBalance = $balance['end'];
            $reportCreatedAt = with(new Carbon($report->created_at))->format('d.m.Y');
        } else {
            if (is_null(Auth::user())) {
                return redirect()->back()->with('msg_err', 'Ошибка авторизации');
            }
            $subdiv_id = (Auth::user()->isAdmin() && !is_null($subdivision_id)) ? $subdivision_id : Auth::user()->subdivision_id;
            $this->cashbookSyncer(new Carbon($dateFrom), \App\Subdivision::find($subdiv_id));
            $orders = $this->getCashbookOrders($dateFrom, $dateTo, $subdiv_id);
            $balance = $this->getCashbookPeriodBalance($subdiv_id, $dateFrom, $dateTo);
            $reportStartBalance = $balance['start'];
            $reportEndBalance = $balance['end'];
            $reportCreatedAt = with(new Carbon($dateTo))->format('d.m.Y');
        }
        if (is_null($subdiv)) {
            Log::error('DailyCashReportController.createPdf3', ['subdiv' => $subdiv, 'report' => $report, 'subdivision_id' => $subdivision_id, 'user' => Auth::user()]);
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_SUBDIV);
        }
        if (is_null($report)) {
            $docs = DocsRegisterController::getDocsFrom1c($dateFrom, $dateTo, $subdiv->name_id, '', 8);
            $docs = array_merge($docs, DocsRegisterController::getDocsFrom1c($dateFrom, $dateTo, $subdiv->name_id, '', 2));
//            $docs = DocsRegisterController::getDocsFrom1c($dateFrom, $dateTo, $subdiv->name_id, '', 11);
        } else {
            $docs = DocsRegisterController::getDocsFrom1c($report->created_at, $report->created_at, $subdiv->name_id, '', 8);
            $docs = array_merge($docs, DocsRegisterController::getDocsFrom1c($report->created_at, $report->created_at, $subdiv->name_id, '', 2));
//            $docs = DocsRegisterController::getDocsFrom1c($report->created_at, $report->created_at, $subdiv->name_id, '', 11);
        }

        $salaryTypeID = \App\OrderType::getSALARYid();
        foreach ($docs as $d) {
            if (!array_key_exists('number', $d)) {
                continue;
            }
            $in_docs = false;
            $tr_order = null;
            foreach ($orders as $order) {
                if ($order->number == $d['number'] && $order->getMySoapItemID() == $d['type']) {
                    $in_docs = true;
                    $tr_order = $order;
                    break;
                }
            }
            $money = str_replace('&nbsp;', '', str_replace(',', '.', htmlentities($d['money'])));
            $money = number_format($money, 2, '.', '') * 100;
            if ($in_docs) {
                $tr = '<tr><td style="text-align:center">' . $tr_order->number . '</td><td>';
//                $contragent = (!is_null($tr_order->passport)) ? $tr_order->passport->fio : '';
                $contragent = $d['fio'];
                $doctype = $tr_order->getInvoice();
                if ($tr_order->orderType->plus) {
                    $tr .= 'Принято от ' . $contragent;
//                    $income += $tr_order->money;
                    $income += $money;
                    $income_docs_num++;
                } else {
                    $tr .= 'Выдано ' . $contragent;
//                    $outcome += $tr_order->money;
                    $outcome += $money;
                    $outcome_docs_num++;
                }
                if ($tr_order->type == $salaryTypeID) {
                    $salary += $tr_order->money;
                }
//                $money = StrUtils::kopToRub($tr_order->money);
//                $tr .= '</td><td style="text-align:center">'
//                        . $doctype . '</td><td>'
//                        . (($order->orderType->plus) ? $money : '') . '</td><td>'
//                        . ((!$order->orderType->plus) ? $money : '') . '</td></tr>';
                $tr .= '</td><td style="text-align:center">'
                        . $doctype . '</td><td>'
                        . (($tr_order->orderType->plus) ? StrUtils::kopToRub($money) : '') . '</td><td>'
                        . (($d['type'] == '2') ? StrUtils::kopToRub($money) : '') . '</td></tr>';
                if (is_null($tr_order->number) || $tr_order->number == '') {
                    continue;
                }
                $tbody .= $tr;
            } else {
                $tr = '<tr><td style="text-align:center">' . $d['number'] . '</td><td>';
                $contragent = $d['fio'];
//                $doctype = $d['account'];
                $doctype = '';
//                $money = str_replace('&nbsp;', '', str_replace(',', '.', htmlentities($d['money'])));
//                $money = number_format($money, 2, '.', '') * 100;
                if ($d['type'] == '8') {
                    $tr .= 'Принято от ' . $contragent;
                    $income += $money;
                    $income_docs_num++;
                } else {
                    $tr .= 'Выдано ' . $contragent;
                    $outcome += $money;
                    $outcome_docs_num++;
                }
//                if ($tr_order->type == $salaryTypeID) {
//                    $salary += $tr_order->money;
//                }
                $tr .= '</td><td style="text-align:center">'
                        . $doctype . '</td><td>'
                        . (($d['type'] == '8') ? StrUtils::kopToRub($money) : '') . '</td><td>'
                        . (($d['type'] == '2') ? StrUtils::kopToRub($money) : '') . '</td></tr>';
                $tbody .= $tr;
            }
        }

        //как то коряво считается баланс на конец дня, поэтому берем начальный баланс и отнимаем прибавляем ордеры за день
        $endbalance = $reportStartBalance + $income - $outcome;
        $html = str_replace('{{dailycashreport.data}}', $tbody, $html);
        $html = str_replace('{{dailycashreport.income}}', StrUtils::kopToRub($income), $html);
        $html = str_replace('{{dailycashreport.outcome}}', StrUtils::kopToRub($outcome), $html);
        $html = str_replace('{{dailycashreport.start_balance}}', StrUtils::kopToRub($reportStartBalance), $html);
        if($report_id==60553){
            $endbalance = 0;
        }
        $html = str_replace('{{dailycashreport.end_balance}}', StrUtils::kopToRub($endbalance), $html);
        $html = str_replace('{{dailycashreport.salary}}', StrUtils::kopToRub($salary), $html);
        $html = str_replace('{{dailycashreport.created_at}}', $reportCreatedAt, $html);
//        $html = str_replace('{{dailycashreport.number}}', $report->id, $html);
        $html = str_replace('{{dailycashreport.number}}', '___________', $html);
        $html = str_replace('{{dailycashreport.income_docs_num}}', StrUtils::num2str($income_docs_num), $html);
        $html = str_replace('{{dailycashreport.outcome_docs_num}}', StrUtils::num2str($outcome_docs_num), $html);
        $html = str_replace('<tr><td></td></tr>', '', $html);
        return \App\Utils\PdfUtil::getPdf($html);
    }

    public function createPdf($report_id = null, $dateFrom = null, $dateTo = null, $subdivision_id = null) {
        return $this->createPdf3($report_id, $dateFrom, $dateTo, $subdivision_id);
    }

    public function createCashbookByDailyReport($report_id) {
        return $this->createPdf($report_id);
    }

    public function cashbook() {
        return view('reports.cashbook');
    }

    public function createCashbook(Request $req) {
        if ($req->has('dateFrom') && $req->has('dateTo')) {
            return $this->createPdf(null, with(new Carbon($req->dateFrom))->format('Y-m-d H:i:s'), with(new Carbon($req->dateTo))->format('Y-m-d H:i:s'), $req->get('subdivisionId', null));
        }
    }

    public function getCashbookOrders($dateFrom, $dateTo, $subdivisionID) {
        $cardTypeID = \App\OrderType::getCARDid();
        return Order::whereBetween('created_at', [
                            with(new Carbon($dateFrom))->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                            with(new Carbon($dateTo))->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                        ])
                        ->where('type', '<>', $cardTypeID)
                        ->where('subdivision_id', $subdivisionID)
                        ->get();
    }

    public function syncCashbook(Request $req) {
        if (!$req->has('date') || !$req->has('subdivision_id')) {
            return 0;
        }
        $date = new Carbon($req->date);
        $subdiv = \App\Subdivision::find($req->subdivision_id);
        if (is_null($subdiv)) {
            return 0;
        }
        $this->cashbookSyncer($date, $subdiv);
    }

    public function cashbookSyncer($date, $subdiv, $res1c = null) {
        \PC::debug('cashbook syncer here');
        if (is_null($subdiv)) {
            \PC::debug('no subdiv', $subdiv);
            return 0;
        }
        if (is_null($res1c)) {
            $res1c = MySoap::getCashbookBalance($date->format('Ymd'), $subdiv->name_id);
        }
        \PC::debug($res1c);
        if (!$res1c['res']) {
            \PC::debug($res1c, 'ошибка');
            return 0;
        }
        $balanceStart = $this->getCashbookDayBalance($subdiv->id, $date->format('Y-m-d H:i:s'));
        \PC::debug($balanceStart, 'balanceStart');

        $orderStart = new Order();
        $orderEnd = new Order();

        DB::beginTransaction();
        if ($balanceStart['start'] != $res1c['cashStart'] * 100) {
            $diffStart = round($res1c['cashStart'] * 100 - $balanceStart['start']);
            \PC::debug($diffStart, 'start diff');
            if ($diffStart != 0) {
                $orderStart->created_at = with(new Carbon($date))->subDay();
                $orderStart->money = abs($diffStart);
                $orderStart->type = ($diffStart < 0) ? OrderType::getRKOid() : OrderType::getPKOid();
                $orderStart->reason = 'синхронизация с 1с (пришло:' . json_encode($res1c) . ', было: start=' . $balanceStart['start'] . ',end=' . $balanceStart['end'] . ')';
                $orderStart->user_id = Auth::user()->id;
                $orderStart->subdivision_id = $subdiv->id;
                $orderStart->purpose = Order::P_OD;
                if (!$orderStart->save()) {
                    DB::rollback();
                    Log::error('dailycashreportcontroller.synccashbook1', ['order' => $orderStart, 'res1c' => $res1c, 'balance' => $balanceStart]);
                    return 0;
                }
                Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_ORDERS, $orderStart);
                \PC::debug($orderStart->toArray(), 'order start');
            }
        }
        $balanceEnd = $this->getCashbookDayBalance($subdiv->id, $date->format('Y-m-d H:i:s'));
        \PC::debug($balanceStart, 'balanceEnd');
        if ($balanceEnd['end'] != $res1c['cashEnd'] * 100) {
            $diffEnd = round($res1c['cashEnd'] * 100 - $balanceEnd['end']);
            \PC::debug($diffEnd, 'end');
            if ($diffEnd != 0) {
                $orderEnd->created_at = with(new Carbon($date))->setTime(12, 0, 0);
                $orderEnd->money = abs($diffEnd);
                $orderEnd->type = ($diffEnd < 0) ? OrderType::getRKOid() : OrderType::getPKOid();
                $orderEnd->reason = 'синхронизация с 1с (пришло:' . json_encode($res1c) . ', было: start=' . $balanceEnd['start'] . ',end=' . $balanceEnd['end'] . ')';
                $orderEnd->user_id = Auth::user()->id;
                $orderEnd->subdivision_id = $subdiv->id;
                $orderEnd->purpose = Order::P_OD;
                if (!$orderEnd->save()) {
                    DB::rollback();
                    Log::error('dailycashreportcontroller.synccashbook2', ['order' => $orderEnd, 'res1c' => $res1c, 'balance' => $balanceEnd]);
                    return 0;
                }
                Spylog::logModelAction(Spylog::ACTION_CREATE, Spylog::TABLE_ORDERS, $orderEnd);
                \PC::debug($orderEnd->toArray(), 'order end');
            }
        }
        Spylog::log(Spylog::ACTION_SYNC_CASHBOOK, null, null, json_encode(['orderStart' => $orderStart, 'orderEnd' => $orderEnd, 'res1c' => $res1c, 'balanceEnd' => $balanceEnd, 'balanceStart' => $balanceStart]));
        DB::commit();
        return 1;
    }

    public function getCashbookBalanceFrom1c($date, $subdiv) {
        $res1c = MySoap::getCashbookBalance($date->format('Ymd'), (is_string($subdiv)) ? $subdiv : $subdiv->name_id);
        if (!$res1c['res']) {
            \PC::debug($res1c, 'ошибка');
            return null;
        }
        return $res1c;
    }

    public function syncCashbookFromDailyCash(Request $req) {
        if ($this->syncCashbook($req)) {
            return redirect()->back()->with('msg_suc', StrLib::SUC);
        } else {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
    }

    public function remove(Request $req) {
        if (!$req->has('id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $drep = DailyCashReport::find($req->id);
        if (is_null($drep)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        Spylog::logModelAction(Spylog::ACTION_DELETE, Spylog::TABLE_DAILY_CASH_REPORTS, $drep);
        if ($drep->delete()) {
            return redirect()->back()->with('msg_suc', StrLib::SUC);
        } else {
            return redirect()->back()->with('msg_err', StrLib::ERR_CANT_DELETE);
        }
    }

    public function calcDailyCashData($json_str) {
//        $data = json_decode($json_str, true);
//        $balance = ['start' => $report->start_balance, 'end' => $report->end_balance];
        $plus = 0;
        $minus = 0;
        $from = 0;
        $to = 0;

        try {
            $data = json_decode($json_str, true);
        } catch (Exception $exc) {
            $data = null;
        }
        if (is_array($data)) {
            foreach ($data as $row) {
                if (array_key_exists('money', $row) && array_key_exists('action', $row)) {
                    $rm = (is_array($row['money'])) ? $row['money'][0] : $row['money'];
                    $rm = number_format((float) str_replace(',', '.', $rm), 2, '.', '');
                    if ($row['action'] == '0') {
                        $plus += $rm;
                    } else if (in_array($row['action'], ['1', '5'])) {
                        $minus += $rm;
                    } else if ($row['action'] == '3') {
                        $from += $rm;
                    } else if ($row['action'] == '4') {
                        $to += $rm;
                    }
                }
            }
            return ['plus' => $plus, 'minus' => $minus, 'to' => $to, 'from' => $from];
        } else {
            return ['plus' => 0, 'minus' => 0, 'to' => 0, 'from' => 0];
        }
    }

    /**
     * переключить возможность редактирования отчета за предыдущий день
     * @param Request $req
     * @return type
     */
    public function toggleReportEditable(Request $req) {
        if (!$req->has('id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $report = DailyCashReport::find($req->get('id'));
        if (is_null($report)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        $report->edit_enabled = ($report->edit_enabled) ? 0 : 1;
        $report->save();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    public function summaryReport(Request $req) {
        if (!$req->has('date')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }

        $contract = ContractForm::where('text_id', 'dailycashsummary')->first();
        if (is_null($contract)) {
            return redirect()->back()->with('msg_err', 'Ошибка! Форма не найдена!');
        }
        $html = $contract->template;

        $tbody = '';
        $cols = [
            'subdivisions.name as subdiv_name', 'subdivisions.name_id as subdiv_name_id', 'subdivisions.id as subdiv_id',
            'subdivisions.created_at as subdiv_created_at',
            'regions.name as region_name'
        ];
        $regionsNum = \App\Region::count();
        $reports = Subdivision::select($cols)
                ->whereNotNull('subdivisions.city_id')
                ->whereNotNull('subdivisions.name')
                ->where('subdivisions.closed', '0')
                ->where('subdivisions.is_terminal', '0')
                ->leftJoin('cities', 'subdivisions.city_id', '=', 'cities.id')
                ->leftJoin('regions', 'cities.region_id', '=', 'regions.id')
                ->orderBy('regions.name')
                ->orderBy('subdivisions.name')
                ->get();
        $i = 0;
        $totalStart = 0;
        $totalEnd = 0;
        $totalPlus = 0;
        $totalMinus = 0;
        $totalTo = 0;
        $totalFrom = 0;
        $totalLimit = 0;
        $regionName = '';
        $prevReqDate0 = with(new Carbon($req->date))->subDay()->setTime(0, 0, 0);
        $reqDate0 = with(new Carbon($req->date))->setTime(0, 0, 0);
        $reqDate24 = with(new Carbon($req->date))->setTime(23, 59, 59);
        foreach ($reports as $report) {
            if ($report->subdiv_id == 113) {
                continue;
            }
            if (DailyCashReport::where('subdivision_id', $report->subdiv_id)->where('created_at', '<', $reqDate24)->count() == 0) {
                continue;
            }
            $i++;
            //отчет за предыдуший день
            $prev_dcr = DailyCashReport::where('subdivision_id', $report->subdiv_id)
                    ->select(['data', 'start_balance', 'end_balance'])
                    ->orderBy('created_at', 'desc')
                    ->where('created_at', '>', $prevReqDate0)
                    ->where('created_at', '<', $reqDate0)
                    ->first();
            //отчет за необходимый день
            $dcr = DailyCashReport::where('subdivision_id', $report->subdiv_id)
                    ->select(['data', 'start_balance', 'end_balance','matches'])
                    ->whereBetween('created_at', [$reqDate0, $reqDate24])
                    ->first();
            //отчет за следующий день
            $next_dcr = DailyCashReport::where('subdivision_id', $report->subdiv_id)
                    ->select(['data', 'start_balance', 'end_balance'])
                    ->where('created_at', '>', $reqDate24)
                    ->first();
            if (is_null($dcr)) {
                //последний отчет до текущего дня на случай если на текущий день отчет не найден
                $last_dcr = DailyCashReport::where('subdivision_id', $report->subdiv_id)
                        ->select(['start_balance', 'end_balance'])
                        ->orderBy('created_at', 'desc')
                        ->where('created_at', '<=', $reqDate0)
                        ->first();
            }
            //вставляем строку с областью, если область у текущего подразделения отличается от предыдущего
            if ($regionName != $report->region_name) {
                $regionName = $report->region_name;
                $tbody .= '<tr class="purple"><td colspan="2"><h2>' . $regionName . '</h2></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            }

            if (is_null($dcr) || is_null($dcr->start_balance)) {
                if (!isset($last_dcr) || is_null($last_dcr)) {
                    $report->sb = 0;
                } else {
                    $report->sb = $last_dcr->end_balance;
                }
            } else {
                $report->sb = $dcr->start_balance;
            }
            $error_style = '';
            if (is_null($dcr) || is_null($dcr->end_balance)) {
                if (!isset($last_dcr) || is_null($last_dcr)) {
                    $report->eb = 0;
                } else {
                    $report->eb = $last_dcr->end_balance;
                }
            } else {
                if (!is_null($next_dcr) && $next_dcr->start_balance != $dcr->end_balance) {
                    \PC::debug(['sb' => $next_dcr->start_balance, 'eb' => $next_dcr->end_balance], 'next dcr ' . $report->subdiv_name);
                    \PC::debug(['sb' => $dcr->start_balance, 'eb' => $dcr->end_balance], 'dcr ' . $report->subdiv_name);
                    $res1c = $this->getCashbookBalanceFrom1c(new Carbon($req->date), $report->subdiv_name_id);
                    if (!is_null($res1c)) {
                        if ($dcr->end_balance != $res1c['cashEnd'] * 100) {
                            $error_style = 'style="background-color:red;"';
                        }
                        $report->eb = $res1c['cashEnd'] * 100;
                    } else {
                        $report->eb = $dcr->end_balance;
                    }
                } else {
                    $report->eb = $dcr->end_balance;
                }
            }
            $totalStart += number_format($report->sb / 100, 2, '.', '');
            $totalEnd += number_format($report->eb / 100, 2, '.', '');
            if (!is_null($dcr) && !is_null($dcr->data)) {
                $calcData = $this->calcDailyCashData($dcr->data);
            } else {
                $calcData = ['plus' => 0, 'minus' => 0, 'to' => 0, 'from' => 0];
            }
            $totalPlus += $calcData['plus'];
            $totalMinus += $calcData['minus'];
            $totalTo += $calcData['to'];
            $totalFrom += $calcData['from'];
            $calcRes = $report->sb / 100 + $calcData['plus'] - $calcData['to'] - $calcData['minus'] + $calcData['from'];
            /**
             * если расчитанная сумма по отчету не совпадает с той, которая в базе, то подсветить красным
             */
            if (number_format($calcRes, 2, ',', '') != number_format($report->eb / 100, 2, ',', '') || (!is_null($dcr) && is_object($dcr) && $dcr->matches==0)) {
                $error_style = 'style="background-color:red;"';
                \PC::debug(['res' => $calcRes, 'eb' => $report->eb / 100, 'name' => $report->subdiv_name], 'unequal');
            }
            /**
             * если конечная сумма предыдущего отчета не сходится с текущей суммой,
             * то подсветить желтым
             */
            if (isset($prev_dcr) && !is_null($prev_dcr) && $report->sb != $prev_dcr->end_balance) {
                $error_style = 'style="background-color:yellow;"';
            }

            $tbody .= '<tr ' . $error_style . '>';
            $tbody .= '<td>' . $report->subdiv_name . '</td>';
            $tbody .= '<td>' . $report->subdiv_name_id . '</td>';
            $tbody .= '<td>' . number_format($report->sb / 100, 2, ',', ' ') . '</td>';
            $tbody .= '<td>' . number_format($calcData['minus'], 2, ',', ' ') . '</td>';
            $tbody .= '<td>' . number_format($calcData['plus'], 2, ',', ' ') . '</td>';
            $tbody .= '<td>' . (($calcData['to'] == 0) ? '' : number_format($calcData['to'], 2, ',', ' ')) . '</td>';
            $tbody .= '<td>' . (($calcData['from'] == 0) ? '' : number_format($calcData['from'], 2, ',', ' ')) . '</td>';
            $tbody .= '<td>' . number_format($report->eb / 100, 2, ',', ' ') . '</td>';
            $tbody .= '<td>' . '</td>';
            $tbody .= '<td>' . $i . '</td>';
            $tbody .= '</tr>';
        }
        $lastRowNum = $regionsNum + count($reports) + 6;
        $totalStart = number_format($totalStart, 2, '.', ' ');
        $totalEnd = number_format($totalEnd, 2, '.', ' ');
        $totalPlus = number_format($totalPlus, 2, '.', ' ');
        $totalMinus = number_format($totalMinus, 2, '.', ' ');
        $totalTo = number_format($totalTo, 2, '.', ' ');
        $totalFrom = number_format($totalFrom, 2, '.', ' ');
        $totalLimit = number_format($totalLimit, 2, '.', ' ');
        $tbody .= '<tr class="beige">';
        $tbody .= '<td>ОФИС</td><td></td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td></td>';
        $tbody .= '</tr>';
        $tbody .= '<tr class="beige">';
        if (Auth::user()->id == 5) {
            $tbody .= '<td>Итого</td><td></td>';
            $tbody .= '<td>' . $totalStart . '</td>';
            $tbody .= '<td>' . $totalMinus . '</td>';
            $tbody .= '<td>' . $totalPlus . '</td>';
            $tbody .= '<td>' . $totalTo . '</td>';
            $tbody .= '<td>' . $totalFrom . '</td>';
            $tbody .= '<td>' . $totalEnd . '</td>';
            $tbody .= '<td>' . $totalLimit . '</td>';
            $tbody .= '<td></td>';
        } else {
            $tbody .= '<td>Итого</td><td></td>';
            $tbody .= '<td>=СУММ(C8:C' . $lastRowNum . ')</td>';
            $tbody .= '<td>=СУММ(D8:D' . $lastRowNum . ')</td>';
            $tbody .= '<td>=СУММ(E8:E' . $lastRowNum . ')</td>';
            $tbody .= '<td>=СУММ(F8:F' . $lastRowNum . ')</td>';
            $tbody .= '<td>=СУММ(G8:G' . $lastRowNum . ')</td>';
            $tbody .= '<td>=СУММ(H8:H' . $lastRowNum . ')</td>';
            $tbody .= '<td>' . $totalLimit . '</td>';
            $tbody .= '<td></td>';
        }
        $tbody .= '</tr>';
        $tbody .= '<tr>';
        $tbody .= '<td>СЭБ СУЗ</td><td></td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td></td>';
        $tbody .= '</tr>';
        $tbody .= '<tr>';
        $tbody .= '<td>Комиссия Банка</td><td></td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td></td>';
        $tbody .= '</tr>';
        $tbody .= '<tr>';
        $tbody .= '<td></td><td></td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td></td>';
        $tbody .= '</tr>';
        $tbody .= '<tr>';
        $tbody .= '<td>Офис-КО</td><td></td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td>' . '</td>';
        $tbody .= '<td></td>';
        $tbody .= '</tr>';
        $html = str_replace('{{date}}', with(new Carbon($req->date))->format('d.m.Y'), $html);
        $html = str_replace('<tr>
<td>{{rows}}</td>
</tr>', $tbody, $html);
        if (Auth::user()->id == 5) {
            return $html;
        }
        $file = "report.xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
                        ->header("Content-type", "application/vnd.ms-excel")
                        ->header("Content-Disposition", "attachment; filename=$file");
    }

}
