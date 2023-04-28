<?php

namespace App\Utils;

use GuzzleHttp\Client;
use Log;
use Illuminate\Support\Facades\DB;
use App\Utils\SMSer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HelperUtil {

    static function FtpFileExists($file_path, $fs_name = 'ftp') {
        $conn_id = ftp_connect(config('filesystems.disks.' . $fs_name . '.host'));
        $login_result = ftp_login($conn_id, config('filesystems.disks.' . $fs_name . '.username'), config('filesystems.disks.' . $fs_name . '.password'));
        $res = ftp_size($conn_id, config('filesystems.disks.' . $fs_name . '.root') . $file_path);
        ftp_close($conn_id);
        if ($res != -1) {
            return true;
        } else {
            return false;
        }
    }

    static function FtpFolderExists($folder, $fs_name = 'ftp') {
        $user = config('filesystems.disks.' . $fs_name . '.username');
        $pass = config('filesystems.disks.' . $fs_name . '.password');
        $ftp = config('filesystems.disks.' . $fs_name . '.host');
        $root = config('filesystems.disks.' . $fs_name . '.root');
        $str = 'ftp://' . $user . ':' . $pass . '@' . $ftp . $root . '/' . $folder;
        return is_dir($str);
    }

    static function FtpGetFile($path, $fs_name = 'ftp') {
        $user = config('filesystems.disks.' . $fs_name . '.username');
        $pass = config('filesystems.disks.' . $fs_name . '.password');
        $ftp = config('filesystems.disks.' . $fs_name . '.host');
        $root = config('filesystems.disks.' . $fs_name . '.root');
        return file_get_contents('ftp://' . $user . ':' . $pass . '@' . $ftp . $root . '/' . $path);
    }

    static function FtpFolderList($folder, $fs_name = 'ftp') {
        $conn_id = ftp_connect(config('filesystems.disks.' . $fs_name . '.host'));
        $login_result = ftp_login($conn_id, config('filesystems.disks.' . $fs_name . '.username'), config('filesystems.disks.' . $fs_name . '.password'));
        $contents = ftp_nlist($conn_id, config('filesystems.disks.' . $fs_name . '.root') . '/' . $folder . '/');
        ftp_close($conn_id);
        return $contents;
    }

    static function GenerateTerminalPromocode($maxval = 17, $unique = false) {
        $val = $maxval;
        $maxlength = 4;
        for ($i = 0; $i < $maxlength; $i++) {
            $nums[$i] = 0;
        }
        for ($i = 0; $i < $maxlength; $i++) {
            $diff = $maxval - HelperUtil::GetPromocodeSum($nums);
            $nums[$i] = rand((($i == 0) ? 0 : 5), ($diff > 9) ? 9 : $diff);
            if (HelperUtil::GetPromocodeSum($nums) > $maxval) {
                $nums[$i] = $diff;
            }
        }
        $promocode = implode('', $nums);
        //если промокод должен быть уникальным, то проверять его по базе промокодов
        if ($unique) {
            if (\App\Promocode::where('number', $promocode)->count() > 0) {
                return HelperUtil::GenerateTerminalPromocode($maxval, $unique);
            }
        }
        return $promocode;
    }

    static function GetPromocodeSum($nums, $num = null) {
        $count = (is_null($num)) ? count($nums) : $num;
        $sum = 0;
        for ($i = 0; $i < $count; $i++) {
            $sum += $nums[$i];
        }
        return $sum;
    }

    /**
      make an http POST request and return the response content and headers
      @param string $url    url of the requested script
      @param array $data    hash array of request variables
      @return returns a hash array with response content and headers in the following form:
      array ('content'=>'<html></html>'
      , 'headers'=>array ('HTTP/1.1 200 OK', 'Connection: close', ...)
      )
     */
    static function SendPostRequest($url, $data, $headers = null, $username = null, $password = null, $reqtype = 'POST') {
        $header_str = 'Content-type: application/x-www-form-urlencoded\r\n';
        if (!is_null($headers)) {
            foreach ($headers as $k => $v) {
                $header_str.= $k . ':' . $v . '\r\n';
            }
        }
        if (!is_null($username)) {
            $header_str .= "Authorization: Basic " . base64_encode('"' . $username . ':' . ((is_null($password)) ? '' : $password) . '"');
        }
        Log::info('helperutil', ['headers' => $header_str]);
        $options = array(
            'http' => array(
                'header' => $header_str,
                'method' => $reqtype,
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */
            Log::error('SendPostRequest', ['result' => $result]);
        }

        return $result;
    }

    static function SendPostByCurl($url, $data = null, $headers = null, $username = null, $password = null) {
        $hdrs = [
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded"
        ];
        if (!is_null($headers)) {
            foreach ($headers as $k => $v) {
                $hdrs[] = $k . ': ' . $v;
            }
        }
        $postfields = '';
        if (!is_null($data)) {
            foreach ($data as $k => $v) {
                $postfields.=$k . '=' . $v;
            }
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => $hdrs,
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if (!$err) {
            return $response;
        } else {
            Log::error('HELPER CURL', ['err' => $err]);
        }
        return null;
    }

    static function SendPost2($url, $data) {
        $sPD = ""; // The POST Data
        foreach ($data as $k => $v) {
            $sPD .= $k . '=' . $v . '&';
        }
        $sPD = rtrim($sPD, '&');
        $aHTTP = array(
            'http' => // The wrapper to be used
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $sPD
            )
        );
        $context = stream_context_create($aHTTP);
        $contents = @file_get_contents($url, false, $context);
        return $contents;
    }

    static function GetMysqlThreadsNum() {
        $mysqlThreads = DB::select("show status where `variable_name` = 'Threads_connected'");
        return $mysqlThreads[0]->Value;
    }

    /**
     * Отдает на скачивание переданную хтмл строку как эксель документ
     * @param string $html хтмл документ
     * @param string $filename имя файла
     * @return type
     */
    static function htmlToExcel($html, $filename = "report.xls") {
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$filename");
        return response($html)
                        ->header("Content-type", "application/vnd.ms-excel")
                        ->header("Content-Disposition", "attachment; filename=$filename");
    }

    static function UpdateConfig($configName, $data) {
//        $config = Config::get($configName);
//        foreach ($data as $k => $v) {
//            $config[$k] = $v;
//        }
//        $filedata = var_export($config, 1);
//        if (File::put(config_path() . '/' . $configName . '.php', "<?php\n return $filedata ;")) {
//            return true;
//        } else {
//            return false;
//        }
    }

    /**
     * 
     * @param \Carbon\Carbon $date1
     * @param \Carbon\Carbon $date2
     */
    static function DatesEqByYearAndMonth($date1, $date2) {
        return ($date1->year == $date2->year && $date1->month == $date2->month);
    }

    /**
     * 
     * @param \Carbon\Carbon $date
     */
    static function DateIsToday($date) {
        return ($date->between(Carbon::now()->setTime(0, 0, 0), Carbon::now()->setTime(23, 59, 59)));
    }
    /**
     * Возвращает ip адрес пользователя
     * @return string
     */
    static function GetClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    static function GetClientIPList() {
        $res = [];
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $res['HTTP_CLIENT_IP'] = $_SERVER['HTTP_CLIENT_IP'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $res['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (isset($_SERVER['HTTP_X_FORWARDED']))
            $res['HTTP_X_FORWARDED'] = $_SERVER['HTTP_X_FORWARDED'];
        if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $res['HTTP_FORWARDED_FOR'] = $_SERVER['HTTP_FORWARDED_FOR'];
        if (isset($_SERVER['HTTP_FORWARDED']))
            $res['HTTP_FORWARDED'] = $_SERVER['HTTP_FORWARDED'];
        if (isset($_SERVER['REMOTE_ADDR']))
            $res['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $res['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        if (isset($_SERVER['REMOTE_HOST']))
            $res['REMOTE_HOST'] = $_SERVER['REMOTE_HOST'];
        return $res;
    }

    /**
     * Проверяет находится ли пользователь в локальной сети
     * @return boolean
     */
    static function ClientIsInLocalNetwork() {
        $ip = HelperUtil::GetClientIP();
        $localIPs = ['192.168.', '127.0.0.1', '172.16.'];
        foreach ($localIPs as $localIP) {
            if (strstr($ip, $localIP) !== FALSE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Запускает команду в консоли сервера
     * @param string $str
     * @return boolean | string возвращает строку вывода в консоли или FALSE в случае неудачи
     */
    static function startShellProcess($str) {
        $process = new Process($str);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
//            throw new ProcessFailedException($process);
            return FALSE;
        }
        return $process->getOutput();
    }

    /**
     * Отправлятель запросов через cURL
     * @param string $url
     * @param array $data
     * @param boolean $is_post
     *
     * скопированно из сайта финтерра.рф для отправки чеков при удержании страховке
     *
     */
    static public function sendByCurl($url, $data, $is_post = false, $username=null, $password=null, $chek=null) {
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';
        $hdrs = [
            "cache-control: no-cache",
            "content-type: application/json"
        ];

        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if ($is_post) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
            if(!empty($data)){
                $query_string = http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query_string);
            }
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($chek==1){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        }

        $answer = curl_exec($ch);
        curl_close($ch);

        return $answer;
    }

}
