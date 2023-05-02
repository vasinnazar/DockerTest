<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\RepaymentType,
    Input,
    \App\ContractForm;

class RepaymentsEditorController extends Controller {

    public function __construct() {
        
    }

    public function index() {
        return view('adminpanel.repaymentTypes', ['repaymentTypes' => RepaymentType::all()]);
    }

    public function editor($id = null) {
        $rType = RepaymentType::findOrNew($id);
        return view('adminpanel.repaymentTypeEditor', ['repaymentType' => $rType, 'contractForms' => ContractForm::where('text_id', config('options.repayment'))->pluck('name', 'id')]);
    }

    public function update() {
        $rType = RepaymentType::findOrNew(Input::get('id'));
        $rType->fill(Input::all());
        if(!Input::has('add_after_freeze')){
            $rType->add_after_freeze = 0;
        }
        if(!Input::has('mandatory_percents')){
            $rType->mandatory_percents = 0;
        }
        $moneyAttrs = ['od_money', 'fine_money', 'percents_money', 'exp_percents_money'];
        foreach ($moneyAttrs as $attr) {
            $rType->setAttribute($attr, \App\StrUtils::parseMoney(Input::get($attr)));
        }
        if ($rType->save()) {
            return redirect('adminpanel/repaymenttypes')->with('msg', 'Сохранено')->with('class', 'alert-success');
        } else {
            return redirect('adminpanel/repaymenttypes')->with('msg', 'Ошибка')->with('class', 'alert-danger');
        }
    }

    public function remove($id) {
        $rType = RepaymentType::find($id);
        if (is_null($rType)) {
            return redirect('adminpanel/repaymenttypes')->with('msg', 'Тип гашения не найден')->with('class', 'alert-danger');
        }
        try {
            if ($rType->delete()) {
                return redirect('adminpanel/repaymenttypes')->with('msg', 'Удалено')->with('class', 'alert-success');
            } else {
                return redirect('adminpanel/repaymenttypes')->with('msg', 'Ошибка. Тип гашения не был удален')->with('class', 'alert-danger');
            }
        } catch (Exception $exc) {
            return redirect('adminpanel/repaymenttypes')->with('msg', 'Исключение. Тип гашения не был удален')->with('class', 'alert-danger');
        }
    }

}
