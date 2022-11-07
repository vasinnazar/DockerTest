<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Carbon\Carbon,
    App\Customer,
    App\CardChange,
    Illuminate\Support\Facades\Validator,
    Illuminate\Support\Facades\DB,
    Auth,
    App\Utils\HtmlHelper,
    App\Utils\StrLib,
    App\ContractVersion,
    App\ContractForm,
    App\Card;
/**
 * Редактор версий договоров для форм договоров.
 */
class ContractVersionsController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
        $this->table = 'contract_versions';
        $this->model = new ContractVersion();
    }
/**
 * открывает редактор форм
 * @param Request $req
 * @return type
 */
    public function versionsEditor(Request $req) {
        if (!$req->has('contract_id')) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $contract = \App\ContractForm::find($req->contract_id);
        $versions = ContractVersion::where('contract_form_id', $req->contract_id)->get();
        return view('contracteditor.versions_editor', ['versions' => $versions, 'contract' => $contract, 'contractslist'=>  ContractForm::orderBy('updated_at','desc')->lists('name','id')]);
    }
/**
 * Добавляет версию к форме договора
 * @param Request $req
 * @return type
 */
    public function addVersion(Request $req) {
        if(!$req->has('contract_form_id')){
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        if(!is_null(ContractForm::find($req->contract_form_id))){
            $version = new ContractVersion();
            $version->date = Carbon::now()->format('Y-m-d H:i:s');
            $version->contract_form_id = $req->contract_form_id;
            $version->save();
            return $this->backWithSuc();
        } else {
            return $this->backWithErr();
        }
    }
/**
 * Обновление версии
 * @param Request $req
 * @return type
 */
    public function updateVersion(Request $req) {
        if(!$req->has('id')){
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $ver = ContractVersion::find($req->id);
        if(is_null($ver)){
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $ver->update($req->all());
        return $this->backWithSuc();
    }

}
