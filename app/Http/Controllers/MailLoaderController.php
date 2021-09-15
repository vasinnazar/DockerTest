<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use App\Utils\EmailReader;
use Illuminate\Support\Facades\DB;
use XMLReader;

class MailLoaderController extends Controller {

    public $notfound_csv;

    public function __construct() {
        
    }

    public function test(Request $request) {
        return 2;
    }

    public function index() {
        return view('adminpanel/mailloader');
    }

    public function saveBigXML() {
        $customers = DB::connection('armf_copy')->select('select passports.series as series,passports.number as number,passports.created_at as date,passports.fio as fio from passports');
        $fp = fopen('./mailfiles/customers.xml', 'w');
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?><root>');
        foreach ($customers as $item) {
            fwrite($fp, '<i s="' . $item->series . '" n="' . $item->number . '" f="' . $item->fio . '" d="' . (with(new Carbon($item->date))->format('dmY')) . '" />');
        }
        fwrite($fp, '</root>');
        fclose($fp);
    }

    public function loadOneMail(EmailReader $er, $passport_series, $passport_number, $fio1c, $date1c) {
        $idlist = $er->getIndexListByDate($date1c);
//        \PC::debug($idlist);
        foreach ($idlist as $id) {
            $email = $er->get($id);
            $fio = imap_mime_header_decode($email['header']->subject)[0]->text;
//            echo $date1c.' | '.$fio.' | '.$fio1c.'<br>';
//            \PC::debug($fio, 'fio mail');
//            \PC::debug($fio1c, 'fio1c');
            $arfio = explode(' ', $fio);
            //количество найденных слов в строке из 1с
            $num = 0;
            foreach ($arfio as $f) {
                $strid = strpos($fio1c, $f);
                if ($strid !== FALSE) {
                    $num++;
                }
            }
            if ($fio == $fio1c || strstr($fio1c, $fio) !== FALSE || $num > 0) {
                $folder_start = $passport_series . $passport_number . '/';
                $this->processMail($id, $er, $folder_start);
            }
        }
    }

    public function loadOneMail2(EmailReader $er, $passport_series, $passport_number, $fio1c, $date1c) {
        $arfio1c = explode(' ', $fio1c);
        $surname = ($arfio1c[0] == '' || $arfio1c[0] == ' ') ? $arfio1c[1] : $arfio1c[0];
        $mails = $er->getMails($date1c, $surname);
//        $mails = array_splice($mails, 0, 10);
//        \PC::debug($mails);

        if ($mails == false) {
            fputcsv($this->notfound_csv, [$fio1c, $date1c]);
            return;
        }
        foreach ($mails as $id) {
//            $email = $er->get($id, false, true);
//            if (!isset($email['header']->subject)) {
//                continue;
//            }
//            $fio = imap_mime_header_decode($email['header']->subject)[0]->text;
//            $fio = iconv(mb_detect_encoding($fio, ['KOI8-R']), 'UTF-8', $fio);;
//            $arfio = explode(' ', $fio);
//            if ($fio == $fio1c || strstr($fio1c, $fio) !== FALSE || strstr($fio, $surname) !== FALSE) {
//                echo '<br>' . $date1c . ' | ' . $fio . ' | ' . $fio1c . '<br>';
                $folder_start = $passport_series . $passport_number . '/';
//                echo $folder_start . ' | ' . $date1c . '<br>';
                $er->email_load_files($er->get2($id, true), $folder_start);
//            }
        }
    }

    public function loadMailList(Request $req) {
        $xml = new \SimpleXMLElement($req->xml);
        $er = new EmailReader();
        foreach ($xml->children() as $item) {
            $this->loadOneMail($er, $item["passport_series"], $item["passport_number"], $item["fio"], $item["date"]);
            usleep(1000);
        }
    }

    public function loadMailXML() {
        $this->notfound_csv = fopen('./mailfiles/notfound.csv', 'a');
        $er = new EmailReader();
        $reader = new XMLReader();
        $reader->open("./mailfiles/customers.xml");
        $offset = 20;
        $r = 0;
        ob_start();
        $start_time = Carbon::now();
        echo 'НАЧАТО: ' . ($start_time->format('H:i:s'));
//        echo '<br>';
        while ($reader->read()) {
            switch ($reader->nodeType) {
                case (XMLREADER::ELEMENT):
                    if ($reader->localName == "i") {
                        $r++;
                        if ($r < $offset) {
                            break;
                        }
                        $item = [];
                        $item['passport_series'] = $reader->getAttribute('s');
                        $item['passport_number'] = $reader->getAttribute('n');
                        $item['fio'] = $reader->getAttribute('f');
                        $item['date'] = $reader->getAttribute('d');
                        $this->loadOneMail2($er, $item["passport_series"], $item["passport_number"], $item["fio"], $item["date"]);
                        ob_flush();
//                        usleep(1000);
                        sleep(1);
                    }
                    break;
            }
            if ($r == 1000) {
                break;
            }
        }
        $end_time = Carbon::now();
        echo '<br>';
        echo 'ЗАВЕРШЕНО: ' . ($end_time->format('H:i:s'));
        echo '<br>';
        echo 'ПОТРАЧЕНО: ' . ($end_time->diffInMinutes($start_time));
        echo '<br> КОЛИЧЕСТВО СТРОК: ' . $r;
        ob_end_flush();
        fclose($this->notfound_csv);
//        $xml = new \SimpleXMLElement($req->xml);
//        foreach ($xml->children() as $item) {
//            $this->loadOneMail($er, $item["passport_series"], $item["passport_number"], $item["fio"], $item["date"]);
//            sleep(1);
//        }
    }

    public function loadMail(Request $req) {
        $er = new EmailReader();
        $this->loadOneMail($er, $req->passport_series, $req->passport_number, $req->fio, $req->date);
    }

    function processMail($i, $er, $folder_start, $email = null) {
        if ($email == null) {
            $email = $er->get2($i, true);
        }
//        $fio = imap_mime_header_decode($email['header']->subject)[0]->text;
//        if($res1c)
//        echo('<h1>' . $fio . '</h1>');
//echo($email['header']);
//        echo '<br>';
//var_dump($email);
//echo(imap_mime_header_decode($email['header']->subject,true)[0]->text);
        $er->email_load_files($email, $folder_start);
//        echo '<hr><br>';
    }

}
