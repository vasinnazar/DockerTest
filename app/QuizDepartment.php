<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;

class QuizDepartment extends Model {

    protected $table = 'quiz_departments';
    protected $fillable = [
        'fio_ruk', 'fio_star_spec',
        'to_friends', 'to_friends_comment',
        'workplace', 'workplace_comment',
        'ruk1', 'ruk1_comment',
        'ruk2', 'ruk2_comment',
        'motivation', 'motivation_comment',
        'vzisk', 'vzisk_comment',
        'ovk', 'ovk_comment',
        'proverka', 'proverka_comment',
        'kadri', 'kadri_comment',
        'sales', 'sales_comment',
        'it', 'it_comment',
        'buh', 'buh_comment'
    ];
    public $fields = [
        'fio_ruk' => 'ФИО Вашего руководителя?',
        'fio_star_spec' => 'ФИО вашего старшего специалиста?',
        'to_friends' => 'Порекомендуете ли Вы нашу компанию своим друзьям в качестве работодателя?',
        'workplace' => 'Устраивает ли вас ваше рабочее место?',
        'ruk1' => 'Довольны ли вы взаимодействием со своим руководителем?',
        'ruk2' => 'У вас хороший руководитель?',
        'motivation' => 'Устраивает ли вас система мотивации?',
        'vzisk' => 'Довольны ли вы взаимодействием с отделом взыскания?',
        'ovk' => 'Довольны ли вы взаимодействием с отделом ОВК?',
        'proverka' => 'Довольны ли вы взаимодействием с отделом проверки?',
        'kadri' => 'Довольны ли вы взаимодействием с отделом кадров?',
        'sales' => 'Довольны ли вы взаимодействием с отделом продаж?',
        'it' => 'Довольны ли вы взаимодействием с отделом IT?',
        'buh' => 'Довольны ли вы взаимодействием с бухгалтерией?'
    ];

    public function getYesNoCommentFields() {
        return array_slice($this->fields, 2);
    }
    /**
     * доступен ли ответ на опрос для текущего пользователя
     * @return boolean
     */
    static function isAvailable() {
        if (is_null(Auth::user())) {
            return false;
        }
        if(!Auth::user()->hasPermission(Permission::makeName(Utils\PermLib::ACTION_OPEN, Utils\PermLib::SUBJ_QUIZ_DEPT))){
            return false;
        }
        $lastQuiz = QuizDepartment::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->first();
        if (is_null($lastQuiz)) {
            return true;
        }
        if (Utils\HelperUtil::DatesEqByYearAndMonth(Carbon::now(), $lastQuiz->created_at)) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * возвращает данные для отчета 
     * @param int $year год
     * @param int $month месяц
     * @param string $fio_ruk фио руководителя
     * @param string $fio_star_spec фио старшего спеца
     * @return type
     */
    static function getReport($year = '2017', $month = null, $fio_ruk = null, $fio_star_spec = null) {
        if (is_null($month)) {
            $month = '01';
            $dateStart = new Carbon($year . '-01-01');
            $dateEnd = Carbon::now()->day($dateStart->daysInMonth);
        } else {
            $dateStart = new Carbon($year . '-' . $month . '-01');
            $dateEnd = $dateStart->copy()->day($dateStart->daysInMonth);
        }
        $quizDepts = QuizDepartment::where('created_at', '>', $dateStart->format('Y-m-d'))->where('created_at', '<', $dateEnd->format('Y-m-d'));
        if (!is_null($fio_ruk) && $fio_ruk != 'all') {
            $quizDepts->where('fio_ruk', 'like', '%' . $fio_ruk . '%');
        }
        if (!is_null($fio_star_spec)) {
            $quizDepts->where('fio_star_spec', 'like', '%' . $fio_star_spec . '%');
        }
        $quizDepts = $quizDepts->get();
        $res = [
            'questions' => [],
            'comments' => []
        ];
        $quizdeptModel = new QuizDepartment();
        $yesNoFields = array_slice($quizdeptModel->fields, 2);
        foreach ($quizdeptModel->fields as $fk => $fv) {
            if ($fk == 'fio_ruk' || $fk == 'fio_star_spec') {
                continue;
            }
            $res['questions'][$fk] = ['label' => $fv, 'yes_count' => 0, 'no_count' => 0, 'comments_count' => 0, 'comments' => []];
        }
        foreach ($quizDepts as $qd) {
            $qda = $qd->toArray();
            foreach ($yesNoFields as $fk => $fv) {
                $comment_field_name = $fk . '_comment';
                if (array_key_exists($comment_field_name, $qda) && $qda[$comment_field_name] != '') {
                    $res['questions'][$fk]['comments'][] = $qda[$comment_field_name];
                    $res['questions'][$fk]['comments_count'] ++;
                }
                if (array_key_exists($fk, $qda)) {
                    if ($qda[$fk] == 1) {
                        $res['questions'][$fk]['yes_count'] ++;
                    } else {
                        $res['questions'][$fk]['no_count'] ++;
                    }
                }
            }
        }
        return $res;
    }

}
