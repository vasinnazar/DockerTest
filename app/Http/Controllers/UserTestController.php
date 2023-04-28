<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\UserTest;
use App\UserTestQuestion;
use App\Utils\StrLib;
use Auth;
use Carbon\Carbon;
use Log;

class UserTestController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * список тестов
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $data = ['usertests' => UserTest::where('enabled', 1)->get()];
        return view('usertests.index', $data);
    }

    public function home() {
        return view('usertests.home');
    }

    /**
     * открывает вопрос теста
     * @param integer $test_id ид теста
     * @param integer $question_id ид вопроса
     * @return type
     */
    public function view(Request $req, $test_id, $question_id = null) {
        $test = UserTest::find($test_id);
        if (is_null($question_id)) {
            $req->session()->forget('usertest_session_id');
            $firstQuestion = UserTestQuestion::where('user_test_id', $test_id)
                    ->whereNotIn('id', $test->getAnsweredQuestions(Auth::user(), \Carbon\Carbon::today()->format('Y-m-d'), true))
                    ->first();
            if (is_null($firstQuestion)) {
                return $this->backWithErr(StrLib::ERR_NULL);
            } else {
                $question_id = $firstQuestion->id;
            }
        }
        $question = UserTestQuestion::find($question_id);
        if (empty($req->session()->get('usertest_session_id'))) {
            $session_id = $test->getMaxSessionId(auth()->user()) + 1;
        } else {
            $session_id = $req->session()->get('usertest_session_id');
        }
        $data = [
            'session_id' => $session_id,
            'questions' => $test->questions,
            'cur_question' => $question,
            'test_completion' => $test->getCompletionPercent(Auth::user(), $session_id)
        ];
        return view('usertests.question', $data);
    }

    /**
     * ответ на вопрос
     * @param Request $req
     * @return type
     */
    public function answer(Request $req) {
        $user = Auth::user();
        $test = UserTest::find($req->get('test_id'));
        if (is_null($test)) {
            return $this->backWithErr(StrLib::ERR_NULL . '(1)');
        }
        $question = UserTestQuestion::find($req->get('question_id'));
        if (is_null($question)) {
            return $this->backWithErr(StrLib::ERR_NULL . '(2)');
        }
        if (empty($req->get('session_id'))) {
            $sessionId = $req->session()->get('usertest_session_id');
            if(empty($sessionId)){
                $sessionId = $question->getSessionId($user);
            }
        } else {
            $sessionId = $req->get('session_id');
        }

        foreach ($req->get('answer',[]) as $a) {
            \App\UserTestUserAnswer::create([
                'answer_id' => $a,
                'question_id' => $question->id,
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);
        }
        $notAnsweredQuestions = $test->getNotAnsweredQuestions($user, $sessionId, true);
        $answeredQuestions = $test->getAnsweredQuestions($user, $sessionId, true);
        if (count($notAnsweredQuestions) == 0) {
            $req->session()->forget('usertest_session_id');
            return redirect('usertests/index')->with('msg_suc', 'Тест успешно завершен');
        }
        $qid = $notAnsweredQuestions[rand(0, count($notAnsweredQuestions) - 1)];
        $req->session()->put('usertest_session_id', $sessionId);
        return redirect('usertests/view/' . $test->id . '/' . $qid);
    }

    /**
     * статистика по тесту
     * @param integer $id ид теста
     */
    public function stat($id, Request $req) {
        if ($req->has('all')) {
            return $this->allStat($id, $req);
        }
        if ($req->has('missed')) {
            return $this->allUnansweredStat($id, $req);
        }
        $test = UserTest::find($id);
        $user = ($req->has('user_id')) ? \App\User::find($req->get('user_id')) : Auth::user();
        $results = [];
        $testSessionsList = $test->getSessionsList($user);
        foreach ($testSessionsList as $tsid) {
            $results[] = $test->getResultForSession($user, $tsid);
        }
        $data = [
            'test' => $test,
            'user' => $user,
            'user_answers' => \App\UserTestUserAnswer::where('user_id', $user->id)->whereIn('question_id', $test->getQuestionsIdList())->orderBy('created_at', 'desc')->get(),
            'questions' => $test->questions,
            'results' => $results,
            'total_result' => $test->getTotalResult($user),
            'test_sessions_list' => $testSessionsList,
            'questions_num' => $test->countQuestions()
        ];
        return view('usertests.usertest_stat', $data);
    }

    /**
     * Вывести статистику по всем пользователям
     * @param integer $id ид теста
     * @param Request $req
     * @return type
     */
    function allStat($id, Request $req) {
        $test = UserTest::find($id);
        $questions = $test->questions;
        $answeredUsers = \App\UserTestUserAnswer::groupBy('user_id')
                ->leftJoin('users', 'users.id', '=', 'user_tests_users_answers.user_id')
                ->leftJoin('subdivisions', 'subdivisions.id', '=', 'users.subdivision_id')
                ->leftJoin('cities', 'subdivisions.city_id', '=', 'cities.id')
                ->leftJoin('regions', 'cities.region_id', '=', 'regions.id')
                ->where('user_tests_users_answers.created_at', '>=', '2017-07-17')
                ->orderBy('regions.name', 'asc')
                ->orderBy('users.name', 'asc')
                ->pluck('user_id')
                ->toArray();
        $data = ['answers' => [], 'test' => $test, 'questions' => $questions];
        $users = [];
        $qnum = count($questions);
        foreach ($answeredUsers as $au) {
            $user = \App\User::find($au);
            $u = ['name' => $user->name, 'answered_num' => 0, 'questions_num' => $qnum, 'region' => $user->subdivision->getCity->region->name];
            foreach ($questions as $q) {
                $answered = 0;
                $slist = $q->getSessionsList($user, '2017-07-17');
                foreach ($slist as $sid) {
                    if ($q->userAnsweredRight($user, $sid) && $answered != 1) {
                        $answered = 1;
                        break;
                    }
                }
                $u['answered'] = $answered;
                $u['answered_num']+=$answered;
                $u['sessions_num'] = count($slist);
            }
            $users[] = $u;
        }
        $data['users'] = $users;
//        foreach ($answeredUsers as $au) {
//            $answers = \App\UserTestUserAnswer::where('user_id', $au)->whereIn('question_id', $test->getQuestionsIdList())->orderBy('created_at', 'desc')->get();
//            $data['answers'][] = [
//                'username' => \App\User::where('id', $au)->value('name'),
//                'answers' => $answers
//            ];
//        }
        return view('usertests.usertest_stat_all', $data);
    }

    /**
     * Вернуть статистику по всем пользователям по неотвеченным вопросам
     * @param integer $id ид теста
     * @return type
     */
    function allUnansweredStat($id, Request $req) {
        set_time_limit(600);
        $test = UserTest::find($id);
        $start_date = $req->get('start_date', '2017-07-17');
        $end_date = $req->get('end_date', Carbon::now()->format('Y-m-d'));
        $answered_users_id = \App\UserTestUserAnswer::leftJoin('user_test_questions', 'user_test_questions.id', '=', 'user_tests_users_answers.question_id')
                ->where('user_test_questions.user_test_id', $id)
                ->where('user_tests_users_answers.created_at', '>=', $start_date)
                ->where('user_tests_users_answers.created_at', '<', $end_date)
                ->distinct()
                ->pluck('user_id')
                ->toArray();
        $users = \App\User::whereNotIn('users.id', $answered_users_id)
                ->leftJoin('subdivisions', 'subdivisions.id', '=', 'users.subdivision_id')
                ->leftJoin('cities', 'subdivisions.city_id', '=', 'cities.id')
                ->leftJoin('regions', 'cities.region_id', '=', 'regions.id')
                ->where('subdivisions.is_terminal', 0)
                ->where('users.banned', 0)
                ->where('users.last_login', '>=', \Carbon\Carbon::today()->subDays(14))
                ->where('users.group_id', '>', 0)
                ->select(['users.name as name', 'regions.name as region'])
                ->distinct('users.id')
                ->orderBy('region', 'asc')
                ->orderBy('name', 'asc');
        
        if (config('app.version_type') != 'sales') {
            $users->leftJoin('role_user', 'role_user.user_id', '=', 'users.id');
            $users->where('role_user.role_id', 12);
        } else {
            $users->whereNotIn('subdivisions.id', [113, 668]);
        }
        
        $users_collection = $users->get();

        $data = ['users' => $users_collection, 'test' => $test, 'dates'];
        return view('usertests.usertest_stat_all_missed', $data);
    }

}
