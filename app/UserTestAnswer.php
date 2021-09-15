<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTestAnswer extends Model {

    protected $table = 'user_test_answers';
    protected $fillable = ['text', 'is_right'];

    public function wasSelected($uid, $sid, $qid) {
        return (UserTestUserAnswer::where('user_id', $uid)->where('answer_id', $this->id)->where('question_id', $qid)->count() > 0) ? true : false;
    }

}
