<?php

namespace App;

use Artisaninweb\SoapWrapper\Facades\SoapWrapper;
use SoapClient;
use Auth;
use Log;
use App\Spylog\Spylog;
use Carbon\Carbon;
use Session;
use App\Utils\HelperUtil;
use App\Utils\SMSer;

class MySoap {

    const MSG_NORESPONSE = 'Нет ответа от 1С.';
    const MSG_BADJSON = 'Неверный ответ от сервера.';
    const MSG_EXISTS = 'Уже существует в 1С.';
    const ITEM_CLAIM = 0;
    const ITEM_LOAN = 1;
    const ITEM_RKO = 2;
    const ITEM_REP_CLAIM = 3;
    const ITEM_REP_DOP = 4;
    const ITEM_REP_PEACE = 5;
    const ITEM_REP_SUZ = 6;
    const ITEM_REP_CLOSING = 7;
    const ITEM_PKO = 8;
    const ITEM_NPF = 9;
    const ITEM_SFP = 10;
    const ITEM_RKO_PKO = 11;
    const ITEM_ISSUE_CLAIM = 12;
    const ITEM_ADVANCE_REPORT = 13;

    static function init() {
        SoapWrapper::add(function ($service) {
            $service
                    ->cache(WSDL_CACHE_NONE)
                    ->name(config('1c.service'))
                    ->wsdl(config('1c.url'))
                    ->trace(true)
                    ->options(['login' => config('1c.login'), 'password' => config('1c.password')]);
        });
    }

    static function get_1c_url($url) {
        $base_url = config('admin.server_1c');
        if (empty(config('admin.server_1c'))) {
            if (config('app.dev')) {
                $base_url = '192.168.1.47:8080/1c47';
            } else {
                $base_url = '192.168.1.21:443/PersonaArea1';
            }
        }
//        if (config('app.version_type') == 'debtors' && !config('app.dev')) {
//            if (!MySoap::checkWsdl('http://192.168.1.31/11SPD34/ws/ARM/?wsdl&AspxAutoDetectCookieSupport=1')) {
//                \PC::debug($base_url);
//                $base_url = '192.168.1.34:81/PersonaArea1';
//            }
//        }
        return $base_url . $url;
    }

    /**
     * генерирует ответный массив с переданным текстом ошибки
     * @param type $error
     * @return type
     */
    static function error($error) {
        return ['res' => 0, 'msg_err' => $error];
    }

    /**
     * Парсит пришедший от 1С json и возвращает либо ошибку либо массив полученный из json
     * @param type $response ответ, пришедший от 1с
     * @param boolean $err_on_empty_json если тру, то выдавать ошибку в случае если на выходе получился пустой массив
     * @return type
     */
    static function parseResponse($response, $err_on_empty_json = FALSE) {
        //************************************
        //безумный способ экранировать кавычки
//        $str = $response->return;
        $str = $response;
//        Log::info('JSON FROM 1C: ' . $str);
        if ($str == 'false') {
            return MySoap::error(MySoap::MSG_BADJSON);
        }
        $str = str_replace('}"closing', '},"closing', $str);
        $str = str_replace('}"suz', '},"suz', $str);
        $str = str_replace('"":"",', '', $str);
        $str = str_replace('\\', '&#92;', $str);
        $str = str_replace(',}', "}", $str);
        $str = str_replace(',]', "]", $str);
        $str = str_replace('\\t', '', $str);
        $str = preg_replace('/([^\pL\pN\pP\pS\pZ])/u', '', $str);

        $str = str_replace('{"', "{@", $str);
        $str = str_replace('":"', "@:@", $str);
        $str = str_replace('":{', "@:{", $str);
        $str = str_replace('":[', "@:[", $str);
        $str = str_replace('},"', "},@", $str);
        $str = str_replace('],"', "],@", $str);
        $str = str_replace('","', "@,@", $str);
        $str = str_replace('"}', "@}", $str);

        $str = str_replace('"', '\"', $str);
        $str = str_replace("'", "\'", $str);

        $str = str_replace("{@", '{"', $str);
        $str = str_replace("@:@", '":"', $str);
        $str = str_replace("@:{", '":{', $str);
        $str = str_replace("@:[", '":[', $str);
        $str = str_replace("},@", '},"', $str);
        $str = str_replace("],@", '],"', $str);
        $str = str_replace("@,@", '","', $str);
        $str = str_replace("@}", '"}', $str);

//        Log::info($str);
//        \PC::debug(substr($str,0,5000));
        //************************************
        try {
            $json = json_decode($str, true);
        } catch (Exception $exc) {
            Log::error('MySoap.parseResponse', ['got from 1c' => $str]);
            return MySoap::error(MySoap::MSG_BADJSON);
        }
        if ($err_on_empty_json && count($json) == 0) {
            Log::error('MySoap.parseResponse.EMPTY_JSON', ['got from 1c' => $str]);
            return MySoap::error(MySoap::MSG_BADJSON);
        }
        $jsonlasterr = json_last_error_msg();
        if ($jsonlasterr != 'No error') {
            Log::error(json_last_error_msg());
        }
        if (!is_null($json) && !array_key_exists('res', $json)) {
            $json['res'] = 1;
        }
//        Log::info('MySoap.parseResponse', ['json' => $json]);
        return $json;
    }

    /**
     * Проверяет есть ли доступ до указанного URL
     * @param type $url
     * @return type
     */
    static function urlExists($url) {
        $file_headers = @get_headers($url);
        return (is_array($file_headers)) ? true : false;
    }

    /**
     * Проверяет количество соединений в базе и при превышении порога переключает на другой 1с сервер и отправляет смс
     */
    static function checkMysql() {
        $alert_threads_num = 45;
        $lastCheckDate = new Carbon(config('admin.last_mysql_check_date'));
        $now = Carbon::now();
        if ($lastCheckDate->addSeconds(30)->lte($now)) {
            $configData = ['last_mysql_check_date' => $now->format('Y-m-d H:i:s')];
            $threadsNum = HelperUtil::GetMysqlThreadsNum();
            if ($threadsNum > $alert_threads_num) {
                $sms = $configData['last_mysql_check_date'] . ' MySQL threads: ' . $threadsNum;
                if (config('admin.auto_change_server_1c') == 1) {
                    $configData['server_1c'] = MySoap::getNextServer1c();
                    $configData['auto_change_server_1c'] = 0;
                    $sms .= '. Переключаюсь на: ' . $configData['server_1c'];
                }
                if (!config('app.dev')) {
                    SMSer::send(config('admin.alert_phone'), $sms);
                }
                Log::warning('MySoap.checkMysql', ['sms' => $sms, 'configdata' => $configData]);
            }
            HelperUtil::UpdateConfig('admin', $configData);
        }
    }

    /**
     * проверяет урл на доступность
     * @param string $url
     * @return boolean
     */
    static function checkWsdl($url) {
        $handle = curl_init($url);
        \PC::debug($url, 'url to curl');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_USERPWD, config('1c.login') . ":" . config('1c.password'));
        $res = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        \PC::debug([$res, $httpCode]);
        if ($httpCode == 200) {
            return true;
        } else {
            return false;
        }
    }

    static function getNextServer1c() {
        $servers_list = config('admin.servers_1c_list');
        $cur_id = array_search(config('admin.server_1c'), $servers_list);
        $next_id = $cur_id + 1;
        if ($next_id >= count($servers_list)) {
            $next_id = 0;
        }
        return $servers_list[$next_id];
    }

    /**
     * Запрос к 1С
     * @param string $name
     * @param array $params
     * @param boolean $json_resp
     * @param boolean $err_on_empty_json
     * @param array $connection
     * @param boolean $log
     * @param boolean $isXml
     * @param string $tableName
     * @param int $docId
     * @return type возвращает массив типа ["res"=>"0/1", ...]
     */
    static function call1C($name, $params, $json_resp = true, $err_on_empty_json = FALSE, $connection = null, $log = true, $isXml = false, $tableName = null, $docId = null) {

//        MySoap::checkMysql();
        $login = config('1c.login');
        $url = MySoap::get_1c_url(config('1c.url'));
        $pass = config('1c.password');

        if (is_array($connection)) {
            if (array_key_exists('url', $connection)) {
                $url = (array_key_exists('absolute_url', $connection)) ? $connection['url'] : MySoap::get_1c_url($connection['url']);
            }
            if (array_key_exists('password', $connection)) {
                $pass = $connection['password'];
            }
            if (array_key_exists('login', $connection)) {
                $login = $connection['login'];
            }
        }

        try {
            foreach ($params as $k => $v) {
                if (is_null($v)) {
                    $params[$k] = "";
                }
            }
            \PC::debug($params, 'call1c_params');
            $start_req_date = Carbon::now();
            $client = new SoapClient('http://' . $url, ['login' => $login, 'password' => $pass, 'cache_wsdl' => WSDL_CACHE_NONE, 'trace' => 1]);
            $response = $client->__soapCall($name, [$params]);
            \PC::debug($response, 'MySoap.call1c.response');
//            \PC::debug($client->__getLastRequest(),'lastresponse');
//            Log::info('MySoap.call1c', ['name' => $name, 'params' => $params, 'response' => $response]);

            $end_req_date = Carbon::now();

            if ($log) {
                Spylog::log(Spylog::ACTION_CALL1C, $tableName, $docId, json_encode([
                    '_start_req_date' => $start_req_date->format('Y-m-d H:i:s'),
                    '_end_req_date' => $end_req_date->format('Y-m-d H:i:s'),
                    '_req_time' => $start_req_date->diffInSeconds($end_req_date),
                    'name' => $name,
                    'params' => $params,
                    'response' => ($json_resp) ? MySoap::parseResponse($response->return, $err_on_empty_json) : $response]));
            } else if (!$isXml) {
                Spylog::log(Spylog::ACTION_CALL1C, $tableName, $docId, json_encode([
                    '_start_req_date' => $start_req_date->format('Y-m-d H:i:s'),
                    '_end_req_date' => $end_req_date->format('Y-m-d H:i:s'),
                    '_req_time' => $start_req_date->diffInSeconds($end_req_date),
                    'name' => $name,
                    'params' => $params
                ]));
            }
        } catch (\SoapFault $exc) {
            \PC::debug($exc, 'SoapFault');
            Log::error('MySoap.call1c.SoapFault', ['name' => $name, 'params' => $params, 'exception' => $exc]);
            Spylog::logError(json_encode(['name' => $name, 'params' => $params, 'exception' => $exc]));
            Session::flash('error_1c', json_encode(['name' => $name, 'params' => $params, 'exception' => $exc]));
            return MySoap::error(MySoap::MSG_NORESPONSE);
        } catch (\Exception $exc) {
            \PC::debug($exc, 'Exception');
            Log::error('MySoap.call1c.Exception', ['name' => $name, 'params' => $params, 'exception' => $exc]);
            Spylog::logError(json_encode(['name' => $name, 'params' => $params, 'exception' => $exc]));
            Session::flash('error_1c', json_encode(['name' => $name, 'params' => $params, 'exception' => $exc]));
            return MySoap::error(MySoap::MSG_NORESPONSE);
        }
        if (is_null($response) || !array_key_exists('return', (array)$response)) {
            Log::error('MySoap.call1c.NO_RESPONSE', ['name' => $name, 'params' => $params, 'response' => $response]);
            Spylog::logError(json_encode(['name' => $name, 'params' => $params, 'response' => $response]));
            Session::flash('error_1c', json_encode(['name' => $name, 'params' => $params]));
            return MySoap::error(MySoap::MSG_NORESPONSE);
        }

        if ($json_resp) {
            return MySoap::parseResponse($response->return, $err_on_empty_json);
        } else {
            if (is_null($response->return) || strpos($response->return, "Ошибка") !== FALSE || ($response->return == "false" && ((isset($response->result) && $response->result == "false"))) || $response->return == '') {
                Session::flash('error_1c', json_encode(['name' => $name, 'params' => $params, 'response' => $response]));
                Spylog::logError(json_encode(['name' => $name, 'params' => $params, 'response' => $response]));
                return ['res' => 0, 'value' => $response->return];
            } else {
                return ['res' => 1, 'value' => (array_key_exists('result', $response)) ? $response->result : $response->return];
            }
        }
    }

    /**
     * получает контрагента по паспорту и возвращает его, либо пустой масив если не найден
     * @param array $params ['series'=>'','number' => '', 'old_series' => '', 'old_number' => '']
     * @return array
     */
    static function passport($params) {
        if (array_key_exists('claim_id', $params)) {
            $params['type'] = 'CheckK';
            $xml = MySoap::sendExchangeArm(MySoap::createXML($params));
            $data = MySoap::xmlToArray($xml);
            foreach ($data as $k => $v) {
                if (is_array($data[$k])) {
                    $data[$k] = '';
                }
            }
            $data['res'] = $data['result'];
            return $data;
        } else {
            $params['type'] = 'CheckK';
            $xml = MySoap::sendExchangeArm(MySoap::createXML($params));
            $data = MySoap::xmlToArray($xml);
            foreach ($data as $k => $v) {
                if (is_array($data[$k])) {
                    $data[$k] = '';
                }
            }
            $data['res'] = $data['result'];
            return $data;
//            return MySoap::call1C('CheckK', $params);
        }
    }

    static function SOAP($id) {
        return MySoap::call1C('SOAP', ['id' => $id]);
    }

    /**
     * создание заявки
     * @param array $params
     * @return type
     */
    static function createClaim($params) {
        return MySoap::call1C('CreateK', $params, true);
    }

    /**
     * создание кредитника
     * @param array $params
     * @return type
     */
    static function createLoan($params) {
        $res = MySoap::call1C('CreateСreditAgreement', $params, true, true);
        if (!array_key_exists('loan_id_1c', $res)) {
            return MySoap::error(MySoap::MSG_EXISTS);
        }
        return $res;
    }

    /**
     * редактирование кредитника
     * @param array $params
     * @return type
     */
    static function updateLoan($params) {
        return MySoap::call1C('CreateСreditAgreement', $params, true, true);
    }

    /**
     * зачисление средств клиенту по кредитнику
     * @param array $params
     * @param boolean $oncard
     * @return type
     */
    static function enrollLoan($params, $oncard) {
        return MySoap::call1C(($oncard) ? 'Create_order_card' : 'Create_order', $params);
    }

    /**
     * проверяет наличие промокода в 1с
     * @param type $params
     * @return type
     */
    static function checkPromocode($params) {
        return MySoap::call1C('checkPromocode', $params);
    }

    /**
     * создает или обновляет ордер в 1с и возвращает номер ордера
     * @param type $params
     * @return type
     */
    static function createOrder($params) {
        return MySoap::call1C('Create_KO', $params, false);
    }

    /**
     * создает или обновляет допник в 1с и возвращает номер договора
     * @param type $params
     * @param type $repaymentType
     * @return type
     */
    static function createRepayment($params, $repaymentType = null) {
        return MySoap::call1C('CreateK_other', $params, false);
    }

    static function createClaimRepayment($params, $repaymentType = null) {
        return MySoap::call1C('CreateZPP', $params, false);
    }

    static function createPeaceRepayment($params, $repaymentType = null) {
//        if(config('app.dev')){
        $params['type'] = 'CreateMS';
        return MySoap::sendExchangeArm(MySoap::createXML($params));
//        } else {
//            return MySoap::call1C('CreateMS', $params, false);
//        }
    }

    static function createClosingRepayment($params, $repaymentType = null) {
        return MySoap::call1C('CreateClose', $params, false);
    }

    static function removeItem($docType, $id_1c, $customer_id_1c, $base_doc_type = null, $base_doc_number = null) {
        $data = ['type' => 'Delete', 'Number' => $id_1c, 'doc_type' => $docType, 'customer_id_1c' => $customer_id_1c];
        if (!is_null($base_doc_type)) {
            $data['base_doc_type'] = $base_doc_type;
        }
        if (!is_null($base_doc_number)) {
            $data['base_doc_number'] = $base_doc_number;
        }
        return MySoap::sendExchangeArm(MySoap::createXML($data));
//        return MySoap::call1C('Delete', ['Number' => $id_1c, 'doc_type' => $docType, 'customer_id_1c' => $customer_id_1c], false);
    }

    static function getLoanRepayments($series, $number) {
//        return MySoap::call1C('LoanK', ['passport_series' => $series, 'passport_number' => $number]);
        /**
         * ХАК ДЛЯ ПОИСКА ЗАЯВОК (приходит без одной скобки, поэтому добавляю и потом парсю)
         */
        $res1c = MySoap::call1C('LoanK', ['passport_series' => StrUtils::parsePhone($series), 'passport_number' => StrUtils::parsePhone($number)], false);
        if (!is_null($res1c) && array_key_exists('value', $res1c)) {
            if (!strpos($res1c['value'], 'loan') && strpos($res1c['value'], 'claim') > 0) {
                $res1c['value'] .= '}';
            }
            \PC::debug(MySoap::parseResponse($res1c['value']), 'parseresponse');
            return MySoap::parseResponse($res1c['value']);
        } else {
            return ['res' => 0, 'err_msg' => 'Не пришло данных'];
        }
    }

    static function addSuzPayment($params) {
        return MySoap::call1C('EditSUZ', $params, false);
    }

    static function createSuzRepayment($params) {
        return MySoap::call1C('EditSUZ', $params);
    }

    static function getSubdivisionsList() {
        $res1c = MySoap::call1C('Loan_sub', []);
//        Log::info('subdivs', ['res1c' => $res1c['value']]);
        return MySoap::parseResponse($res1c);
    }

    static function claimForRemove($doc_type, $id_1c, $date) {
        return MySoap::call1C('', ['doc_type' => $doc_type, 'id_1c' => $id_1c, 'date' => $date]);
    }

    static function getPassportsByFio($fio) {
        $res1c = MySoap::call1C('Loan_K_FIO', ['Fio' => $fio]);
        return $res1c;
    }

    static function terminalPayment($params) {
        return MySoap::call1C('AddTerminal', $params, false, false, ['url' => config('1c.terminal_url'), 'pass' => config('1c.terminal_password')]);
    }

    static function getLoanRepaymentsByNumber($id_1c) {
        $loan = Loan::where('id_1c', $id_1c)->first();
        if (is_null($loan)) {
            return ['res' => 0, 'err_msg' => 'Не найден кредитник'];
        }
        return MySoap::call1C('LoanK_number', ['Number' => $id_1c, 'customer_id_1c' => $loan->claim->customer->id_1c], true, false, ['url' => config('1c.loank_number_url'), 'pass' => config('1c.password')]);
//        return MySoap::call1C('LoanK_number', ['Number' => $id_1c], true, false);
//        if (!is_null($res1c) && array_key_exists('value', $res1c)) {
//            if (!strpos($res1c['value'], 'loan')) {
//                $res1c['value'] .= '}';
//            }
//            return MySoap::parseResponse($res1c['value']);
//        } else {
//            return ['res' => 0, 'err_msg' => 'Не пришло данных'];
//        }
    }

    static function getLoanRepaymentsByNumber2($id_1c, $customer_id_1c) {
        return MySoap::call1C('LoanK_number', ['Number' => $id_1c, 'customer_id_1c' => $customer_id_1c], true, false, ['url' => config('1c.loank_number_url'), 'pass' => config('1c.password')]);
    }

    static function addCustomer($params) {
        return MySoap::call1C('CreateFL', $params, false);
    }

    static function addNPF($params) {
        return MySoap::call1C('CreateNPF', $params, false, false, ['url' => config('1c.npf_url')]);
    }

    static function addDailyCashReport($params) {
        return MySoap::call1C('CreateDailyCashReport', $params, false);
    }

    static function getDebtByNumber($telephone) {
        return MySoap::call1C('GetDebtByNumber', ['Number' => $telephone], false, false, ['url' => config('1c.debt_url'), 'pass' => config('1c.debt_password')]);
    }

    static function getPaysheet($start, $end, $user_id_1c) {
        $connection = [
            'url' => '192.168.1.77:81/Pay/ws/myWebService1?wsdl',
            'login' => 'ИТ',
            'password' => 'iatianymatonv',
            'absolute_url' => true
        ];
        return MySoap::call1C('get_prime', ['Responsible' => $user_id_1c,'DateStart' => $start, 'DateFinish' => $end], false, false, $connection);
//        return MySoap::call1C('GetSheet', ['DateStart' => $start, 'DateFinish' => $end, 'Responsible' => $user_id_1c], false, false, ['url' => config('1c.paysheet_url'), 'pass' => config('1c.paysheet_password')]);
    }

    static function getCashbookBalance($date, $subdivision_id_1c) {
        return MySoap::call1C('GetSubdivisionCash', ['date' => $date, 'subdivision_id_1c' => $subdivision_id_1c], true, false, ['url' => config('1c.cashbook_url'), 'pass' => config('1c.cashbook_password')]);
    }

    /**
     * 
     * @param type $params [date_start,date_finish,subdivision_id_1c,user_id_1c=null]
     * @return type
     */
    static function getDocsRegister($params) {
        return MySoap::call1C('GetDocsRegister', $params, true, true, ['url' => config('1c.docsregister_url')]);
    }

    static function getDailyCashReport($date, $subdivision_id_1c) {
        return MySoap::call1C('GetDailyCashReport', ['date' => $date, 'subdivision_id_1c' => $subdivision_id_1c], false, false, ['url' => config('1c.dailycashreport_url')]);
    }

    static function saveMaterialsClaim($params) {
//        \PC::debug($params);
//        return ['res'=>'0'];
        return MySoap::call1C('SaveMatClaim', $params, true, true, ['url' => config('1c.matclaim_url')]);
    }

    static function saveWorkTime($params) {
        return MySoap::call1C('CreateWorkTime', $params, false, false, ['url' => config('1c.worktime_url')]);
    }

    static function enrollTerminal($loan_id_1c, $created_at, $user_id_1c, $subdivision_id_1c) {
        return MySoap::call1C('Create_order', [
                    "loan_id_1c" => $loan_id_1c,
                    "created_at" => $created_at,
                    "user_id_1c" => $user_id_1c,
                    "subdivision_id_1c" => $subdivision_id_1c
                        ], true, true, ['url' => config('1c.order_terminal_url')]);
    }

    static function sendToAutoApprove($xml) {
        return MySoap::call1C('Auto_okay', ['params' => $xml], false, false, ['url' => config('1c.auto_approve_url')]);
    }

    static function sendXML($xml, $log = true, $module_1c = 'IAmMole', $connection = null) {
        //типы операций
        //0 - вернуть все суммы по договору и контрагенту
        //1 - получить суммы приходников добавленных без создания договора
        //2 - списать суммы приходников добавленных без создания договора
        //3 - получить ордеры
        //4 - получить суммы задолженности
        //5
        //6
        //7 - получить оборотно сальдовую ведомость
        //8 - получить план
        //9 - получить\записать данные по обзвону
        //10 - отчет по продажам
        $start_req = Carbon::now();
        $connectionParams = [
            'url' => config('1c.mole_url')
        ];
        if (is_array($connection)) {
            $connectionParams = $connection;
        } else if (!is_null($connection)) {
            $connectionParams['url'] = $connection;
        }
        $res = MySoap::call1C($module_1c, ['params' => $xml], false, false, $connectionParams, false, true);
        $end_req = Carbon::now();
        \PC::Debug($res);
        if (array_key_exists('value', $res)) {
            $resXml = simplexml_load_string($res["value"]);
            if (!$resXml->result) {
                Spylog::logError(json_encode([
                    '_start_req_date' => $start_req->format('Y-m-d H:i:s'),
                    '_end_req_date' => $end_req->format('Y-m-d H:i:s'),
                    '_req_time' => $start_req->diffInSeconds($end_req),
                    "name" => $module_1c,
                    'params' => json_decode(json_encode($xml), true),
                    'response' => $res
                ]));
            } else {
                if ($log) {
                    Spylog::log(Spylog::ACTION_CALL1C, null, null, json_encode([
                        '_start_req_date' => $start_req->format('Y-m-d H:i:s'),
                        '_end_req_date' => $end_req->format('Y-m-d H:i:s'),
                        '_req_time' => $start_req->diffInSeconds($end_req),
                        "name" => $module_1c,
                        'params' => htmlspecialchars($xml),
                        'response' => htmlspecialchars($res["value"])
                    ]));
                }
            }
        } else {
            Spylog::logError(json_encode([
                '_start_req_date' => $start_req->format('Y-m-d H:i:s'),
                '_end_req_date' => $end_req->format('Y-m-d H:i:s'),
                '_req_time' => $start_req->diffInSeconds($end_req),
                "name" => $module_1c,
                'params' => htmlspecialchars($xml),
                'response' => htmlspecialchars((string) $res)
            ]));
            $resXml = simplexml_load_string(MySoap::createXML(['result' => 0, 'error' => 'Нет ответа']));
        }
        return $resXml;
    }

    static function createXML($params) {
        $xml = new \SimpleXMLElement('<root/>');
        MySoap::arrayToXML($params, $xml);
        return $xml->asXML();
    }

    static function arrayToXML($data, &$xml_data, &$table_node = null) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key; //для массивов с числовым ключом
                }
                $subnode = $xml_data->addChild($key);
                if (is_null($table_node) && $subnode->getName() != 'root') {
                    $subnode->addAttribute('table', '1');
                }
                MySoap::arrayToXML($value, $subnode, $xml_data);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    static function xmlToArray($xmlObject, $out = array()) {
        foreach ((array) $xmlObject as $index => $node)
            $out[$index] = ( is_object($node) ) ? MySoap::xmlToArray($node) : (string) $node;
        return $out;
    }

    static function sendArmOut($xml, $log = true) {
        return MySoap::sendXML($xml, $log, 'armout', config('1c.mole_url_out'));
    }

    static function sendExchangeArm($xml, $log = true) {
        return MySoap::sendXML($xml, $log, 'Main', config('1c.exchange_arm'));
    }

}
