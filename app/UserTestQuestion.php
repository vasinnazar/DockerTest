<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserTestQuestion extends Model {

    protected $table = 'user_test_questions';
    protected $fillable = ['text'];

    public function answers() {
        return $this->hasMany('\App\UserTestAnswer', 'question_id');
    }

    public function usertest() {
        return $this->belongsTo('\App\UserTest', 'user_test_id');
    }
    
    /**
     * Правильно ли ответил пользователь на вопрос
     * @param \App\User $user
     * @param type $date
     * @return boolean
     */
    public function userAnsweredRight($user, $session_id) {
        $rightAnswers = UserTestAnswer::where('question_id', $this->id)->where('is_right', '1')->lists('id')->toArray();
        $userAnswers = UserTestUserAnswer::where('user_id', $user->id)
                ->where('session_id', $session_id)
                ->where('question_id', $this->id)
                ->get();
        $answeredRightCount = 0;
        foreach ($userAnswers as $ua) {
            if (!in_array($ua->answer_id, $rightAnswers)) {
                return false;
            } else {
                $answeredRightCount++;
            }
        }
        if ($answeredRightCount == count($rightAnswers)) {
            return true;
        }
        return false;
    }
    public function getSessionId($user){
        return UserTestUserAnswer::where('user_id',$user->id)->where('question_id',$this->id)->max('session_id')+1;
    }
    public function getSessionsList($user){
        return UserTestUserAnswer::where('user_id',$user->id)->where('question_id',$this->id)->distinct()->lists('session_id');
    }

}
