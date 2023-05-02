<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;
use App\Utils\StrLib;
use App\MySoap;
use App\ContractForm;
use Auth;
use App\StrUtils;
use App\User;

class PaySheetController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        return view('reports.paysheet');
    }

    public function getHTML($xmlStr, $form_id, $date, $username) {
        $xml = new \SimpleXMLElement($xmlStr);
        $form = ContractForm::find($form_id);
        if (is_null($form)) {
            return null;
        }
        $html = $form->template;
        $nachisleno = '<table>';
        $total_nachisleno = 0;
        $uderzhano = '<table>';
        $total_uderzhano = 0;
        $viplacheno = '<table>';
        $total_viplacheno = 0;
        $dohodi = '<table>';
        $total_dohodi = 0;
        $children = $xml->children();
        foreach ($children as $item) {
            if ($item->getName() == 'Начисление') {
                $nachisleno .= '<TR>'
                        . '<TD class="vid-col">' . $item['ВидНачисленно'] . '</TD>'
                        . '<TD>' . '' . '</TD>'
                        . '<TD class="xs-col">' . $item['ДниНачисленноОтработано'] . '</TD>'
                        . '<TD class="xs-col">' . $item['ЧасыНачисленноОтработано'] . '</TD>'
                        . '<TD class="xs-col">' . $item['ДниНачисленноОпачено'] . '</TD>'
                        . '<TD class="xs-col">' . $item['ЧасыНачисленноОплачено'] . '</TD>'
                        . '<TD class="summa-col">' . $this->money($item['СуммаНачисленно']) . '</TD>'
                        . '</TR>';
                $total_nachisleno += $this->parseMoney($item['СуммаНачисленно']);
            }
            if ($item->getName() == 'Удержание' && isset($item['ВидУдержано'])) {
                $uderzhano .= '<TR>'
                        . '<TD class="vid-col">' . $item['ВидУдержано'] . '</TD>'
                        . '<TD>' . '' . '</TD>'
                        . '<TD class="summa-col">' . $this->money($item['СуммаУдержано']) . '</TD>'
                        . '</TR>';
                $total_uderzhano += $this->parseMoney($item['СуммаУдержано']);
            }
            if ($item->getName() == 'Удержание' && isset($item['ВидВыплачено'])) {
                $viplacheno .= '<TR>'
                        . '<TD class="vid-col">' . $item['ВидВыплачено'] . '</TD>'
                        . '<TD>' . '' . '</TD>'
                        . '<TD class="summa-col">' . $this->money($item['СуммаВыплачено']) . '</TD>'
                        . '</TR>';
                $total_viplacheno += $this->parseMoney($item['СуммаВыплачено']);
            }
            if ($item->getName() == 'ДоходыВНатФорме') {
                $dohodi .= '<TR>'
                        . '<TD class="vid-col">' . $item['ВидДоходы'] . '</TD>'
                        . '<TD>' . '' . '</TD>'
                        . '<TD class="xs-col">' . $item['ДниДоходыОтработано'] . '</TD>'
                        . '<TD class="xs-col">' . $item['ЧасыДоходыОтработано'] . '</TD>'
                        . '<TD class="xs-col">' . $item['ДниДоходыОпачено'] . '</TD>'
                        . '<TD class="xs-col">' . $item['ЧасыДоходыОплачено'] . '</TD>'
                        . '<TD class="summa-col">' . $this->money($item['СуммаДоходы']) . '</TD>'
                        . '</TR>';
                $total_dohodi += $this->parseMoney($item['СуммаДоходы']);
            }
        }
        $nachisleno .= '</table>';
        $uderzhano .= '</table>';
        $viplacheno .= '</table>';
        $dohodi .= '</table>';

        $html = str_replace('{{k_viplate}}', $this->money($xml->СуммаВыплаты['СуммаВыплаты']), $html);

        $html = str_replace('{{nachisleno}}', $nachisleno, $html);
        $html = str_replace('{{uderzhano}}', $uderzhano, $html);
        $html = str_replace('{{viplacheno}}', $viplacheno, $html);
        $html = str_replace('{{dohodi}}', $dohodi, $html);

        $html = str_replace('{{total_nachisleno}}', $this->money($total_nachisleno), $html);
        $html = str_replace('{{total_uderzhano}}', $this->money($total_uderzhano), $html);
        $html = str_replace('{{total_viplacheno}}', $this->money($total_viplacheno), $html);
        $html = str_replace('{{total_dohodi}}', $this->money($total_dohodi), $html);

        $html = str_replace('{{debt_start}}', $this->money($xml->Сальдо['СальдоНаНачало']), $html);
        $debt_end = $this->money($xml->Сальдо['СальдоНаКонец']);
        $html = str_replace('{{debt_end}}', str_replace('-', '', $debt_end), $html);
        $html = str_replace('{{debt_ndfl_start}}', $this->money($xml->НДФЛ['НДФЛНаНачало']), $html);
        $html = str_replace('{{debt_ndfl_end}}', $this->money($xml->НДФЛ['НДФЛНаКонец']), $html);

        $html = str_replace('{{ndfl_self}}', '', $html);
        $html = str_replace('{{ndfl_children}}', '', $html);
        $html = str_replace('{{ndfl_im}}', '', $html);

        $html = str_replace('{{date}}', $date, $html);
        $html = str_replace('{{users.name}}', $username, $html);

        $html = str_replace('{{dolg}}', 'Долг за ' . ((strstr($debt_end, '-') !== FALSE) ? 'работником' : 'предприятием') . ' на конец месяца', $html);
//        echo $html;
        return $html;
    }

    public function money($sum) {
//        \PC::debug($sum.'|'.number_format((float)$sum, 2, '.', ''),'money');
//        return number_format((float)$sum, 2, '.', '');
        return (is_null($sum) || $sum == '') ? '' : StrUtils::kopToRub(floatval($sum) * 100) . ' руб.';
    }

    public function parseMoney($sum) {
        return number_format((float) $sum, 2, '.', '');
    }

    public function createPdf(Request $req) {
//        if($req->has('newzup')){
        return $this->createPdf2($req);
//        }
        if (!$req->has('month') || !$req->has('year')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS . '(0)');
        }
        $form = ContractForm::where('text_id', config('options.paysheet'))->first();
        if (is_null($form)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL . '(1)');
        }
        if (Auth::user()->isAdmin() && $req->has('user_id')) {
            $user = \App\User::find($req->user_id);
        } else {
            $user = Auth::user();
        }
        $mnames = ["", "Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
        $startDate = new Carbon($req->year . '-' . $req->month . '-01');
        $endDate = new Carbon($req->year . '-' . $req->month . '-' . $startDate->daysInMonth);
        $res1c = MySoap::getPaysheet($endDate->format('Ymd'), $endDate->format('Ymd'), $user->name);
        if (!$res1c['res']) {
            return redirect()->back()->with('msg_err', StrLib::ERR_1C . '(2)');
        }
        $path = $res1c['value'];
        $html = $this->getHTML($path, $form->id, $mnames[$req->month], $user->name);
        if (is_null($html)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL . '(3)');
        }
        $opts = ['encoding' => 'UTF-8', 'orientation' => 'landscape'];
        $html = ContractEditorController::replaceConfigVars($html);
        return \App\Utils\PdfUtil::getPdf($html, $opts);
    }

    public function createPdf2(Request $req) {
        if (!$req->has('month') || !$req->has('year')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS . '(0)');
        }
        $form = ContractForm::where('text_id', config('options.paysheet'))->first();
        if (is_null($form)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL . '(1)');
        }
        if (Auth::user()->isAdmin() && $req->has('user_id')) {
            $user = \App\User::find($req->user_id);
        } else {
            $user = Auth::user();
        }
        $startDate = new Carbon($req->year . '-' . $req->month . '-01');
        $endDate = new Carbon($req->year . '-' . $req->month . '-' . $startDate->daysInMonth);
        $advance = ($req->has('advance'))?1:0;
        $connection = config('admin.zup_connection');
        $res1c = MySoap::call1C('get_calc_list', ['getFIO' => $user->name, 'birthDate' => with(new Carbon($user->birth_date))->format('Ymd'), 'startDate' => $endDate->format('Ymd'), 'typeResult' => 0, 'advance'=>$advance], false, false, $connection);
//        $res1c = MySoap::call1C('get_calc_list', ['Responsible' => 'Колесников Владислав','DateStart' => $startDate->format('Ymd'), 'DateFinish' => $endDate->format('Ymd')], false, false, $connection);
        return $res1c['value'];
    }

}
