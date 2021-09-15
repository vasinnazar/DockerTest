<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;

class QuizDepartmentController extends BasicController {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    public function create(Request $req) {
        $quizDept = new \App\QuizDepartment();
        return view('quiz/quizDept', [
            'quizDeptModel' => $quizDept,
            'quizDeptModelYesNoFields' => $quizDept->getYesNoCommentFields(),
            'directors' => $this->getDirectorsList(false)
        ]);
    }

    public function getReport(Request $req) {
        $report = \App\QuizDepartment::getReport($req->get('year', '2017'), $req->get('month', null), $req->get('fio_ruk', null), $req->get('fio_star_spec', null));
        return view('quiz/quizDeptReport', [
            'report' => $report,
            'directors' => $this->getDirectorsList(),
        ]);
    }

    function getDirectorsList($withAll = true) {
        $res = ($withAll) ? ['all' => 'Все'] : [];
        $list = json_decode(\App\Region::groupBy('director')->lists('director', 'director'), true);
        $list[] = 'Попова Татьяна Анатольевна';
        return array_merge($res, $list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $req
     * @return \Illuminate\Http\Response
     */
    public function store(Request $req) {
        $quiz = new \App\QuizDepartment();
        $quiz->fill($req->all());
        $quiz->user_id = Auth::user()->id;
        $quiz->subdivision_id = Auth::user()->subdivision->id;
        $quiz->save();
        return redirect('/')->with('msg_suc', 'Спасибо за участие в опросе');
    }

}
