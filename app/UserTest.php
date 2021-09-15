<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserTest extends Model {

    protected $table = 'user_tests';
    protected $fillable = ['name'];

    /**
     * Возвращает JSON с вопросами и ответами по этому тесту
     * @return type
     */
    public function generateJson() {
        $data = [];

        foreach ($this->questions as $q) {
            $qdata = $q->toArray();
            $qdata['answers'] = [];
            foreach ($q->answers as $a) {
                $adata = $a->toArray();
                $qdata['answers'][] = $adata;
            }
            $data[] = $qdata;
        }
        return json_encode($data);
    }

    public function questions() {
        return $this->hasMany('\App\UserTestQuestion', 'user_test_id');
    }

    public function getQuestionsIdList() {
        return UserTestQuestion::where('user_test_id', $this->id)->lists('id');
    }

    /**
     * Выполнен тест или не выполнен
     * @param \App\User $user
     * @return type
     */
    public function isCompleted($user) {
        $answered = count($this->getAnsweredQuestions($user, null, true));
        $total = UserTestQuestion::where('user_test_id', $this->id)->count();
        return ($answered == $total);
    }

    /**
     * Возвращает процент выполнения теста
     * @param \App\User $user
     * @return type
     */
    public function getCompletionPercent($user, $session_id = null) {
        $answered = count(UserTestUserAnswer::whereIn('question_id', UserTestQuestion::where('user_test_id', $this->id)->lists('id')->toArray())
                        ->where('user_id', $user->id)
                        ->where('session_id', $session_id)
                        ->groupBy('question_id')
                        ->select('id')->get());
        $total = UserTestQuestion::where('user_test_id', $this->id)->count();
        return $answered / $total * 100;
    }

    /**
     * вернуть отвеченные вопросы по тесту
     * @param \App\User $user пользователь для которого искать
     * @param integer $session_id идентификатор сессии
     * @param boolean $id_list вернуть в виде списка айдишников вопросов
     * @return type
     */
    public function getAnsweredQuestions($user, $session_id = null, $id_list = false) {
        $questionsList = UserTestQuestion::where('user_test_id', $this->id)->lists('id');
        $userAnswers = UserTestUserAnswer::whereIn('question_id', $questionsList)
                ->where('user_id', $user->id);
        if (!is_null($session_id)) {
            $userAnswers->where('session_id', $session_id);
        }
        return ($id_list) ? $userAnswers->lists('question_id') : $userAnswers->get();
    }

    /**
     * вернуть неотвеченные вопросы по тесту
     * @param \App\User $user пользователь для которого искать
     * @param integer $session_id идентификатор сессии
     * @param boolean $id_list вернуть в виде списка айдишников вопросов
     * @return type
     */
    public function getNotAnsweredQuestions($user, $session_id = null, $id_list = false) {
        $questionsList = UserTestQuestion::where('user_test_id', $this->id)->lists('id');
        $userAnswers = UserTestUserAnswer::whereIn('question_id', $questionsList)
                ->where('user_id', $user->id);
        if (!is_null($session_id)) {
            $userAnswers->where('session_id', $session_id);
        }
        $notAnswered = UserTestQuestion::whereNotIn('id', $userAnswers->lists('question_id'))->where('user_test_id', $this->id);
        return ($id_list) ? $notAnswered->lists('id') : $notAnswered->get();
    }

    /**
     * Возвращает количество правильных ответов за сессию для пользователя
     * @param \App\User $user
     * @param int $sid
     * @return int
     */
    public function getResultForSession($user, $sid) {
        $questions = $this->questions;
        $answeredNum = 0;
        foreach ($questions as $q) {
            if ($q->userAnsweredRight($user, $sid)) {
                $answeredNum++;
            }
        }
        return $answeredNum;
    }

    public function getTotalResult($user) {
        $answeredNum = 0;
        $questions = $this->questions;
        foreach ($questions as $q) {
            $answered = 0;
            $slist = $q->getSessionsList($user);
            foreach ($slist as $sid) {
                if ($q->userAnsweredRight($user, $sid) && $answered != 1) {
                    $answered = 1;
                    break;
                }
            }
            $answeredNum+=$answered;
        }
        return $answeredNum;
    }

    /**
     * Возвращает количество вопросов в тесте
     * @return type
     */
    public function countQuestions() {
        return UserTestQuestion::where('user_test_id', $this->id)->count();
    }

    /**
     * Возвращает сессии для теста
     * @param \App\User $user
     * @return type
     */
    public function getSessionsList($user) {
        return UserTestUserAnswer::where('user_id', $user->id)->whereIn('question_id', UserTestQuestion::where('user_test_id', $this->id)->lists('id')->toArray())->distinct()->lists('session_id');
    }

}
