<?php

namespace App\Utils;

use Auth;
use Response;
use App\Passport;
use Carbon\Carbon;
use App\Loan;
use App\User;

class FileToPdfUtil {
    /**
     *
     * @var \clsTinyButStrong
     */
    public $TBS;
    /**
     *
     * @var string 
     */
    public $output_file_name;
    public  function __construct() {
        
    }
    static function replaceKeys($filename, $data, $type = false, $arMassTask = false) {
        $dirname = self::getPathToTpl(); //путь к папке с шаблонами документов .ods

        if (is_null($filename) || $filename == '') {
            return 'Не указан путь к шаблону.';
            die();
        }

        if (!is_dir($dirname)) {
            mkdir($dirname, 0777);
            mkdir($dirname . 'tmp', 0777);
        }

        include_once(__DIR__ . '/tbs_plugin/tbs_class.php'); // Load the TinyButStrong template engine
        include_once(__DIR__ . '/tbs_plugin/tbs_plugin_opentbs.php'); // Load the OpenTBS plugin
        // prevent from a PHP configuration problem when using mktime() and date()
        if (version_compare(PHP_VERSION, '5.1.0') >= 0) {
            if (ini_get('date.timezone') == '') {
                date_default_timezone_set('UTC');
            }
        }

        // Initialize the TBS instance
        $TBS = new \clsTinyButStrong(); // new instance of TBS
        $TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN); // load the OpenTBS plugin
//        $dirname = $_SERVER['DOCUMENT_ROOT'] . 'odss/';
//        $filename = 'anketa.odt';

        $TBS->LoadTemplate($dirname . $filename, OPENTBS_ALREADY_UTF8);

        // ----------------------
        // Debug mode of the demo
        // ----------------------
        if (isset($_POST['debug']) && ($_POST['debug'] == 'current'))
            $TBS->Plugin(OPENTBS_DEBUG_XML_CURRENT, true); // Display the intented XML of the current sub-file, and exit.
        if (isset($_POST['debug']) && ($_POST['debug'] == 'info'))
            $TBS->Plugin(OPENTBS_DEBUG_INFO, true); // Display information about the document, and exit.
        if (isset($_POST['debug']) && ($_POST['debug'] == 'show'))
            $TBS->Plugin(OPENTBS_DEBUG_XML_SHOW); // Tells TBS to display information when the document is merged. No exit.

        $mergeData = [];
        $mergeData[0] = $data;

        //echo '<pre>'; print_r($data); echo '</pre>'; die();
        if (count($data) > 0) {
            if ($type && $type == 'order') {
                $sum = \App\StrUtils::kopToRub($data['orders']['money']);
                $mergeData[0]['company_name'] = config('vars.company_name');
                $mergeData[0]['orders']['created_at'] = with(new Carbon($data['orders']['created_at']))->format('d.m.Y');
                $mergeData[0]['orders']['created_at_txt'] = \App\StrUtils::dateToStr($data['orders']['created_at']);
                $mergeData[0]['orders']['money'] = number_format($sum, 2, ',', ' ');
                $mergeData[0]['orders']['money_frmt'] = \App\StrUtils::sumToRubAndKop($sum);
                $mergeData[0]['orders']['money_txt'] = \App\StrUtils::num2str($sum, true);
            } else if ($type && $type == 'debtors') {
                foreach ($data as $key => $value) {
                    $mergeData[0][$key] = $value;
                }
            } else {
                $arName = explode(" ", $data['passports']['fio']);
                $mergeData[0]['passports']['last_name'] = $arName[0];
                $mergeData[0]['passports']['first_name'] = $arName[1];
                $mergeData[0]['passports']['middle_name'] = isset($arName[2]) ? $arName[2] : "";

                $mergeData[0]['about_clients']['sex'] = ($data['about_clients']['sex'] == 1) ? "Мужской" : "Женский";

                $mergeData[0]['claims']['created_at'] = with(new Carbon($data['claims']['created_at']))->format('d.m.Y');
                $mergeData[0]['passports']['issued_date'] = with(new Carbon($data['passports']['issued_date']))->format('d.m.Y');
                $mergeData[0]['passports']['birth_date'] = with(new Carbon($data['passports']['birth_date']))->format('d.m.Y');

                $mergeData[0]['full_address'] = Passport::getFullAddress($data['passports']);

                $mergeData[0]['company_address'] = config('vars.company_address');
                $mergeData[0]['company_ogrn'] = config('vars.company_ogrn');
                $mergeData[0]['company_mfo_number'] = config('vars.company_mfo_number');

                $mergeData[0]['company_phone'] = config('vars.company_phone');
                $mergeData[0]['company_phone2'] = config('vars.company_phone2');
                $mergeData[0]['company_name'] = config('vars.company_name');
                $mergeData[0]['company_rs'] = config('vars.company_rs');
                $mergeData[0]['company_kpp'] = config('vars.company_kpp');
                $mergeData[0]['company_bik'] = config('vars.company_bik');
                $mergeData[0]['company_ks'] = config('vars.company_ks');
                $mergeData[0]['company_bank_predl'] = config('vars.company_bank_predl');
                $mergeData[0]['company_post_address'] = config('vars.company_post_address');
            }
        }
//        $user = User::find($data['claims']['user_id']);
//        $mergeData[0]['users']['name'] = $user->name;


        $TBS->MergeBlock('a', $mergeData);
        
        if ($arMassTask && is_array($arMassTask)) {
            $output_file_name = $arMassTask['filename'] . '.odt';
        } else {
            $output_file_name = str_replace('.', '_' . uniqid() . '.', $filename);
        }

        

        //$TBS->Show(OPENTBS_DOWNLOAD, $output_file_name);
        $TBS->Show(OPENTBS_FILE, $dirname . 'tmp/' . $output_file_name);

        \App\Utils\PdfUtil::getPdfFromFile($output_file_name, $arMassTask);

        //die();
    }
    /**
     * Заменяет теги в файле с переданным именем и выводит его в виде pdf в окно браузера
     * @param string $filename имя шаблона odt\ods, который нужно распечатать
     * @param array $data массив данных которые нужно вставить в шаблон
     * @return string
     */
    static function replaceKeysAndPrint($filename, $data) {
        $file2pdfUtil = new FileToPdfUtil();
        $dirname = $file2pdfUtil->getTmpDirname();

        if (is_null($filename) || $filename == '') {
            return 'Не указан путь к шаблону.';
            die();
        }

        $file2pdfUtil->initTBS($filename);
        $file2pdfUtil->replaceTags($data);
        return $file2pdfUtil->getPdf();
    }
    /**
     * Вывести документ в виде pdf
     */
    public function getPdf(){
        $this->TBS->Show(OPENTBS_FILE, $this->getTmpDirname() . 'tmp/' . $this->output_file_name);
        return \App\Utils\PdfUtil::getPdfFromFile($this->output_file_name);
    }
    /**
     * Заменить теги в документе на переданные данные
     * @param array $data данные для замены
     */
    public function replaceTags($data){
        $mergeData = [];
        $mergeData[0] = $data;
        $this->TBS->MergeBlock('a', $mergeData);
    }
    /**
     * Возвращает путь к папке с шаблонами документов .ods. Если ее нет то создает
     * @return string
     */
    private function getTmpDirname(){
        $dirname = self::getPathToTpl();
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777);
            mkdir($dirname . 'tmp', 0777);
        }
        return $dirname;
    }
    /**
     * Инициализирует шаблонизатор для работы с шаблонами документов
     * @param string $filename имя файла шаблона
     */
    public function initTBS($filename){
        include_once(__DIR__ . '/tbs_plugin/tbs_class.php'); // Load the TinyButStrong template engine
        include_once(__DIR__ . '/tbs_plugin/tbs_plugin_opentbs.php'); // Load the OpenTBS plugin
        // prevent from a PHP configuration problem when using mktime() and date()
        if (version_compare(PHP_VERSION, '5.1.0') >= 0) {
            if (ini_get('date.timezone') == '') {
                date_default_timezone_set('UTC');
            }
        }
        $this->TBS = new \clsTinyButStrong();
        $this->TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);
        $this->TBS->LoadTemplate($this->getTmpDirname() . $filename, OPENTBS_ALREADY_UTF8);
        if (isset($_POST['debug']) && ($_POST['debug'] == 'current'))
            $this->TBS->Plugin(OPENTBS_DEBUG_XML_CURRENT, true); // Display the intented XML of the current sub-file, and exit.
        if (isset($_POST['debug']) && ($_POST['debug'] == 'info'))
            $this->TBS->Plugin(OPENTBS_DEBUG_INFO, true); // Display information about the document, and exit.
        if (isset($_POST['debug']) && ($_POST['debug'] == 'show'))
            $this->TBS->Plugin(OPENTBS_DEBUG_XML_SHOW); // Tells TBS to display information when the document is merged. No exit.
        $this->output_file_name = str_replace('.', '_' . uniqid() . '.', $filename);
    }
    

    static function getPathToTpl() {
        return storage_path() . '/app/tplsForPdf/';
    }

}
