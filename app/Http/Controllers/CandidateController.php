<?php

namespace App\Http\Controllers;

use App\CandidateRegion;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Utils\PermLib;
use App\Permission;
use App\Utils\StrLib;
use Auth;
use App\Candidate;
use App\Region;
use App\Order;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\DB;
use App\StrUtils;
use App\Utils\HtmlHelper;
use Carbon\Carbon;
use App\Photo;
use Illuminate\Support\Facades\Storage;
use App\Passport;
use App\User;
use App\Utils;
use Image;
use App\PlannedDeparture;
use App\Loan;
use App\Claim;
use App\Repayment;
use App\MySoap;
use App\NoticeNumbers;
use Config;
use App\MyResult;
use Log;
use App\DebtorsInfo;
use App\Message;
use App\Role;

class CandidateController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
        if (is_null(Auth::user())) {
            return redirect('auth/login');
        }
    }

    /*     * \PC::debug($res1c);
     * Открывает список кандидатов
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $req) {
        if ($req->get('toExcel') == 1) {
            return $this->exportToExcel($req);
        }
        $candidate = Candidate::getCandidateList($req);
        $reach = Candidate::getReach();
        $decision = Candidate::getDecision();
		$result = Candidate::getResult();
		$interviewResult = Candidate::getInterviewResult();
        $regions = CandidateRegion::getCandidateRegions();

        $role = DB::table('roles')->select('id')->where('name', 'DepHR_headman')->first();
        $role_user = DB::table('role_user')->where('role_id', $role->id)->lists('user_id');
		$headman = DB::table('candidate_list')->groupBy('headman')->lists('headman');
		$userList = array_merge($role_user, $headman);
		
		$user = DB::table('users')->whereIn('id', $userList)->orderBy('name', 'asc')->lists('name', 'id');
		
		//$userList = DB::table('candidate_list')->leftJoin('users', 'users.id', '=', 'candidate_list.headman')->groupBy('candidate_list.headman')->lists('users.name' as 'name', 'candidate_list.headman' as 'id');
		
        return view('candidate.index', [
            'candidate_list' => $candidate,
            'reach_list' => $reach,
            'decision_list' => $decision,
			'result_list' => $result,
			'interviewResult_list' => $interviewResult,
            'regions_list' => $regions,
            'roles_list' => $user
        ]);
    }

    public function create(Request $req) {
        $reach = Candidate::getReach();
        $decision = Candidate::getDecision();
		$result = Candidate::getResult();
		$interviewResult = Candidate::getInterviewResult();
        $regions = CandidateRegion::where('visible', 1)->lists('name', 'id')->toArray();
        $role = DB::table('roles')->select('id')->where('name', 'DepHR_headman')->first();
        $role_user = DB::table('role_user')->where('role_id', $role->id)->lists('user_id');
        $user = DB::table('users')->whereIn('id', $role_user)->lists('name', 'id');
        return view('candidate.create', [
            'candidate' => new Candidate(),
            'reach_list' => $reach,
            'decision_list' => $decision,
			'result_list' => $result,
			'interviewResult_list' => $interviewResult,
            'regions_list' => $regions,
            'roles_list' => $user
        ]);
    }

    public function insertCandidate(Request $req) {
        Candidate::create($req->input());
        return redirect('candidate/index');
    }

    public function deleteCandidate(Request $req) {
        Candidate::where('id', '=', $req->get('id'))->delete();
        return redirect('candidate/index');
    }

    public function updateCandidate(Request $req) {
        //dd($req->all());
        if(Candidate::find($req->get('id'))){
            Candidate::find($req->get('id'))->update($req->input());
        }
        return redirect('candidate/index');
    }

    public function update(Request $req) {
        $reach = Candidate::getReach();
        $decision = Candidate::getDecision();
		$result = Candidate::getResult();
		$interviewResult = Candidate::getInterviewResult();
        $regions = CandidateRegion::where('visible', 1)->lists('name', 'id')->toArray();
        $role = DB::table('roles')->select('id')->where('name', 'DepHR_headman')->first();
        $role_user = DB::table('role_user')->where('role_id', $role->id)->lists('user_id');
        $user = DB::table('users')->whereIn('id', $role_user)->lists('name', 'id');

        return view('candidate.update', [
            'candidate' => Candidate::where('id', '=', $req->get('id'))->first(),
            'reach_list' => $reach,
            'decision_list' => $decision,
			'result_list' => $result,
			'interviewResult_list' => $interviewResult,
            'regions_list' => $regions,
            'roles_list' => $user
        ]);
    }

    public function exportToExcel(Request $req) {
        $candidateForExcel = Candidate::getCandidateListAll($req);

        $reachs = Candidate::getReach();
        $decisions = Candidate::getDecision();
		$result = Candidate::getResult();
		$interviewResult = Candidate::getInterviewResult();
        $regions = CandidateRegion::getCandidateRegions();
        $role = DB::table('roles')->select('id')->where('name', 'DepHR_headman')->first();
        $role_user = DB::table('role_user')->where('role_id', $role->id)->lists('user_id');
        $user = DB::table('users')->whereIn('id', $role_user)->lists('name', 'id');

        $html = '<table border="1">';
        $firstRow = true;
        $colHeaders = [
            'id' => 'id',
            'fio' => 'ФИО',
            'city' => 'Город',
            'tel_candidate' => 'Телефон кандидата',
            'call_date' => 'Дата звонка',
            'interview_date' => 'Дата собеседования',
            'reach' => 'Дошел/ Не дошел',
			'interview_result' => 'результат собеседования',
            'decision' => 'Решение СБ',
            'approval_date' => 'Дата одобрения',
            'comment' => 'Комментарий менеджера',
            'training' => 'Дата выхода на стажировку',
            'result' => 'Результат по кандидату',
            'comment_ruk' => 'Комментарий руководителя',
            'mentor' => 'Куратор',
            'headman' => 'Руководитель',
            'responsible' => 'Ответственный'
        ];
        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($colHeaders as $k => $v) {
            $html .= '<th>' . $v . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        // что то тут педрит 
        foreach ($candidateForExcel as $candidate) {
			$candidateArray = $candidate->toArray();
			foreach($decisions as $decision_key => $decision_value) {
                 if($decision_key == $candidateArray['decision']) {
                     $candDecision = $decision_value;
                 }				
             }
			foreach($result as $result_key => $result_value) {
				if($result_key == $candidateArray['result']) {
					$candResult = $result_value;
				}
             }
            $date1 = \Carbon\Carbon::now();

			if ($candidateArray['approval_date']!="") { //Дата одобрения
                 $date2 = with(new \Carbon\Carbon($candidateArray['approval_date']));
             } else{
                 $date2 = \Carbon\Carbon::yesterday();
             }

             //$date3 = $date1->diffInDays($date2); // разница в днях
			$date3 = with(new \Carbon\Carbon($date2))->addDay(1)->setTime(12,0,0);
			
			if($candResult=='Отказ кандидата' or $candResult=='Отказ руководителя') {
				$html .= '<tr>';
			} elseif (($candDecision=="Одобрено" or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") and ($candResult!='' or !empty($candResult)) and $candidateArray['mentor']=="" and ($candidateArray['training']=="" or $candidateArray['training']=="0000-00-00 00:00:00")) {
				$html .= '<tr">';
			} elseif (($candDecision=="Одобрено"or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") and ($candidateArray['training']!="" or $candidateArray['training']!="0000-00-00 00:00:00") and $candidateArray['mentor']!="") {
				$html .= '<tr style="background-color: green;">';
			} elseif (($candDecision=="Одобрено" or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") and $date1 > $date3) {
				$html .= '<tr style="background-color: red;">';
			} elseif ($candDecision=="Одобрено" or $candDecision=="Одобрение при согласовании руководителя" or $candDecision=="Одобрение при согласовании руководителя СБ") {
				$html .= '<tr style="background-color: yellow;">';
             } else {
                $html .= '<tr>';
             }			
            foreach ($candidateArray as $key => $val) {
                if ($key == 'id') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'fio') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'city') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'tel_candidate') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'call_date') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'interview_date') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'reach') {
                    foreach ($reachs as $reachkey => $reachval) {
                        if ($reachkey == $val) {
                            $html .= '<th>' . $reachval . '</th>';
                        }
                    }
                }
				if ($key == 'interview_result') {
					foreach($interviewResult as $interviewResultkey => $interviewResultvalue) {
						if($interviewResultkey == $val) {
							$html .= '<th>' . $interviewResultvalue . '</th>';
						}
					}
                 }	
                if ($key == 'decision') {
                    $html .= '<th>' . $candDecision . '</th>';
                }
                if ($key == 'approval_date') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'comment') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'training') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'result') {
                    $html .= '<th>' . $candResult . '</th>';
                }
                if ($key == 'region') {
                    foreach ($regions as $regionkey => $regionval) {
                        if ($regionkey == $val) {
                            $html .= '<th>' . $regionval . '</th>';
                        }
                    }
                }
                if ($key == 'mentor') {
                    $html .= '<th>' . $val . '</th>';
                }
                if ($key == 'headman') {
                    foreach ($user as $userkey => $userval) {
                        if ($userkey == $val) {
                            $html .= '<th>' . $userval . '</th>';
                        }
                    }
                }
                if ($key == 'responsible') {
                    $html .= '<th>' . $val . '</th>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $sdate = date('d.m.Y');
        $file = "Кандидаты_" . $sdate . ".xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
                        ->header("Content-type", "application/vnd.ms-excel")
                        ->header("Content-Disposition", "attachment; filename=$file");
    }

	public function exportToExcelReport(Request $req) {
		if(isset($req->start_date) and $req->start_date == ''){
			return redirect('candidate/index')->with('msg_err', 'Не выбранна начальная дата отчета!');
		} elseif(isset($req->end_date) and $req->end_date == '') {
			return redirect('candidate/index')->with('msg_err', 'Не выбранна конечная дата отчета!');
		}
		$today = date('Y-m-d');
		$start_date = (!empty($req->get('start_date'))?$req->get('start_date'):$today);
		$end_date   = (!empty($req->get('end_date'))?$req->get('end_date'):$today);

        $regions = CandidateRegion::getCandidateRegions();

        $arr_user1 = ['16','21','13', '27', '15', '14', '23'];
        $arr_user2 = ['9','17','18','19','10','23'];

		$candiSpec1 = Candidate::whereBetween('call_date', array($start_date." 00:00:00", $end_date." 23:59:59"))
							->whereIn('region',$arr_user1);
		$candiSpec1Count = $candiSpec1->count();
		$candiSpec1Run = $candiSpec1->where('reach','2')->count();
		if ($candiSpec1Run == 0 or $candiSpec1Count ==0) {
			$candiSpec1Survivors = 0;
		} else {
			$candiSpec1Survivors = intval(round($candiSpec1Run/$candiSpec1Count*100,0));
		}
		$candiSpec1Intern = $candiSpec1->whereNotNull('training')
									   ->where('training','<>','0000-00-00 00:00:00')
									   ->count();
		if ($candiSpec1Intern == 0 or $candiSpec1Run == 0) {
			$candiSpec1Percent = 0;
		} else {
			$candiSpec1Percent = intval(round($candiSpec1Intern/$candiSpec1Run*100,0));
		}						

		$candiSpec2 = Candidate::whereBetween('call_date', array($start_date." 00:00:00", $end_date." 23:59:59"))
							->whereIn('region',$arr_user2);
		$candiSpec2Count = $candiSpec2->count();
		$candiSpec2Run = $candiSpec2->where('reach','2')->count();
		if ($candiSpec2Run == 0 or $candiSpec2Count ==0) {
			$candiSpec2Survivors = 0;
		} else {
			$candiSpec2Survivors = intval(round($candiSpec2Run/$candiSpec2Count*100,0));
		}
		$candiSpec2Intern = $candiSpec2->whereNotNull('training')
									   ->where('training','<>','0000-00-00 00:00:00')
									   ->count();
		if ($candiSpec2Intern == 0 or $candiSpec2Run == 0) {
			$candiSpec2Percent = 0;
		} else {
			$candiSpec2Percent = intval(round($candiSpec2Intern/$candiSpec2Run*100,0));
		}
		
		$html = '<table border="1">';
        $colHeaders = [
            'sales_department' => 'Департамент продаж',
            'avg_vacancy' => 'Среднее кол-во вакансий',
            'number_invitees' => 'Кол-во приглашений, итого',
            'number_survivors' => 'Кол-во дошедших, итого',
            'perc_involved' => '% привлечения',
            'number_trainees' => 'Кол-во выведенных на стажировку',
            'percent' => '%',
            'number_employed' => 'Кол-во трудоустроенных',
            'total' => '% воронки, итого'
        ];
        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($colHeaders as $k => $v) {
            $html .= '<th style="background-color: #d6dce4;">' . $v . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
			$html .= '<tr>';	
				$html .= '<th>Костикова О. В.</th>'; // candiSpec1
				$html .= '<th></th>';
				$html .= '<th>'.$candiSpec1Count.'</th>';
				$html .= '<th>'.$candiSpec1Run.'</th>';
				$html .= '<th style="background-color: #e2efda;">'.$candiSpec1Survivors.'</th>';
				$html .= '<th>'.$candiSpec1Intern.'</th>';
				$html .= '<th>'.$candiSpec1Percent.'</th>';
				$html .= '<th></th>';
				$html .= '<th style="background-color: #e2efda;"></th>';
			$html .= '</tr>';
			$html .= '<tr>';	
				$html .= '<th>Ибатулина Е.С.</th>'; // candiSpec2
				$html .= '<th></th>';
				$html .= '<th>'.$candiSpec2Count.'</th>';
				$html .= '<th>'.$candiSpec2Run.'</th>';
				$html .= '<th style="background-color: #e2efda;">'.$candiSpec2Survivors.'</th>';
				$html .= '<th>'.$candiSpec2Intern.'</th>';
				$html .= '<th>'.$candiSpec2Percent.'</th>';
				$html .= '<th></th>';
				$html .= '<th style="background-color: #e2efda;"></th>';
			$html .= '</tr>';
			
			$totalCount = $candiSpec1Count+$candiSpec2Count;
			$totalRun = $candiSpec1Run+$candiSpec2Run;
			$totalSurvivors = intval(round($totalRun/$totalCount*100,0));
			$totalIntern = $candiSpec1Intern+$candiSpec2Intern;
			if($totalRun==0){
				$totalPercent = intval(round($totalIntern,0));
			}else{
				$totalPercent = intval(round($totalIntern/$totalRun*100,0));
			}
			$html .= '<tr>';
				$html .= '<th>Итого Управление подбора и адаптации</th>'; // Итого
				$html .= '<th></th>';
				$html .= '<th>'.$totalCount.'</th>';
				$html .= '<th>'.$totalRun.'</th>';
				$html .= '<th style="background-color: #e2efda;">'.$totalSurvivors.'</th>';
				$html .= '<th>'.$totalIntern.'</th>';
				$html .= '<th>'.$totalPercent.'</th>';
				$html .= '<th></th>';
				$html .= '<th style="background-color: #e2efda;"></th>';
			$html .= '</tr>';
			
			
		$html .= '</tbody>';
        $html .= '</table>';
		
		$file = "Отчет_кандидаты_с_".$start_date."_по_".$end_date.".xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
                        ->header("Content-type", "application/vnd.ms-excel")
                        ->header("Content-Disposition", "attachment; filename=$file");
		
	}
	
	public function exportToExcelReportCity (Request $req) {
		if(isset($req->start_date) and $req->start_date == ''){
			return redirect('candidate/index')->with('msg_err', 'Не выбранна начальная дата отчета!');
		} elseif(isset($req->end_date) and $req->end_date == '') {
			return redirect('candidate/index')->with('msg_err', 'Не выбранна конечная дата отчета!');
		} 
		$today = date('Y-m-d');
		$start_date = (!empty($req->get('start_date'))?$req->get('start_date'):$today);
		$end_date   = (!empty($req->get('end_date'))?$req->get('end_date'):$today);
		$start_dateT = $start_date.' 00:00:00';
		$end_dateT = $end_date.' 23:59:59';

        $regions = CandidateRegion::getCandidateRegions();
		//$role = DB::table('roles')->select('id')->where('name', 'DepHR_headman')->first();
        //$role_user = DB::table('role_user')->where('role_id', $role->id)->lists('user_id');
       // $user = DB::table('users')->whereIn('id', $role_user)->lists('name', 'id');

        $arr_user1 = "('16','21','13', '27', '15', '14', '23')";
        $arr_user2 = "('9','17','18','19','10','23')";

		$cityReports1 = DB::select(DB::raw('SELECT cl.region, cl.city, (SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city) AS "number_invitees",
								(SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and reach="2" AND city=cl.city) AS "number_survivors",
								ROUND((SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND reach="2" AND city=cl.city)*100./(SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND city=cl.city),0) AS "perc_involved",
								(SELECT COUNT(training) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND training > "2017-01-01 00:00:00" AND city=cl.city) AS "number_trainees",
								ROUND((SELECT COUNT(training) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND training > "2017-01-01 00:00:00" AND city=cl.city)*100./(SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user1.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND reach="2" AND city=cl.city),0) AS "perc_trainees",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND decision="1") AS "result_sb",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND result="4") AS "deni_headman",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND interview_result="2") AS "deni_candidate",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND result="2") AS "deni_recrut"
								FROM candidate_list cl 
								WHERE cl.region in '.$arr_user1.' and cl.call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'"
								GROUP BY cl.city
								ORDER BY cl.region DESC'));
		$cityReports2 = DB::select(DB::raw('SELECT cl.region, cl.city, (SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city) AS "number_invitees",
								(SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and reach="2" AND city=cl.city) AS "number_survivors",
								ROUND((SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND reach="2" AND city=cl.city)*100./(SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND city=cl.city),0) AS "perc_involved",
								(SELECT COUNT(training) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND training > "2017-01-01 00:00:00" AND city=cl.city) AS "number_trainees",
								ROUND((SELECT COUNT(training) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND training > "2017-01-01 00:00:00" AND city=cl.city)*100./(SELECT COUNT(reach) FROM candidate_list WHERE region in '.$arr_user2.' and call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" AND reach="2" AND city=cl.city),0) AS "perc_trainees",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND decision="1") AS "result_sb",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND result="4") AS "deni_headman",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND interview_result="2") AS "deni_candidate",
								(SELECT COUNT(*) FROM candidate_list WHERE call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'" and city=cl.city AND result="2") AS "deni_recrut"
								FROM candidate_list cl 
								WHERE cl.region in '.$arr_user2.' and cl.call_date BETWEEN "'.$start_dateT.'" and "'.$end_dateT.'"
								GROUP BY cl.city
								ORDER BY cl.region DESC'));
		$html = '<table border="1">';
        $colHeaders = [
			'tbl_headman' => '',
			'tbl_openVacansu' => 'Открытых вакансий',
			'tbl_city' => 'Город',
			'tbl_number_invitees' => 'Кол-во приглашенных',
			'tbl_number_survivors' => 'Кол-во дошедших',
			'tbl_perc_involved' => '% привлеченных',
			'tbl_number_trainees' => 'Кол-во выведенных на стажеровку',
			'tbl_perc_trainees' => '% выведенных на стажеровку',
			'tbl_result_sb' => 'Решение СБ',
			'tbl_deni_headman' => 'Отказ руководителя',
			'tbl_deni_candidate' => 'Отказ кандидата',
			'tbl_deni_recrut' => 'Отказ после стажировки'
        ];
        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($colHeaders as $k => $v) {
            $html .= '<th style="background-color: #d6dce4;">' . $v . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
		
		$summ_number_invitees1 =0;
		$summ_number_survivors1 =0;
		$summ_number_trainees1 =0;
		$summ_result_sb1 =0;
		$summ_deni_headman1 =0;
		$summ_deni_candidate1 =0;
		$summ_deni_recrut1 =0;
		foreach ($cityReports1 as $cityReport) {
			foreach ($regions as $region_key => $region_val) {
				if($region_key==$cityReport->region){
				$summ_number_invitees1 += $cityReport->number_invitees;
				$summ_number_survivors1 += $cityReport->number_survivors;
				$summ_number_trainees1 += $cityReport->number_trainees;
				$summ_result_sb1 +=  $cityReport->result_sb;
				$summ_deni_headman1 += $cityReport->deni_headman;
				$summ_deni_candidate1 += $cityReport->deni_candidate;
				$summ_deni_recrut1 += $cityReport->deni_recrut;
				$html .= '<tr>';
					$html .= '<th>'. $region_val .'</th>';
					$html .= '<th></th>';
					$html .= '<th>' . $cityReport->city . '</th>';
					$html .= '<th>' . $cityReport->number_invitees . '</th>';
					$html .= '<th>' . $cityReport->number_survivors . '</th>';
					$html .= '<th style="background-color: #d6dce4;">' . $cityReport->perc_involved . '</th>';
					$html .= '<th>' . $cityReport->number_trainees . '</th>';
					$html .= '<th style="background-color: #d6dce4;">' . (!empty($cityReport->perc_trainees)?$cityReport->perc_trainees:0) . '</th>';
					$html .= '<th>' . $cityReport->result_sb . '</th>';
					$html .= '<th>' . $cityReport->deni_headman . '</th>';
					$html .= '<th>' . $cityReport->deni_candidate . '</th>';
					$html .= '<th>' . $cityReport->deni_recrut . '</th>';
				$html .= '</tr>';
				}
			}
        }
		$html .= '<tr>';//#d6dce4
			$html .= '<th style="background-color: #fce4d6;">Итого менеджер Костикова О. В.</th>';
			$html .= '<th style="background-color: #fce4d6;"></th>';
			$html .= '<th style="background-color: #fce4d6;"></th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_number_invitees1 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_number_survivors1 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . round(($summ_number_survivors1!=0?$summ_number_survivors1/$summ_number_invitees1*100:0),0) . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_number_trainees1 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . round(($summ_number_trainees1!=0?$summ_number_trainees1/$summ_number_survivors1*100:0),0) . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_result_sb1 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_deni_headman1 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_deni_candidate1 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_deni_recrut1 . '</th>';
		$html .= '</tr>';
		
		$summ_number_invitees2 =0;
		$summ_number_survivors2 =0;
		$summ_number_trainees2 =0;
		$summ_result_sb2 =0;
		$summ_deni_headman2 =0;
		$summ_deni_candidate2 =0;
		$summ_deni_recrut2 =0;
		foreach ($cityReports2 as $cityReport) {
			foreach ($regions as $region_key => $region_val) {
				if($region_key==$cityReport->region){
				$summ_number_invitees2 += $cityReport->number_invitees;
				$summ_number_survivors2 += $cityReport->number_survivors;
				$summ_number_trainees2 += $cityReport->number_trainees;
				$summ_result_sb2 +=  $cityReport->result_sb;
				$summ_deni_headman2 += $cityReport->deni_headman;
				$summ_deni_candidate2 += $cityReport->deni_candidate;
				$summ_deni_recrut2 += $cityReport->deni_recrut;
				$html .= '<tr>';
					$html .= '<th>'. $region_val .'</th>';
					$html .= '<th></th>';
					$html .= '<th>' . $cityReport->city . '</th>';
					$html .= '<th>' . $cityReport->number_invitees . '</th>';
					$html .= '<th>' . $cityReport->number_survivors . '</th>';
					$html .= '<th style="background-color: #d6dce4;">' . $cityReport->perc_involved . '</th>';
					$html .= '<th>' . $cityReport->number_trainees . '</th>';
					$html .= '<th style="background-color: #d6dce4;">' . (!empty($cityReport->perc_trainees)?$cityReport->perc_trainees:0) . '</th>';
					$html .= '<th>' . $cityReport->result_sb . '</th>';
					$html .= '<th>' . $cityReport->deni_headman . '</th>';
					$html .= '<th>' . $cityReport->deni_candidate . '</th>';
					$html .= '<th>' . $cityReport->deni_recrut . '</th>';
				$html .= '</tr>';
				}
			}
        }
		$html .= '<tr>';
			$html .= '<th style="background-color: #fce4d6;">Итого менеджер Ибатулина Е.С.</th>';
			$html .= '<th style="background-color: #fce4d6;"></th>';
			$html .= '<th style="background-color: #fce4d6;"></th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_number_invitees2 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_number_survivors2 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . round(($summ_number_survivors2!=0?$summ_number_survivors2/$summ_number_invitees2*100:0),0) . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_number_trainees2 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . round(($summ_number_trainees2!=0?$summ_number_trainees2/$summ_number_survivors2*100:0),0) . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_result_sb2 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_deni_headman2 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_deni_candidate2 . '</th>';
			$html .= '<th style="background-color: #fce4d6;">' . $summ_deni_recrut2 . '</th>';
		$html .= '</tr>';
		$total_number_invitees = $summ_number_invitees1 + $summ_number_invitees2;
		$total_number_survivors = $summ_number_survivors1 + $summ_number_survivors2;
		$total_number_trainees = $summ_number_trainees1 + $summ_number_trainees2;
		$total_result_sb = $summ_result_sb1 + $summ_result_sb2;
		$total_summ_deni_headman = $summ_deni_headman1 + $summ_deni_headman2;
		$total_summ_deni_candidate = $summ_deni_candidate1 + $summ_deni_candidate2;
		$total_summ_deni_recrut = $summ_deni_recrut1 + $summ_deni_recrut2;
		$html .= '<tr>';
			$html .= '<th style="background-color: #d6dce4;">Итого</th>';
			$html .= '<th style="background-color: #d6dce4;"></th>';
			$html .= '<th style="background-color: #d6dce4;"></th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_number_invitees . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_number_survivors . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . round(($total_number_survivors!=0?$total_number_survivors/$total_number_invitees*100:0),0) . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_number_trainees . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . round(($total_number_trainees!=0?$total_number_trainees/$total_number_survivors*100:0),0) . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_result_sb . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_summ_deni_headman . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_summ_deni_candidate . '</th>';
			$html .= '<th style="background-color: #d6dce4;">' . $total_summ_deni_recrut . '</th>';
		$html .= '</tr>';	
		$html .= '</tbody>';
        $html .= '</table>';
		
		$file = "Отчет_по_городам_с_".$start_date."_по_".$end_date.".xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$file");
        return response($html)
                        ->header("Content-type", "application/vnd.ms-excel")
                        ->header("Content-Disposition", "attachment; filename=$file");
	}
	
}


