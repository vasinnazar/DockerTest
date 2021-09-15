<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;
use App\Utils\StrLib;
use App\MySoap;
use App\ContractForm;
use Auth;
use DB;
use App\Loan,
    App\Claim,
    App\Repayment,
    App\Order,
    App\User,
    App\Subdivision;

class DocsRegisterController extends Controller {

    public function __construct() {
        
    }

    public function index() {
        return view('reports.docsregister');
    }

    static function getDocsFrom1c($dateStart, $dateEnd, $subdivision_id_1c, $user_id_1c, $type) {
        $ctrl = new DocsRegisterController();
        return $ctrl->getDataFrom1c($dateStart, $dateEnd, $subdivision_id_1c, $user_id_1c, $type);
    }

    public function getDataFrom1c($dateStart, $dateEnd, $subdivision_id_1c, $user_id_1c, $type) {
        $res = [];
        if ($type == "" || is_null($type)) {
            $type = 0;
        }
//        if ($type < 0) {
//            return $this->getData($dateStart, $dateEnd, with(Subdivision::where('name_id', $subdivision_id_1c)->first())->id, $user_id_1c, $type);
//        }
        if(empty($subdivision_id_1c)){
            $subdivision_id_1c = Auth::user()->subdivision->name_id;
        }
        $res1c = MySoap::getDocsRegister(['date_start' => with(new Carbon($dateStart))->format('Ymd'), 'date_finish' => with(new Carbon($dateEnd))->format('Ymd'), 'subdivision_id_1c' => $subdivision_id_1c, 'user_id_1c' => '', 'type' => $type]);
        if ($res1c && array_key_exists('docs', $res1c)) {
            if ($type != MySoap::ITEM_SFP) {
                foreach ($res1c['docs'] as &$doc) {
                    switch ($doc['type']) {
                        case MySoap::ITEM_LOAN:
                            $doc['type_name'] = 'Кредитный договор';
                            break;
                        case MySoap::ITEM_PKO:
                            $doc['type_name'] = 'Приходно-кассовый ордер';
                            break;
                        case MySoap::ITEM_RKO:
                            $doc['type_name'] = 'Расходно-кассовый ордер';
                            break;
                        case MySoap::ITEM_REP_CLAIM:
                            $doc['type_name'] = 'Заявление о приостановке процентов';
                            break;
                        case MySoap::ITEM_REP_DOP:
                            $doc['type_name'] = 'Дополнительное соглашение';
                            break;
                        case MySoap::ITEM_REP_PEACE:
                            $doc['type_name'] = 'Соглашение об урегулировании задолженности';
                            break;
                        case MySoap::ITEM_SFP:
                            $doc['type_name'] = 'Карта СФП';
                            break;
                        case MySoap::ITEM_CLAIM:
                            $doc['type_name'] = 'Заявка на кредит';
                            break;
                        case MySoap::ITEM_NPF:
                            $doc['type_name'] = 'НПФ';
                            break;
                    }
                    $res[] = $doc;
                }
            } else {
                $res = $res1c;
            }
        }
        return $res;
    }

    public function getData($dateStart, $dateEnd, $subdivision_id, $user_id_1c, $type) {
        $res = [];
        if (in_array($type, [0, 1])) {
            $loans = Loan::select('loans.id_1c as doc_id_1c', 'passports.fio as fio')
                    ->leftJoin('claims', 'loans.claim_id', '=', 'claims.id')
                    ->leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
                    ->whereBetween('loans.created_at', [$dateStart, $dateEnd])
                    ->where('loans.subdivision_id', $subdivision_id)
                    ->get();
            foreach ($loans as $loan) {
                $res[] = ['number' => $loan->doc_id_1c, 'type_name' => "Кредитный договор", 'fio' => $loan->fio, 'type' => MySoap::ITEM_LOAN];
            }
        }
        if (in_array($type, [0, 2, 3, 4])) {
            $reps = Repayment::select('repayments.id_1c as doc_id_1c', 'passports.fio as fio', 'repayment_types.name as type_name', 'repayment_types.text_id as tt_id')
                    ->leftJoin('loans', 'repayments.loan_id', '=', 'loans.id')
                    ->leftJoin('claims', 'loans.claim_id', '=', 'claims.id')
                    ->leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
                    ->leftJoin('repayment_types', 'repayments.repayment_type_id', '=', 'repayment_types.id')
                    ->whereBetween('repayments.created_at', [$dateStart, $dateEnd])
                    ->where('repayments.subdivision_id', $subdivision_id);
            if ($type == 2) {
                $reps->where('repayment_types.text_id', 'dopnik');
            } else
            if ($type == 3) {
                $reps->where('repayment_types.text_id', 'claim');
            } else
            if ($type == 4) {
                $reps->where('repayment_types.text_id', 'peace');
            }
            $reps = $reps->get();
            foreach ($reps as $rep) {
                $type_id = MySoap::ITEM_REP_DOP;
                if (in_array($rep->tt_id, [config('options.rtype_dopnik'), config('options.rtype_dopnik2'), config('options.rtype_dopnik3'), config('options.rtype_dopnik4'), config('options.rtype_dopnik5'), config('options.rtype_dopnik6'), config('options.rtype_dopnik7')])) {
                    $type_id = MySoap::ITEM_REP_DOP;
                }
                if ($rep->tt_id == config('options.rtype_claim')) {
                    $type_id = MySoap::ITEM_REP_CLAIM;
                }
                if ($rep->tt_id == config('options.rtype_peace')) {
                    $type_id = MySoap::ITEM_REP_PEACE;
                }
                $res[] = ['number' => $rep->doc_id_1c, 'type_name' => $rep->type_name, 'fio' => $rep->fio, 'type' => $type_id];
            }
        }
        if (in_array($type, [0, 5, 6])) {
            $orders = DB::table('orders')->select('orders.number as doc_id_1c', 'passports.fio as fio', 'order_types.name as type_name', 'order_types.text_id as tt_id')
                    ->leftJoin('passports', 'passports.id', '=', 'orders.passport_id')
                    ->leftJoin('order_types', 'orders.type', '=', 'order_types.id')
                    ->whereBetween('orders.created_at', [$dateStart, $dateEnd])
                    ->where('orders.subdivision_id', $subdivision_id);
            if ($type == 0) {
                $orders->whereIn('order_types.text_id', ['PKO', 'RKO']);
            } else {
                $orders->where('order_types.text_id', ($type == 0) ? 'RKO' : 'PKO');
            }
            $orders = $orders->get();
            foreach ($orders as $order) {
                $res[] = ['number' => $order->doc_id_1c, 'type_name' => $order->type_name, 'fio' => $order->fio, 'type' => (($type == 0) ? MySoap::ITEM_RKO : MySoap::ITEM_PKO)];
            }
        }
//        if ($type == -1) {
//            $claims = Claim::leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
//                    ->select('passports.fio', 'claims.id_1c')
//                    ->whereBetween('claims.created_at', [$dateStart, $dateEnd])
//                    ->where('claims.subdivision_id', $subdivision_id)
//                    ->get();
//            foreach ($claims as $claim) {
//                $res[] = ['number' => $claim->id_1c, 'type_name' => 'Заявка', 'fio' => $claim->fio, 'type' => MySoap::ITEM_CLAIM];
//            }
//        }
//        if ($type == -2) {
//            $npfs = \App\NpfContract::leftJoin('passports', 'npf_contracts.passport_id', '=', 'passports.id')
//                    ->select('passports.fio', 'npf_contracts.id_1c')
//                    ->whereBetween('npf_contracts.created_at', [$dateStart, $dateEnd])
//                    ->where('npf_contracts.subdivision_id', $subdivision_id)
//                    ->get();
//            foreach ($npfs as $npf) {
//                $res[] = ['number' => $npf->id_1c, 'fio' => $npf->fio];
//            }
//        }
        return $res;
    }

    public function createPdf(Request $req) {
        if (!$req->has('start') || !$req->has('end')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        if (with(new Carbon($req->start))->gt(new Carbon($req->end))) {
            return redirect()->back()->with('msg_err', 'Дата начала больше даты конца');
        }
        $dateStart = with(new Carbon($req->start))->format('Y-m-d');
        $dateEnd = with(new Carbon($req->end))->format('Y-m-d');
        $dops_num = 0;
        $loans_num = 0;
        $claims_num = 0;
        $rko_num = 0;
        $pko_num = 0;
        $peace_num = 0;
        $sfp_prihod = 0;
        $sfp_rashod = 0;
        $residue_begin = 0;
        $residue_end = 0;
        $cardstable = '';

        if ($req->doctype == MySoap::ITEM_SFP) {
            $form = ContractForm::where('text_id', config('options.docsregister_sfp'))->first();
        } else {
            $form = ContractForm::where('text_id', config('options.docsregister'))->first();
        }
        if (is_null($form)) {
            return abort(404);
        }

        $user = ($req->has('user_id')) ? User::find($req->user_id) : Auth::user();
        if(is_null($user)){
//            $user = Auth::user();
        }
        $subdiv = (Auth::user()->isAdmin() && $req->has('subdivision_id')) ? Subdivision::find($req->subdivision_id) : Subdivision::find(Auth::user()->subdivision_id);
        $html = $form->template;
        $user_id_1c = (is_null($user))?null:$user->id_1c;
        $data = $this->getDataFrom1c($dateStart, $dateEnd, $subdiv->name_id, $user_id_1c, $req->doctype);
        if ($req->doctype == MySoap::ITEM_SFP) {
            $residue_begin = $data['residue_begin'];
            $residue_end = $data['residue_end'];
            $docstable = '<table class="docstable"><tr><th>Штрих-код карты</th><th>От кого получено и кому выдано</th><th>Приход, шт.</th><th>Расход, шт.</th></tr>';
            $docs = $data['docs'];
            if (is_array($docs)) {
                foreach ($docs as $d) {
                    if (array_key_exists('card_number', $d)) {
                        if (array_key_exists('quantity', $d) && $d['quantity'] >= 0) {
                            $docstable.='<tr><td>' . $d['card_number'] . '</td><td>' . $d['partner'] . '</td><td>1</td><td></td></tr>';
                            $sfp_prihod++;
                        } else {
                            $docstable.='<tr><td>' . $d['card_number'] . '</td><td>' . $d['partner'] . '</td><td></td><td>1</td></tr>';
                            $sfp_rashod++;
                        }
                    }
                }
            }
            $docstable .= '<tr><td></td><td>Итого за день</td><td>' . $sfp_prihod . '</td><td>' . $sfp_rashod . '</td></tr>';
            $docstable .= '</table>';
            if (array_key_exists('cards', $data)) {
                $cardstable = '<div class="cards-cols">';
                $cards = $data['cards'];
                $cardsnum = count($cards);
                if (is_array($cards)) {
                    for ($i = 0; $i < $cardsnum; $i++) {
                        if ($req->has('remove_order_numbers')) {
                            $cardstable.=$cards[$i]['card_number'] . '<br>';
                        } else {
                            $cardstable.='' . ($i + 1) . ' &nbsp;' . $cards[$i]['card_number'] . '<br>';
                        }
                    }
                }
                $cardstable.='</div>';
                $html = str_replace('{{cards}}', $cardstable, $html);
            }

            $html = str_replace('{{residue_begin}}', $residue_begin, $html);
            $html = str_replace('{{residue_end}}', $residue_end, $html);
        } else {
            $docstable = '<table class="docstable"><tr><td>№ документа</td><td>Вид документа</td><td>Контрагент</td></tr>';
            if (count($data) == 0) {
//                $data = $this->getData($dateStart, $dateEnd, $subdiv->id, null, $req->doctype);
            }
            if (is_array($data)) {
                foreach ($data as $d) {
                    $docstable.='<tr><td>' . $d['number'] . '</td><td>' . $d['type_name'] . '</td><td>' . $d['fio'] . '</td></tr>';
                    switch ($d['type']) {
                        case MySoap::ITEM_REP_DOP:
                            $dops_num++;
                            break;
                        case MySoap::ITEM_REP_CLAIM:
                            $claims_num++;
                            break;
                        case MySoap::ITEM_REP_PEACE:
                            $peace_num++;
                            break;
                        case MySoap::ITEM_LOAN:
                            $loans_num++;
                            break;
                        case MySoap::ITEM_PKO:
                            $pko_num++;
                            break;
                        case MySoap::ITEM_RKO:
                            $rko_num++;
                            break;
                    }
                }
            }
            $docstable .= '</table>';


            $html = str_replace('{{loans_num}}', ($loans_num > 0) ? $loans_num : 'Отсутствует', $html);
            $html = str_replace('{{claims_num}}', ($claims_num > 0) ? $claims_num : 'Отсутствует', $html);
            $html = str_replace('{{peace_num}}', ($peace_num > 0) ? $peace_num : 'Отсутствует', $html);
            $html = str_replace('{{dops_num}}', ($dops_num > 0) ? $dops_num : 'Отсутствует', $html);
            $html = str_replace('{{pko_num}}', ($pko_num > 0) ? $pko_num : 'Отсутствует', $html);
            $html = str_replace('{{rko_num}}', ($rko_num > 0) ? $rko_num : 'Отсутствует', $html);
        }
        $html = str_replace('{{docs}}', $docstable, $html);
        $html = str_replace('{{subdivisions.name_id}}', $subdiv->name_id, $html);
        $html = str_replace('{{subdivisions.address}}', $subdiv->address, $html);
        $html = str_replace('{{subdivisions.group}}', (!is_null($subdiv->group)) ? $subdiv->group->name : '', $html);
        $html = str_replace('{{users.name}}', (is_null($user))?Auth::user()->name:$user->name, $html);
        $html = str_replace('{{start_date}}', with(new Carbon($req->start))->format('d.m.Y'), $html);
        $html = str_replace('{{end_date}}', with(new Carbon($req->end))->format('d.m.Y'), $html);
        $html = str_replace('{{sfp_prihod}}', $sfp_prihod, $html);
        $html = str_replace('{{sfp_rashod}}', $sfp_rashod, $html);

        $html = ContractEditorController::clearTags($html);
//        if(Auth::user()->id==5){
//            return $html;
//        }
        return \App\Utils\PdfUtil::getPdf($html);
//        return \App\Utils\PdfUtil::getPdf($html, null, true);
    }

    public function createPdf2(Request $req) {
        if (!$req->has('start') || !$req->has('end')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        if (with(new Carbon($req->start))->gt(new Carbon($req->end))) {
            return redirect()->back()->with('msg_err', 'Дата начала больше даты конца');
        }
        $dateStart = with(new Carbon($req->start))->format('Y-m-d');
        $dateEnd = with(new Carbon($req->end))->format('Y-m-d');

        $form = ContractForm::where('text_id', 'docsregister2')->first();
        if (is_null($form)) {
            return abort(404);
        }

        $user = ($req->has('user_id')) ? User::find($req->user_id) : Auth::user();
        $subdiv = (Auth::user()->isAdmin() && $req->has('subdivision_id')) ? Subdivision::find($req->subdivision_id) : Subdivision::find(Auth::user()->subdivision_id);
        $html = $form->template;
        $user_id_1c = ($req->has('user_id')) ? with(User::find($req->user_id))->id_1c : null;

        $data = $this->getData($dateStart, $dateEnd, $subdiv->id, $user_id_1c, $req->doctype);
        $docstable = '<table class="docstable"><tr><td>№ документа</td><td>Контрагент</td></tr>';
        $docs_num = count($data);
        if (is_array($data)) {
            foreach ($data as $d) {
                $docstable.='<tr><td>' . $d['number'] . '</td><td>' . $d['fio'] . '</td></tr>';
            }
        }
        $docstable .= '</table>';

        $html = str_replace('{{docsnum}}', ($docs_num > 0) ? $docs_num : 'Отсутствует', $html);

        $html = str_replace('{{docs}}', $docstable, $html);
        $html = str_replace('{{subdivisions.name_id}}', $subdiv->name_id, $html);
        $html = str_replace('{{subdivisions.address}}', $subdiv->address, $html);
        $html = str_replace('{{subdivisions.group}}', (!is_null($subdiv->group)) ? $subdiv->group->name : '', $html);
        $html = str_replace('{{users.name}}', $user->name, $html);
        $html = str_replace('{{start_date}}', with(new Carbon($req->start))->format('d.m.Y'), $html);
        $html = str_replace('{{end_date}}', with(new Carbon($req->end))->format('d.m.Y'), $html);

        $html = ContractEditorController::clearTags($html);
//        if(Auth::user()->id==5){
//            return $html;
//        }
        return \App\Utils\PdfUtil::getPdf($html);
//        return \App\Utils\PdfUtil::getPdf($html, null, true);
    }

}
