<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Config;
use Log;
use Illuminate\Http\Request;

class Candidate extends Model {

    protected $table = 'debtors.candidate_list';
    protected $fillable = ['id', 'fio', 'city', 'tel_candidate', 'call_date', 'interview_date', 'reach', 'interview_result', 'decision', 'approval_date', 'comment', 'training', 'result', 'region', 'mentor', 'headman', 'responsible','comment_ruk'];
		
	public static function getReach() {
		$cand_reach = [
		    '0' => null,
            '1' => 'Не дошел',
            '2' => 'Дошел'
		];
		return $cand_reach;
	}

	public static function getDecision() {
		$cand_reach = [
			'0' => null,
			'1' => 'Отказ',
			'2' => 'Одобрено',
			'3' => 'Одобрение при согласовании руководителя',
			'4' => 'Одобрение при согласовании руководителя СБ'
		];
		return $cand_reach;
	}
	
	public static function getResult() {
		$cand_result = [
			'0' => null,
			'1' => 'Трудоустройство',
			'2' => 'Отказ кандидата',
			'3' => 'Резерв',
			'4' => 'Отказ руководителя'
		];
		return $cand_result;
	}

	public static function getInterviewResult() {
		$cand_interviewResult = [
			'0' => null,
			'1' => 'Отказ руководителя',
			'2' => 'Отказ кандидата',
			'3' => 'Анкета направлена СБ'
		];
		return $cand_interviewResult;
	}
	
	public static function getCandidateList($req) {
		$candidate = Candidate::orderBy('interview_date', 'desc');
		if (!empty($req->input ('id'))) {
			if ($req->input ('id') !== "") {
				$candidate->where('id', $req->get('id'));
			}
		}
		if (!empty($req->input ('fio'))) {
			if ($req->input ('fio') !== "") {
				$candidate->where('fio','like', '%'.$req->get('fio').'%');
			}
		}
		if (!empty($req->input ('city'))) {
			if ($req->input ('city') !== "") {
				$candidate->where('city','like', '%'.$req->get('city').'%');
			}
		}
		if (!empty($req->input ('tel_candidate'))) {
			if ($req->input ('tel_candidate') !== "") {
				$candidate->where('tel_candidate','like', '%'.$req->get('tel_candidate').'%');
			}
		}
		if (!empty($req->input ('call_date_start')) and !empty($req->input ('call_date_end'))) {
			if ($req->input ('call_date_start') !== "" and $req->input ('call_date_end') !== "") {
				$candidate->whereBetween('call_date', array($req->get('call_date_start')." 00:00:00", $req->get('call_date_end')." 23:59:59"));
			}
		}
		if (!empty($req->input ('interview_date_start')) and !empty($req->input ('interview_date_end'))) {
			if ($req->input ('interview_date_start') !== "" and $req->input ('interview_date_end') !== "") {
				$candidate->whereBetween('interview_date', array($req->get('interview_date_start')." 00:00:00", $req->get('interview_date_end')." 23:59:59"));
			}
		}
		if (null !==($req->input ('reach'))) {
			if ($req->input ('reach') !== "") {
				$candidate->whereIn('reach',$req->get('reach'));
			}
		}
		if (null !==($req->input('interview_result'))) {
			if ($req->input ('interview_result') !== "") {
				$candidate->whereIn('interview_result',$req->get('interview_result'));
			}
		}
		if (null !==($req->input('decision'))) {
			if ($req->input ('decision') !== "") {
				$candidate->whereIn('decision',$req->get('decision'));
			}
		}
        if (!empty($req->input ('approval_date_start')) and !empty($req->input ('approval_date_end'))) {
			if ($req->input ('approval_date_start') !== "" and $req->input ('approval_date_end') !== "") {
				$candidate->whereBetween('approval_date', array($req->get('approval_date_start')." 00:00:00", $req->get('approval_date_end')." 23:59:59"));
			}
		}
		if (!empty($req->input ('comment'))) {
			if ($req->input ('comment') !== "") {
				$candidate->where('comment','like', '%'.$req->get('comment').'%');
			}
		}
		if (!empty($req->input ('training_date_start')) and !empty($req->input ('training_date_end'))) {
			if ($req->input ('training_date_start') !== "" and $req->input ('training_date_end') !== "") {
				$candidate->whereBetween('training', array($req->get('training_date_start')." 00:00:00", $req->get('training_date_end')." 23:59:59"));
			}
		}
		if (!empty($req->input ('result'))) {
			if ($req->input ('result') !== "") {
				$candidate->whereIn('result',$req->get('result'));
			}
		}
		if (!empty($req->input ('region'))) {
			if ($req->input ('region') !== "") {
				$candidate->whereIn('region',$req->get('region'));
			}
		}
		if (!empty($req->input ('mentor'))) {
			if ($req->input ('mentor') !== "") {
				$candidate->where('mentor','like', '%'.$req->get('mentor').'%');
			}
		}
        if (!empty($req->input ('headman'))) {
			if ($req->input ('headman') !== "") {
				$candidate->whereIn('headman',$req->get('headman'));
			}
		}
        if (!empty($req->input ('responsible'))) {
			if ($req->input ('responsible') !== "") {
				$candidate->where('responsible','like', '%'.$req->get('responsible').'%');
			}
		}
		if (!empty($req->input ('comment_ruk'))) {
			if ($req->input ('comment_ruk') !== "") {
				$candidate->where('comment_ruk','like', '%'.$req->get('comment_ruk').'%');
			}
		}
        return $candidate->paginate(25);
		//->toArray();
    }
	
    public static function getCandidateListAll($req) {
		$candidateAll = Candidate::orderBy('interview_date', 'desc');
		if (!empty($req->input ('id'))) {
			if ($req->input ('id') !== "") {
				$candidateAll->where('id', $req->get('id'));
			}
		}
		if (!empty($req->input ('fio'))) {
			if ($req->input ('fio') !== "") {
				$candidateAll->where('fio', 'like', '%'.$req->get('fio').'%');
			}
		}
		if (!empty($req->input ('city'))) {
			if ($req->input ('city') !== "") {
				$candidateAll->where('city','like', '%'.$req->get('city').'%');
			}
		}
		if (!empty($req->input ('tel_candidate'))) {
			if ($req->input ('tel_candidate') !== "") {
				$candidateAll->where('tel_candidate','like', '%'.$req->get('tel_candidate').'%');
			}
		}
		if (!empty($req->input ('call_date_start')) and !empty($req->input ('call_date_end'))) {
			if ($req->input ('call_date_start') !== "" and $req->input ('call_date_end') !== "") {
				$candidateAll->whereBetween('call_date', array($req->get('call_date_start')." 00:00:00", $req->get('call_date_end')." 23:59:59"));
			}
		}
		if (!empty($req->input ('interview_date_start')) and !empty($req->input ('interview_date_end'))) {
			if ($req->input ('interview_date_start') !== "" and $req->input ('interview_date_end') !== "") {
				$candidateAll->whereBetween('interview_date', array($req->get('interview_date_start')." 00:00:00", $req->get('interview_date_end')." 23:59:59"));
			}
		}
		if (null !==($req->input ('reach'))) {
			if ($req->input ('reach') !== "") {
				$candidateAll->whereIn('reach',$req->get('reach'));
			}
		}
		if (null !==($req->input('interview_result'))) {
			if ($req->input ('interview_result') !== "") {
				$candidateAll->whereIn('interview_result',$req->get('interview_result'));
			}
		}
		if (null !==($req->input('decision'))) {
			if ($req->input ('decision') !== "") {
				$candidateAll->whereIn('decision',$req->get('decision'));
			}
		}
        if (!empty($req->input ('approval_date_start')) and !empty($req->input ('approval_date_end'))) {
			if ($req->input ('approval_date_start') !== "" and $req->input ('approval_date_end') !== "") {
				$candidateAll->whereBetween('approval_date', array($req->get('approval_date_start')." 00:00:00", $req->get('approval_date_end')." 23:59:59"));
			}
		}
		if (!empty($req->input ('comment'))) {
			if ($req->input ('comment') !== "") {
				$candidateAll->where('comment','like', '%'.$req->get('comment').'%');
			}
		}
		if (!empty($req->input ('training_date_start')) and !empty($req->input ('training_date_end'))) {
			if ($req->input ('training_date_start') !== "" and $req->input ('training_date_end') !== "") {
				$candidateAll->whereBetween('training', array($req->get('training_date_start')." 00:00:00", $req->get('training_date_end')." 23:59:59"));
			}
		}
		if (!empty($req->input ('result'))) {
			if ($req->input ('result') !== "") {
				$candidateAll->whereIn('result',$req->get('result'));
			}
		}
		if (!empty($req->input ('region'))) {
			if ($req->input ('region') !== "") {
				$candidateAll->whereIn('region',$req->get('region'));
			}
		}
		if (!empty($req->input ('mentor'))) {
			if ($req->input ('mentor') !== "") {
				$candidateAll->where('mentor','like', '%'.$req->get('mentor').'%');
			}
		}
        if (!empty($req->input ('headman'))) {
			if ($req->input ('headman') !== "") {
				$candidateAll->whereIn('headman',$req->get('headman'));
			}
		}
        if (!empty($req->input ('responsible'))) {
			if ($req->input ('responsible') !== "") {
				$candidateAll->where('responsible','like', '%'.$req->get('responsible').'%');
			}
		}
		if (!empty($req->input ('comment_ruk'))) {
			if ($req->input ('comment_ruk') !== "") {
				$candidateAll->where('comment_ruk','like', '%'.$req->get('comment_ruk').'%');
			}
		}
        return $candidateAll->get();
    }

}
