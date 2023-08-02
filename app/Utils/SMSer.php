<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

class SMSer {

    const SMS_LOGIN = 'finterra';
    const SMS_PASS = 'iatianymatonv';
    const HLR_LOGIN = 'finterra_hlr';
    const HLR_PASS = 'jnd43uya';
    const SMS_FEEDBACK_USER = 'finterra';
    const SMS_FEEDBACK_PASS = 'iatianymatonv';

//    const SMS_LOGIN = 'pdengi';
//    const SMS_PASS = 'c33367701511b4f6020ec61ded352059';
    /**
     * Отправить смс
     * @param string $telephone номер телефона
     * @param string $sms текст сообщения
     * @return boolean
     */
    static function send($telephone, $sms) {
		return SMSer::sendBySmsService($telephone, $sms);
        //return SMSer::sendBySmsFeedback($telephone, $sms, 'FinTerra');        
//        if (config('app.dev')) {
//            Log::info("SEND SMS: ", ['telephone' => $telephone, 'sms' => $sms]);
//            return true;
//        }
        /*if (SMSer::telephoneIsBeeline($telephone)) {
            $beesms = new BEESMS();
            $res = $beesms->post_message($sms, $telephone);
            $xml = new \SimpleXMLElement($res);
            if (isset($xml->errors)) {
                if (count($xml->errors->children()) > 0) {
                    return SMSer::makeSmsError($res);
                } else {
                    Log::info("SEND SMS: ", ['res' => $res]);
                    return true;
                }
            } else {
                return SMSer::makeSmsError($res);
            }
        } else {*/
            /*$res = SMSer::sendBySMSC($telephone, $sms);
            if ($res === FALSE) {
                return SMSer::makeSmsError($res);
            } else {
                Log::info("SEND SMS: ", ['res' => $res]);
                return true;
            }*/
        /*}*/
    }
    static function sendBySmsFeedback($telephone, $sms, $sender = false, $wapurl = false) {
        $host = "api.smsfeedback.ru";
        $fp = fsockopen($host, 80, $errno, $errstr);
        if (!$fp) {
            return "errno: $errno \nerrstr: $errstr\n";
        }
        fwrite($fp, "GET /messages/v2/send/" .
                "?phone=" . rawurlencode($telephone) .
                "&text=" . rawurlencode($sms) .
                ($sender ? "&sender=" . rawurlencode($sender) : "") .
                ($wapurl ? "&wapurl=" . rawurlencode($wapurl) : "") .
                "  HTTP/1.0\n");
        fwrite($fp, "Host: " . $host . "\r\n");
        fwrite($fp, "Authorization: Basic " . base64_encode(SMSer::SMS_FEEDBACK_USER . ":" . SMSer::SMS_FEEDBACK_PASS) . "\n");
        fwrite($fp, "\n");
        $response = "";
        while (!feof($fp)) {
            $response .= fread($fp, 1);
        }
        fclose($fp);
        list($other, $responseBody) = explode("\r\n\r\n", $response, 2);
        $res = (string) $responseBody;
        if (substr($res, 0, strpos($res, ';')) === 'accepted') {
            return true;
        } else {
            return false;
        }
    }
    
    static function makeSmsError($res) {
        Log::channel('sms')->error("SEND SMS: ", ['res' => $res]);
        \App\Spylog\Spylog::logError(json_encode(['file' => 'SMSer.send', 'res' => $res]), true);
        return false;
    }
	
	static function sendBySmsService($telephone, $sms){
        if (config('app.dev')) {
            Log::channel('sms')->info("SEND SMS: ", ['telephone' => $telephone, 'sms' => $sms]);
            return true;
        }
        //$url = 'http://192.168.35.84:90/api/json/sms/send?phone='.$telephone.'&sms_text='.$sms;
        $url = 'http://192.168.35.51/api/messages?phone=' . $telephone . '&text=' . $sms . '&response=json&type=1';
        $res = json_decode(file_get_contents($url), true);
        if(is_array($res) && array_key_exists('code', $res) && $res['code']==200){
            Log::channel('sms')->info("SEND SMS: ", ['telephone' => $telephone, 'sms' => $sms]);
            return true;
        } else {
            return SMSer::makeSmsError($res);
        }
    }
    /**
     * Проверяет принадлежит ли номер билайну
     * @param string $tel номер телефона
     * @return boolean
     */
    static function telephoneIsBeeline($tel) {
        if (substr($tel, 1, 4) == '9532' && (int) $tel[5] >= 0 && (int) $tel[5] < 3) {
            return true;
        }
        if (substr($tel, 1, 4) == '9510' && (int) $tel[5] >= 0 && (int) $tel[5] < 3) {
            return true;
        }
        if (in_array(substr($tel, 1, 3), ['903', '909', '963', '964', '965', '966', '967', '968', '905', '906', '962', '961', '960'])) {
            return true;
        }
        return false;
    }
    /**
     * Отправить смс через смс центр
     * @param string $telephone номер телефона
     * @param string $sms текст сообщения
     * @return type
     */
    static function sendBySMSC($telephone, $sms) {
        $sms = str_replace(array('%3A', '%2F'), array(':', '/'), urlencode(iconv('UTF-8', 'CP1251', $sms)));
        $url = 'https://' . config('admin.sms_server') . '/sys/send.php?login=' . SMSer::SMS_LOGIN . '&psw=' . SMSer::SMS_PASS . '&phones=' . $telephone . '&mes=' . $sms;
        $res = @file_get_contents($url);
        return $res;
    }

    /**
     * Отправить смс через симку в GoIp 
     * @param string $telephone
     * @param string $sms
     * @return boolean
     */
    static function sendByGoip($telephone, $sms) {
        $rand = rand();
        $url = 'http://192.168.1.116/default/en_US/sms_info.html';
        $line = '1'; // sim card to use in my case #1
        $telnum = $telephone; // phone number to send sms
        $smscontent = $sms; //your message
        $username = "admin"; //goip username
        $password = "admin"; //goip password
        $res = '';
        $fields_string = '';

        $fields = array(
            'line' => urlencode($line),
            'smskey' => urlencode($rand),
            'action' => urlencode('sms'),
            'telnum' => urlencode($telnum),
            'smscontent' => urlencode($smscontent),
            'send' => urlencode('send')
        );
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PORT, 80);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_exec($ch);
        curl_getinfo($ch);
        curl_close($ch);
        return $res;
    }
    /**
     * Отправить смс через goip сервер
     * @param string $telephone номер телефона
     * @param string $sms текст сообщения
     * @return boolean
     */
    static function sendByGoIpApi($telephone, $sms) {
        if (config('app.dev')) {
            Log::channel('sms')->info("SEND SMS: ", ['telephone' => $telephone, 'sms' => $sms]);
            return true;
        }
        $address = 'http://192.168.1.241/';
        $user = 'root';
        $pass = 'root';
        $provider = 3;
        $telephone = substr($telephone, 2);
        $url = $address . 'goip/en/dosend.php?USERNAME=' . $user . '&PASSWORD=' . $pass . '&smsprovider=' . $provider . '&smsnum=' . $telephone . '&method=2&Memo=' . urlencode($sms);

        $res = @file_get_contents($url);
        $start = strpos($res, 'resend.php?messageid=') + 21;
        $end = strpos($res, '&USERNAME=');
        $mid = substr($res, $start, $end - $start);
        $url2 = $address . 'goip/en/resend.php?messageid=' . $mid . '&USERNAME=' . $user . '&PASSWORD=' . $pass;
        $res = @file_get_contents($url2);
        if (strpos($res, 'All sendings done!') !== FALSE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Отправить смс через билайновский класс
     * @param string $telephone
     * @param string $sms
     * @return boolean
     */
    static function sendByBeeline($telephone, $sms) {
        $beesms = new BEESMS();
        $res = $beesms->post_message($sms, $telephone);
        return (count($res->errors) > 0) ? false : true;
    }

    static function sendHLR($telephone) {
        $url = 'https://smsc.ru/sys/send.php?login=' . SMSer::HLR_LOGIN . '&psw=' . SMSer::HLR_PASS . '&phones=' . $telephone . '&hlr=1&fmt=3&charset=utf-8';
        $res = @file_get_contents($url);
        Log::info('SMSer.hlr', ['res' => $res]);
        return $res;
    }

    static function checkStatus($telephone, $msg_id) {
        $url = 'https://smsc.ru/sys/status.php?login=' . SMSer::HLR_LOGIN . '&psw=' . SMSer::HLR_PASS . '&phone=' . $telephone . '&id=' . $msg_id . '&all=1&fmt=3&charset=utf-8';
        $res = @file_get_contents($url);
        Log::info('SMSer.check', ['res' => $res, 'url' => $url]);
        return $res;
    }

    /**
     * возвращает входящие смски из смс центра
     * @param integer $after_id идентификатор смсцентра для последней смски в базе, чтобы запрашивать смски которые пришли после нее
     * @return type
     */
    static function getInbox($after_id = null) {
        $url = 'https://smsc.ru/sys/get.php?get_answers=1&login=' . SMSer::SMS_LOGIN . '&psw=' . SMSer::SMS_PASS . '&fmt=3&charset=utf-8';
        if (!is_null($after_id)) {
            $url .= '&after_id=' . $after_id;
        }
        $res = @file_get_contents($url);
        return json_decode($res);
    }

}
