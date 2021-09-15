<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\WorkTime,
    yajra\Datatables\Datatables,
    App\Utils\StrLib,
    Auth,
    App\Utils\HtmlHelper,
    App\MySoap,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    App\Subdivision,
    Carbon\Carbon;

class WorkTimeController extends BasicController {

    public function __construct() {
        $this->table = 'work_times';
        $this->model = new WorkTime();
    }

    public function getTableView(Request $req) {
        return parent::getTableView($req);
    }

    public function editItem(Request $req) {
//        $res = parent::editItem($req);
        $item = $this->getItemByID($req);
        if (is_null($item)) {
            $item = $this->model;
        }
        return view($this->table . '.edit')->with('item', $item);
    }

    public function getList(Request $req) {
        return parent::getList($req);
    }

    public function removeItem(Request $req) {
        return parent::removeItem($req);
    }

    public function getTodayItem() {
        if (is_null(Auth::user())) {
            return null;
        }
        $item = WorkTime::whereBetween('created_at', [Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'), Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')])
                        ->where('user_id', Auth::user()->id)->first();
        if (!is_null($item)) {
            $item->date_start = with(new Carbon($item->date_start))->format('d.m.Y H:i:s');
            $item->date_end = with(new Carbon($item->date_end))->format('d.m.Y H:i:s');
        }
        return $item;
    }

    public function updateItem(Request $req) {
        parent::updateItem($req);
        $worktime = WorkTime::findOrNew($req->get('id', NULL));
        $worktime->fill($req->all());
        if (is_null($worktime->date_start)) {
            $worktime->date_start = Carbon::now()->format('Y-m-d H:i:s');
        }
        if ($req->has('logout') && $req->logout) {
            $worktime->date_end = Carbon::now()->format('Y-m-d H:i:s');
        }
        DB::beginTransaction();
        $user = Auth::user();
        if(is_null($user)){
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_USER);
        }
        if (is_null($user->subdivision)) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_SUBDIV);
        }
        if ($req->has('subdivision_name_id') && $req->subdivision_name_id != $user->subdivision->name_id) {
            $subdiv = Subdivision::where('name_id', $req->subdivision_name_id)->first();
            if (is_null($subdiv)) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_NO_SUBDIV);
            }
            Spylog::log(Spylog::ACTION_SUBDIV_CHANGE, 'users', $user->id, ($user->subdivision_id . '->' . $subdiv->id));
            $user->subdivision_id = $subdiv->id;
        } else {
            $subdiv = $user->subdivision;
        }
        if($req->has('birth_date')){
            $user->birth_date = with(new Carbon($req->birth_date))->format('Y-m-d');
            $user->save();
        }
        $worktime->user_id = $user->id;
        $worktime->subdivision_id = $user->subdivision_id;
        $res1c = MySoap::saveWorkTime([
                    'id_1c' => ((is_null($worktime->id_1c)) ? "" : $worktime->id_1c),
                    'created_at' => (is_null($worktime->created_at)) ? Carbon::now()->format("Ymd") : $worktime->created_at->format("Ymd"),
                    'user_id_1c' => $user->id_1c,
                    'date_start' => (is_null($worktime->date_start)) ? Carbon::now()->format('YmdHis') : (with(new Carbon($worktime->date_start))->format('YmdHis')),
                    'date_end' => (is_null($worktime->date_end)) ? "00010101" : with(new Carbon($worktime->date_end))->format('YmdHis'),
                    'subdivision_id_1c' => (!is_null($subdiv) && !is_null($subdiv->name_id)) ? $subdiv->name_id : "",
                    'comment' => (is_null($worktime->comment)) ? "" : $worktime->comment,
                    'evaluation' => $worktime->evaluation,
                    'review' => (is_null($worktime->review)) ? "" : $worktime->review,
                    'reason' => (is_null($worktime->reason)) ? "" : $this->JSONtoXML(json_decode($worktime->reason, true), (new Carbon($req->date_start))),
                    'birth_date'=>(is_null($user->birth_date))?'':with(new Carbon($user->birth_date))->format('Ymd')
        ]);
        if (!$res1c['res']) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        $worktime->id_1c = $res1c['value'];
        if (!$worktime->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        if (!$user->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }

        if (!is_null($worktime->date_end)) {
            DB::commit();
            return redirect('/auth/logout');
        } else {
            DB::commit();
            return redirect()->back()->with('msg_suc', StrLib::SUC_SAVED);
        }
    }

    public function JSONtoXML($json, $date = null) {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        if (is_null($date)) {
            $date = Carbon::now();
        }
        if (is_array($json)) {
            foreach ($json as $item) {
                $xml_item = $xml->addChild('item');
                $xml_item->addAttribute('date_start', $date->format('Ymd') . (substr($item["absent_start"], 0, strpos($item["absent_start"], ':'))) . (substr($item["absent_start"], strpos($item["absent_start"], ':') + 1)) . '00');
                $xml_item->addAttribute('date_end', $date->format('Ymd') . (substr($item["absent_end"], 0, strpos($item["absent_end"], ':'))) . (substr($item["absent_end"], strpos($item["absent_end"], ':') + 1)) . '00');
                $xml_item->addAttribute('reason', $item["absent_reason"]);
                $xml_item->addAttribute('time', with(new Carbon($item["absent_start"]))->diffInMinutes(new Carbon($item["absent_end"])));
            }
        }
        return $xml->asXML();
    }

}
