<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use App\Utils\StrLib;
use App\Spylog\Spylog;
use App\Spylog\SpylogModel;
use Illuminate\Support\Facades\Validator;
use Mail;
use Log;
use Config;
use App\MySoap;
use App\Utils\FileToPdfUtil;
use App\ContractForm;

class EmploymentDocsController extends BasicController {
    
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Страница печати документов
     * @return type
     */
    public function index() {
        $this->sendEmailOnEnter();
        $contractsList = ContractForm::where('text_id', 'employment_doc')->where('description', '<>', 'EmploymentContract1')->get();
        $contracts = [];
        foreach ($contractsList as $item) {
            $url = url('employment/docs/pdf?contract_form_id=' . $item->id);
            //если документ - опись то добавлять еще один шаблон
            // в одном шаблоне трудовая в другом - заявление на трудовую
            if($item->tplFileName=='opis.odt'){
                $contracts[] = [
                    'label' => 'Опись (если трудовая книжка есть)',
                    'url' => $url.'&trud=1',
                    'printed' => false
                ];
                $contracts[] = [
                    'label' => 'Опись (если первое рабочее место)',
                    'url' => $url.'&trud=0',
                    'printed' => false
                ];
            } else {
                $contracts[] = [
                    'label' => $item->name,
                    'url' => $url,
//                    'printed' => $this->hasSpylogForContract($item->id)
                    'printed' => false
                ];
            }
        }
        $manual = ContractForm::where('text_id', 'employment_help')->first();
        return view('dashboard/employment_docs', [
            'contracts' => $contracts,
            'manual' => [
                'label' => $manual->name,
                'url' => url('employment/docs/pdf?contract_form_id=' . $manual->id)
            ]
        ]);
    }

    /**
     * отправить письмо о первом входе в кадры
     */
    function sendEmailOnEnter() {
        if (is_null(Auth::user())) {
            return redirect('auth/login');
        }
        $logged = SpylogModel::where('action', Spylog::ACTION_LOGIN)
                ->where('user_id', Auth::user()->id)
                ->first();
        if (is_null($logged) || $logged->created_at->between(Carbon::now()->subMinutes(2), Carbon::now())) {
            Log::info('EmploymentDocsController ОТПРАВКА ПОЧТЫ НА ПЕРВЫЙ ВХОД', ['user' => Auth::user()]);
            if (config('app.dev') != 1) {
                Mail::send('emails.firstlogin', ['username' => Auth::user()->name, 'date' => Carbon::now()->format('H:i:s d.m.Y')], function($message) {
                    $message->subject('Первая авторизация в АРМ');
//                    $message->to('a.lyakh@pdengi.ru');
                    $message->to('sov.mpp@pdengi.ru');
                });
            }
        }
    }

    /**
     * Устанавливает дату рождения в пользователе со страницы печати документов
     * @param Request $req
     * @return type
     */
    public function setBirthDate(Request $req) {
        $user = Auth::user();
        if (is_null($user)) {
            return redirect('auth/login');
        }
        if (!$req->has('birth_date') || empty($req->get('birth_date'))) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $user->birth_date = with(new Carbon($req->get('birth_date')))->format('Y-m-d H:i:s');
        $user->save();
        return $this->backWithSuc();
    }

    /**
     * Обработка формы печати документов
     * Проверяет выставлена ли галочка и были ли события о печати всех нужных доков в базе
     * @param Request $req
     * @return type
     */
    public function setEmploymentDocsSigned(Request $req) {
        $user = Auth::user();
        if ($req->has('employment_agree')) {

            $contractsList = ContractForm::where('text_id', 'employment_doc')->where('description', '<>', 'EmploymentContract1')->lists('id');
            $allPrinted = true;
//            foreach ($contractsList as $cid) {
//                if (!$this->hasSpylogForContract($cid)) {
//                    $allPrinted = FALSE;
//                }
//            }
            if (!$allPrinted) {
                return $this->backWithErr('Необходимо распечатать все документы!');
            }

            $user->employment_agree = Carbon::now()->format('Y-m-d H:i:s');
            $user->save();
            return redirect('/');
        } else {
            return redirect('employment/docs')->with('msg_err', 'Необходимо согласиться с условиями');
        }
    }
    /**
     * Проверяет распечатан ли был документ или нет
     * @param integer $cid ид формы 
     * @return boolean
     */
    function hasSpylogForContract($cid) {
        return (SpylogModel::where('action', Spylog::ACTION_PRINT)
                        ->where('user_id', Auth::user()->id)
                        ->where('table_id', Spylog::TABLE_CONTRACTS_FORMS)
                        ->where('doc_id', $cid)
                        ->whereBetween('created_at', [
                            Carbon::today()->format('Y-m-d H:i:s'),
                            Carbon::tomorrow()->format('Y-m-d H:i:s'),
                        ])
                        ->count() > 0) ? true : false;
    }

    /**
     * Страница ввода трекномера
     * @return type
     */
    public function addTrackNumber() {
        return view('dashboard/employment_track_number');
    }

    /**
     * Обработка формы ввода трек номера
     * @param Request $req
     * @return type
     */
    public function updateTrackNumber(Request $req) {
        $user = Auth::user();
        if (is_null($user)) {
            return redirect('auth/login');
        }
        $validator = Validator::make($req->all(), [
                    'employment_docs_track_number' => 'required|size:14'
        ]);
        if ($validator->fails()) {
            return $this->backWithErr('Неверный трек-номер');
        }
        $user->employment_docs_track_number = $req->get('employment_docs_track_number');
        $user->save();
        try {
            $this->sendEmailOnTracknumber();
        } catch (\Exception $ex) {
            
        }
        return redirect('/')->with('msg_suc', 'Трек номер сохранен');
    }

    /**
     * отправить письмо о первом входе в кадры
     */
    function sendEmailOnTracknumber() {
        if (is_null(Auth::user())) {
            return redirect('auth/login');
        }
        $user = Auth::user();
        Log::info('EmploymentDocsController ДОБАВЛЕН ТРЕК НОМЕР', ['user' => $user]);
        if (config('app.dev') != 1) {
            $text = 'Пользователь ' . Auth::user()->name . ' отправил документы на трудоустройство по почте. Трек номер: ' . $user->employment_docs_track_number;
            Mail::raw($text, function($message) {
                $message->subject('ARM: Трек номер отправленных документов');
//                    $message->to('a.lyakh@pdengi.ru');
                $message->to('sov.mpp@pdengi.ru');
            });
        }
    }

    /**
     * Печать документов на трудоустройство
     * @param Request $req
     * @return string
     */
    public function createPdf(Request $req) {
        if (!$req->has('contract_form_id')) {
            return $this->backWithErr(StrLib::ERR_NO_PARAMS);
        }
        $contract = ContractForm::find($req->contract_form_id);
        if (is_null($contract)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }

        $contractData = $this->getDataForDoc($contract->description);
        if($req->has('trud')){
            $contractData['trud'] = ($req->get('trud'))?'Трудовая книжка '.Auth::user()->name:'Заявление на трудовую книжку '.Auth::user()->name;
        }
        \PC::debug($contractData);
        if (array_key_exists('result', $contractData) && $contractData['result'] == 0) {
            return $this->backWithErr('Пользователь с такими параметрами не найден. Обратитесь в тех. поддержку');
        }
        //если вернулись параметры для договора без графика то выбрать форму договора без графика
        if (array_key_exists('Type_of_employment_num', $contractData) && $contractData['Type_of_employment_num'] == '0') {
            $contract = ContractForm::where('description', 'EmploymentContract1')->first();
            if (is_null($contract)) {
                return $this->backWithErr(StrLib::ERR_NULL);
            }
        }
        Spylog::log(Spylog::ACTION_PRINT, Spylog::TABLE_CONTRACTS_FORMS, $contract->id, null, Auth::user()->id);
        if (!empty($contract->tplFileName)) {
            return FileToPdfUtil::replaceKeysAndPrint($contract->tplFileName, $contractData);
        } else {
            $html = $contract->template;
            foreach ($contractData as $k => $v) {
                if (!is_array($v)) {
                    $html = str_replace('{{' . $k . '}}', $v, $html);
                }
            }
            $html = ContractEditorController::replaceConfigVars($html);
//            return \App\Utils\PdfUtil::getPdf($html, [], true);
            return \App\Utils\PdfUtil::getPdf($html);
        }
        return 'Здесь будет печать документов';
    }

    /**
     * Получить набор данных для вставки в документы
     * @param string $service1cUrl
     * @return array
     */
    function getDataForDoc($service1cUrl = null) {
        if ($service1cUrl == 'EmploymentСontract1') {
            $service1cUrl = 'EmploymentСontract';
        }
        $contractData = Config::get('vars');
        $birthDate = new Carbon(Auth::user()->birth_date);
        if (!empty($service1cUrl)) {
            $params = [
                'fio' => Auth::user()->name,
                'birth_date' => $birthDate->format('YmdHis'),
                'type' => $service1cUrl
            ];
            $connection = [
                'url' => '192.168.1.77:81/Pay/ws/Employment?wsdl',
                'login' => 'ИТ',
                'password' => 'iatianymatonv',
                'absolute_url' => true
            ];
            $res1c = \App\MySoap::sendXML(MySoap::createXML($params), true, 'Main', $connection);
            foreach ($res1c as $k => $v) {
                $contractData[$k] = (string) $v;
            }
            $contractData['fio'] = $params['fio'];
            if (!array_key_exists('Date_of_Birth', $contractData)) {
                $contractData['Date_of_Birth'] = $birthDate->format('d.m.Y');
            }
            $subdiv = Auth::user()->subdivision;
            if(!is_null($subdiv)){
                $contractData['subdivision_city'] = $subdiv->city;
            }
            $user = Auth::user();
            if(!is_null($user)){
                $doc = explode(' от ', $user->doc);
                $contractData['user_doc_number'] = $doc[0];
                if(count($doc)>1){
                    $contractData['user_doc_date'] = $doc[1];
                }
            }

            if ($service1cUrl == "EmploymentСontract" && array_key_exists('Type_of_employment_num', $contractData) && $contractData['Type_of_employment_num'] === '1') {
                $contractData['work_schedule'] = $this->addScheduleTable($res1c->work_schedule->children());
            }
        }
        return $contractData;
    }

    /**
     * Возвращает строкой сгенерированную по переданном данным таблицу графика работы для вставки в шаблон
     * @param array $data 
     * @return string
     */
    function addScheduleTable($data) {
        $table = '<table>';
        $table .= '<thead>';
        $table .= '<tr>';
        $table .= '<td rowspan=2>Месяц</td>';
        $table .= '<td colspan=31>Часов за день</td>';
        $table .= '<td colspan=2>По графику</td>';
        $table .= '</tr>';
        $table .= '<tr>';
        for ($d = 1; $d <= 31; $d++) {
            $table .= '<td>' . $d . '</td>';
        }
        $table .= '<td>дней</td>';
        $table .= '<td>часов</td>';
        $table .= '</tr>';
        $table .= '</thead>';
        $table .= '<tbody>';
        $months = [];
        $monthNames = \App\StrUtils::getMonthNames();
        foreach ($data as $day) {
            $date = new Carbon((string) $day->date);
            if (!array_key_exists($date->month, $months)) {
                $months[$date->month] = ['days' => [], 'days_num' => 0, 'hours_num' => 0, 'name' => $monthNames[$date->month - 1]];
            }
            $months[$date->month]['days'][$date->day] = [
                'type' => (string) $day->type,
                'hours' => str_replace(',','.',$day->hours),
                'class' => ((string) $day->type == 'Рабочий') ? '' : ' class="red-cell"'
            ];
            $months[$date->month]['days_num']++;
            $months[$date->month]['hours_num'] += $months[$date->month]['days'][$date->day]['hours'];
        }
        foreach ($months as $m) {
            $table.='<tr>';
            $table.='<td>' . $m['name'] . '</td>';
            for ($d = 1; $d <= 31; $d++) {
                $table.='<td ';
                if (array_key_exists($d, $m['days'])) {
                    $table .= $m['days'][$d]['class'];
                } else {
                    $table .= 'class="gray-cell"';
                }
                $table .= '>' . ((array_key_exists($d, $m['days'])) ? $m['days'][$d]['hours'] : 'X');
                $table .= '</td>';
            }
            $table.='<td>' . $m['days_num'] . '</td>';
            $table.='<td>' . $m['hours_num'] . '</td>';
            $table.='</tr>';
        }
        $table .= '</tr></tbody>';
        $table.='</table>';
        return $table;
    }

}
