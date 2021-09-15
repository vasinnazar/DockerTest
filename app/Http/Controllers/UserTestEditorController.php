<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\UserTest;
use App\UserTestAnswer;
use App\UserTestQuestion;
use Illuminate\Support\Facades\DB;
use App\Utils\StrLib;

class UserTestEditorController extends BasicController {

    /**
     * 
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        if (config('app.version_type') == 'debtors') {
            $currentUser = auth()->user();
            if (!$currentUser->hasRole('debtors_chief')) {
                return $this->backWithErr('Недостаточно прав.');
            }
        }
        
        $data = [
            'usertests' => UserTest::all()
        ];
        return (config('app.version_type') == 'sales') ? view('usertests.editor.index', $data) : view('usertests.editor.index_debtors', $data);
    }

    /**
     * Страница создания теста
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $test = new UserTest();
        $data = [
            'usertest' => $test,
            'json' => $test->generateJson()
        ];
        return view('usertests.editor.edit', $data);
    }

    /**
     * Открыть тест на редактирование
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $test = UserTest::find($id);
        if (is_null($test)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $data = [
            'usertest' => $test,
            'json' => $test->generateJson()
        ];
        return view('usertests.editor.edit', $data);
    }

    /**
     * Сохранение теста
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req) {
        DB::beginTransaction();
        $test = UserTest::findOrNew($req->get('id', null));
        $test->fill($req->input());
        $test->save();

        $qData = json_decode($req->get('questions'), true);
        $dataQuestionsIds = [];
        foreach ($qData as $q) {
            $question = UserTestQuestion::findOrNew($q['id']);
            $question->fill($q);
            $question->user_test_id = $test->id;
            $question->save();
            $dataQuestionsIds[] = $question->id;
            $dataAnswersIds = [];
            foreach ($q['answers'] as $a) {
                $answer = UserTestAnswer::findOrNew($a['id']);
                $answer->fill($a);
                $answer->question_id = $question->id;
                $answer->save();
                $dataAnswersIds[] = $answer->id;
            }
            $existingAnswers = $question->answers;
            foreach($existingAnswers as $ea){
                if(!in_array($ea->id, $dataAnswersIds)){
                    $ea->delete();
                }
            }
        }
        $existingQuestions = $test->questions;
        foreach($existingQuestions as $eq){
            if(!in_array($eq->id, $dataQuestionsIds)){
                $eq->delete();
            }
        }
        DB::commit();
        return redirect('usertests/editor/index')->with('msg_suc',  StrLib::SUC);
    }

    /**
     * Удалить тест
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function remove($id) {
        $test = UserTest::find($id);
        if(is_null($test)){
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $test->delete();
        return $this->backWithSuc();
    }

}
