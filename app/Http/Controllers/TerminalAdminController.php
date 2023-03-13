<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Terminal;
use App\TerminalAction;
use yajra\Datatables\Facades\Datatables;
use Form;
use App\Utils\StrLib;
use App\User;
use App\Utils\HtmlHelper;
use App\TerminalCommand;
use App\Http\Controllers\TerminalController;

class TerminalAdminController extends Controller {

    public function __construct() {
        
    }

    public function index() {
        return view('adminpanel.terminals');
    }

    public function getTerminalsList(Request $req)
    {
        $cols = [
            'id',
            'description as name',
            'is_locked',
            'bill_count as billcount',
            'bill_cash as billcash',
            'dispenser_count as dispcount',
            'DispenserStatus',
            'stWebcamStatus',
            'stValidatorStatus',
            'stPrinterStatus',
            'stScannerStatus',
            'last_status'
        ];
        $items = Terminal::select($cols);
        return Datatables::of($items)
            ->editColumn('name', function ($item) {
                return ($item->closed) ? ('<s>' . $item->name . '</s>') : $item->name;
            })
            ->addColumn('status', function ($item) {
                $html = '<div id="terminalStatusHolder' . $item->id . '">';
                $con_status = (Carbon::now()->diffInMinutes(new Carbon($item->last_status)) > TerminalController::MINS_TO_ALERT) ? 'label-danger' : 'label-success';
                $con_time = (is_null($item->last_status)) ? ('-') : (with(new Carbon($item->last_status))->format('d.m.y H:i'));
                $html .= '<span class="label ' . $con_status . ' status_connection">' . $con_time . '</span>';
                $html .= ' <span title="' . $item->stWebcamStatus . '" class="label ' . (($item->stWebcamStatus == '') ? 'label-success' : 'label-danger') . ' status_camera">Камера</span>';
                $html .= ' <span title="' . $item->stPrinterStatus . '" class="label ' . (($item->stPrinterStatus == '') ? 'label-success' : 'label-danger') . ' status_printer">Принтер</span>';
                $html .= ' <span title="' . $item->stScannerStatus . '" class="label ' . (($item->stScannerStatus == '') ? 'label-success' : 'label-danger') . ' status_scanner">Сканер</span>';
                $html .= ' <span class="label ' . ((bool)(str_replace(',', '',
                        $item->DispenserStatus)) ? 'label-danger' : 'label-success') . ' status_dispenser">Диспенсер</span>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('dispenser_cash', function ($item) {
                return ($item->dispcount * 1000) . ' руб.';
            })
            ->addColumn('dispenser_count', function ($item) {
                return $item->dispcount . ' шт.';
            })
            ->addColumn('bill_cash', function ($item) {
                return ($item->billcash / 100) . ' руб.';
            })
            ->addColumn('lock_status', function ($item) {
                return '<input' . (($item->is_locked) ? ' checked' : '')
                    . ' class="checkbox" id="is_locked' . $item->id . '" name="is_locked" type="checkbox" value="'
                    . $item->is_locked . '"><label for="is_locked' . $item->id . '" onclick="$.terminalsCtrl.changeLockStatus('
                    . $item->id . ');" ></label>';
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group btn-group-sm">';
                $html .= '<button class="btn btn-default" onclick="$.terminalsCtrl.openIncassModal(' . $item->id . '); return false;">Инкассация</button>';
                $html .= '<button class="btn btn-default" onclick="$.terminalsCtrl.openAddCashModal(' . $item->id . '); return false;">Пополнить</button>';
                $html .= '<button class="btn btn-default" onclick="$.terminalsCtrl.openAddCommandModal(' . $item->id . '); return false;">Команда</button>';
                $html .= HtmlHelper::Buttton(url('adminpanel/terminals/edit?id=' . $item->id), ['glyph' => 'pencil']);
                $html .= HtmlHelper::Buttton(url('adminpanel/terminals/remove?id=' . $item->id), ['glyph' => 'remove']);
                $html .= '</div>';
                return $html;
            })
            ->removeColumn('id')
            ->removeColumn('last_status')
            ->removeColumn('is_locked')
            ->removeColumn('billcount')
            ->removeColumn('billcash')
            ->removeColumn('dispcount')
            ->removeColumn('DispenserStatus')
            ->removeColumn('stWebcamStatus')
            ->removeColumn('stValidatorStatus')
            ->removeColumn('stPrinterStatus')
            ->removeColumn('stScannerStatus')
            ->filter(function ($query) use ($req) {
                if ($req->has('description')) {
                    $query->where('description', 'like', "%" . $req->get('description') . "%");
                }
            })
            ->setTotalRecords(1000)
            ->make();
    }

    public function addCash(Request $req) {
        if (!$req->has('id') || !$req->has('dispenser_count') || !$req->has('dispenser_cash')) {
            return redirect()->back()->with('msg', 'Ошибка! Переданы не все параметры')->with('class', 'alert-danger');
        }
        $terminal = Terminal::find($req->id);
        if (is_null($terminal)) {
            return redirect()->back()->with('msg', 'Терминал не найден')->with('class', 'alert-danger');
        }
        if ($req->dispenser_count * 1000 != $req->dispenser_cash) {
            return redirect()->back()->with('msg', 'Количество купюр не сходится с введенной суммой')->with('class', 'alert-danger');
        }
        $terminal->dispenser_count += $req->dispenser_count;
        if ($terminal->save()) {
            return redirect()->back()->with('msg', 'Сохранено')->with('class', 'alert-success');
        } else {
            return redirect()->back()->with('msg', 'Ошибка! Баланс не пополнен')->with('class', 'alert-danger');
        }
    }

    public function incass(Request $req) {
        if (!$req->has('id') || !$req->has('bill_cash') || !$req->has('bill_count')) {
            return redirect()->back()->with('msg', 'Ошибка! Переданы не все параметры')->with('class', 'alert-danger');
        }
        $terminal = Terminal::find($req->id);
        if (is_null($terminal)) {
            return redirect()->back()->with('msg', 'Терминал не найден')->with('class', 'alert-danger');
        }
        $terminal->bill_cash -= $req->bill_cash * 100;
        $terminal->bill_count -= $req->bill_count;
        if ($req->has('dispenser_count')) {
            $terminal->dispenser_count -= $req->dispenser_count;
        }
        if ($terminal->save()) {
            return redirect()->back()->with('msg', 'Сохранено')->with('class', 'alert-success');
        } else {
            return redirect()->back()->with('msg', 'Ошибка! Средства не сняты')->with('class', 'alert-danger');
        }
    }

    public function changeLockStatus(Request $req) {
        if (!$req->has('id') || !$req->has('is_locked')) {
            return redirect()->back()->with('msg', 'Ошибка! Переданы не все параметры')->with('class', 'alert-danger');
        }
        $terminal = Terminal::find($req->id);
        if (is_null($terminal)) {
            return redirect()->back()->with('msg', 'Терминал не найден')->with('class', 'alert-danger');
        }
        $terminal->is_locked = $req->is_locked;
        return (int) $terminal->save();
    }

    public function refreshStatus(Request $req) {
        return ['camera' => rand(0, 1), 'printer' => rand(0, 1), 'connection' => rand(0, 1), 'dispenser' => rand(0, 1), 'validator' => rand(0, 1)];
    }

    public function update(Request $req) {
        $terminal = Terminal::findOrNew($req->get('id', null));
        $terminal->fill($req->all());
        if (!$req->has('HardwareID') || $req->HardwareID == '') {
            $terminal->HardwareID = NULL;
        }
        if ($req->has('id') && $req->id != '' && $req->id != $terminal->id) {
            $terminal->id = $req->id;
        }
        if (!$terminal->save()) {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
        $terminal->pay_point_id = $terminal->id;
        if (!$terminal->save()) {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
        return redirect('adminpanel/terminals')->with('msg_suc', StrLib::$SUC_SAVED);
    }

    public function editor(Request $req) {
        if ($req->has('id')) {
            $terminal = Terminal::find($req->id);
            if (is_null($terminal)) {
                return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
            }
        } else {
            $terminal = new Terminal();
        }
        return view('adminpanel.terminal_edit')->with('item', $terminal)->with('users', User::orderBy('name', 'asc')->pluck('name', 'id'));
    }

    public function remove(Request $req) {
        if ($req->has('id')) {
            $terminal = Terminal::find($req->id);
            if (is_null($terminal)) {
                return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
            }
            if ($terminal->delete()) {
                return redirect()->back()->with('msg_suc', StrLib::$SUC);
            } else {
                return redirect()->back()->with('msg_err', StrLib::$ERR);
            }
        } else {
            return redirect()->back()->with('msg_err', StrLib::$ERR_NO_PARAMS);
        }
    }

    public function addCommand(Request $req) {
        if (!$req->has('point_id') && !$req->has('name')) {
            return redirect()->back()->with('msg_err', StrLib::$ERR_NO_PARAMS);
        }
        $cmd = new TerminalCommand();
        $cmd->PayPointID = $req->point_id;
        $cmd->name = $req->name;
        if ($req->has('params')) {
            $cmd->params = $req->params;
        }
        $cmd->save();
        return redirect()->back()->with('msg_suc', StrLib::$SUC);
    }
    
    public function viewCommandsList(Request $req){
        
    }    

}
