<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Illuminate\Support\Facades\DB,
    App\Utils\StrLib,
    App\RemoveRequest,
    Auth,
    Log,
    App\Spylog\Spylog,
    Carbon\Carbon;

class BasicController extends Controller {

    public $table;
    public $model;
    public $mysoapItemID;
    public $useDatatables = true;
    public $defPaginate = 25;

    public function __construct() {
        
    }

    public function getTableView(Request $req) {
        if ($this->useDatatables) {
            return view($this->table . '.table');
        } else {
            return view($this->table . '.table')->with('items', $this->model->getModel()->paginate($this->defPaginate));
        }
    }

    public function getList(Request $req) {
        
    }
    /**
     * Добавляет к запросу условия поиска
     * @param type $query либо Eloquent либо DB
     * @param array $input массив данных с формы поиска
     */
    public function addSearchConditionsToQuery($query,$input){
        foreach ($input as $k => $v) {
            if (strpos($k, 'search_field_') === 0 && strpos($k, '_condition') === FALSE && !empty($v)) {
                $fieldName = str_replace('search_field_', '', $k);
                $tableName = substr($fieldName, 0, strpos($fieldName, '@'));
                $colName = substr($fieldName, strlen($tableName) + 1);
                $condColName = $k . '_condition';
                $condition = (array_key_exists($condColName, $input)) ? $input[$condColName] : '=';
                if($condition=='like'){
                    $v = '%'.$v.'%';
                }
                $query->where($tableName . '.' . $colName, $condition, $v);
            }
        }
    }

    public function removeItem(Request $req) {
        if ($req->has('id')) {
            $item = $this->model->getModel()->find($req->id);
            if (is_null($item)) {
                Log::error('BasicController.removeItem не найден', ['item' => $item, 'req' => $req->all()]);
                return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
            }
            if ($item->delete()) {
                $remreq = RemoveRequest::where('doc_id', $item->id)->where('doc_type', $this->mysoapItemID)->first();
                if (!is_null($remreq)) {
                    $remreq->update(['status' => RemoveRequest::STATUS_DONE, 'user_id' => Auth::user()->id]);
                }
                Spylog::logModelAction(Spylog::ACTION_DELETE, $this->table, $item);
                return redirect()->back()->with('msg_suc', StrLib::$SUC);
            } else {
                Log::error('BasicController.removeItem не удалился', ['item' => $item, 'req' => $req->all()]);
                return redirect()->back()->with('msg_err', StrLib::$ERR);
            }
        } else {
            Log::error('BasicController.removeItem не все параметры', ['req' => $req->all()]);
            return redirect()->back()->with('msg_err', StrLib::$ERR_NO_PARAMS);
        }
    }

    public function editItem(Request $req) {
        $res = view($this->table . '.edit');
        if ($req->has('id')) {
            $item = $this->model->getModel()->find($req->id);
            if (!is_null($item)) {
                $res->with('item', $item);
            } else {
                return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
            }
        } else {
            $res->with('item', $this->model);
        }
        return $res;
    }

    public function getItemByID(Request $req) {
        if ($req->has('id')) {
            return $this->model->getModel()->find($req->id);
        } else {
            return null;
        }
    }

    public function updateItem(Request $req) {
        
    }

    public function backWithSuc($msg = null) {
        return redirect()->back()->with('msg_suc', (is_null($msg)) ? StrLib::SUC : $msg);
    }

    public function backWithErr($msg = null) {
        return redirect()->back()->with('msg_err', (is_null($msg)) ? StrLib::ERR : $msg);
    }

    public function ajaxResult($result = 0, $msg = null, $data = null) {
        $res = ['result' => intval($result)];
        if (!is_null($msg)) {
            $res['msg'] = $msg;
        }
        if (!is_null($data) && is_array($data)) {
            $res = array_merge($res, $data);
        }
        return $res;
    }

}
