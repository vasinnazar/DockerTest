<?php

namespace App\Http\Controllers;

use Input,
    Auth,
    Validator,
    App\Condition,
    App\Spylog\Spylog;

/**
 * условия для типов кредитников. 
 */
class ConditionsController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        return view('conditions.list')->with('conditions', Condition::all());
    }
    /**
     * открывает форму создания\редактирования условия
     * @param int $condition_id идентификатор условия, если нуль - то новое
     * @return type
     */
    public function editor($condition_id = null) {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        Spylog::log(Spylog::ACTION_OPEN, 'conditions', $condition_id);
        $condition = Condition::findOrNew($condition_id);
        return view('conditions.editor', ['condition' => $condition]);
    }
    /**
     * создает или обновляет условие в базе
     * @return type
     */
    public function update() {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        if (!is_null(Input::get('id')) && Input::get('id') != '') {
            $cond = Condition::findOrNew(Input::get('id'));
            Spylog::logModelChange('conditions', $cond, Input::all());
            $cond->fill(Input::all());
            $cond->save();
        } else {
            $cond = Condition::create(Input::all());
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'conditions', $cond);
        }
        return redirect('loantypes/list')->with('msg', 'Условие сохранено')->with('class', 'alert-success');
    }
    /**
     * удаляет условие
     * @param int $condition_id идентификатор условия
     * @return type
     */
    public function delete($condition_id) {
        if (!Auth::user()->isAdmin()) {
            return redirect('home')->with('msg', 'Нет доступа!')->with('class', 'alert-danger');
        }
        $condition = Condition::find($condition_id);
        if ($condition->delete()) {
            Spylog::logModelAction(Spylog::ACTION_DELETE, 'conditions', $condition);
            return redirect()->route('loantypes.list')
                            ->with('msg', 'Условие удалено')
                            ->with('class', 'alert-success');
        } else {
            return redirect()->route('loantypes.list')
                            ->with('msg', 'Ошибка! Условие не удалено')
                            ->with('class', 'alert-danger');
        }
    }

}
