<?php

namespace App\Utils;
/**
 * Класс для хранения и получения текстовых идентификаторов разрешений
 */
class PermLib extends \Illuminate\Support\Facades\Facade {

    const ACTION_SELECT = 'select';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_OPEN = 'open';
    const ACTION_CREATE = 'create';
    const COND_ALL = 'all';
    const COND_SUBDIV = 'subdivision';
    const COND_USER = 'user';
    const TIME_ALL = 'ever';
    const TIME_YEAR = 'year';
    const TIME_DAY = 'day';
    const TIME_HOUR = 'hour';
    const SUBJ_ORDERS = 'orders';
    const SUBJ_CLAIMS = 'claims';
    const SUBJ_LOANS = 'loans';
    const SUBJ_QUIZ_DEPT = 'quiz_departments';
    const SUBJ_QUIZ_DEPT_REPORT = 'quiz_departments_report';
    const SUBJ_ADMINPANEL = 'adminpanel';
    const SUBJ_REMREQ = 'remove_requests';
    const SUBJ_RNKO = 'rnko';
    const SUBJ_DEBTORS = 'debtors';
    const SUBJ_DEBTOR_TRANSFER = 'debtor_transfer';
    const SUBJ_SALES_REPORT = 'sales_report';
    const SUBJ_NO_SUBDIVS_REPORT = 'absent_subdivisions_report';
    const SUBJ_USER_TESTS = 'user_tests';
    const SUBJ_USER_TESTS_EDITOR = 'user_tests_editor';
    const SUBJ_ADVANCE_REPORTS = 'advance_reports';
    const SUBJ_USERPHOTOS = 'user_photos';
    const SUBJ_TICKETS = 'tickets';
    const SUBJ_REPAYMENTS = 'repayments';
    const SUBJ_SMSINBOX = 'sms_inbox';
    const SUBJ_WORKTIMES = 'worktimes';
    const SUBJ_REPORTS = 'reports';
    const SUBJ_PHONE_CALLS = 'phone_calls';
    const SUBJ_SUBDIVISIONS = 'subdivisions';
    const SUBJ_SUBDIVISION_STOCK_SETTINGS = 'subdivision_stock_settings';
	const SUBJ_CANDIDATE_LIST = 'candidate_list';
    
    protected static function getFacadeAccessor() { return 'permlib'; }

    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    static function getSubjects($invert = false) {
        return PermLib::getConstantsByPrefix('SUBJ', $invert);
    }

    static function getActions($invert = false) {
        return PermLib::getConstantsByPrefix('ACTION', $invert);
    }

    static function getConditions($invert = false) {
        return PermLib::getConstantsByPrefix('COND', $invert);
    }

    static function getTime($invert = false) {
        return PermLib::getConstantsByPrefix('TIME', $invert);
    }

    static function getConstantsByPrefix($prefix, $invert = false) {
        $consts = PermLib::getConstants();
        $res = [];
        foreach ($consts as $k => $v) {
            if (str_contains($k, $prefix)) {
                if ($invert) {
                    $res[$v] = $k;
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }

}
