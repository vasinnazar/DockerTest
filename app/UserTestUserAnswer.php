<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTestUserAnswer extends Model {

    protected $table = 'user_tests_users_answers';
    protected $fillable = ['user_id', 'question_id', 'answer_id', 'session_id'];

    public function user() {
        return $this->hasOne('\App\User', 'user_id');
    }

    public function answer() {
        return $this->hasOne('\App\UserTestQuestion', 'user_id');
    }

    public function question() {
        return $this->hasOne('\App\UserTestQuestion', 'question_id');
    }

}
