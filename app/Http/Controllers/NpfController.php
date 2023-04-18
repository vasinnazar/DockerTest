<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\NpfContract,
    App\NpfFond,
    Yajra\DataTables\Facades\DataTables,
    App\Utils\StrLib,
    Auth,
    App\Utils\HtmlHelper,
    App\MySoap,
    App\Customer,
    App\Passport,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    App\ContractForm,
    mikehaertl\wkhtmlto\Pdf,
    Input,
    Carbon\Carbon;

class NpfController extends BasicController {

    public function __construct() {
        $this->table = 'npf_contracts';
        $this->model = new NpfContract();
        $this->mysoapItemID = MySoap::ITEM_NPF;
    }

    public function getTableView(Request $req) {
        return parent::getTableView($req);
    }

    public function editItem(Request $req) {
//        $res = parent::editItem($req);
        $item = $this->getItemByID($req);
        if (!is_null($item)) {
            $item->contragent_fio = $item->passport->fio;
            $item->snils = $item->passport->customer->snils;
        } else {
            $item = $this->model;
            $item->user_id = Auth::user()->id;
            $item->subdivision_id = Auth::user()->subdivision_id;
        }
        //убираем доверие из списка фондов
//        if (Auth::user()->isAdmin()) {
        return view($this->table . '.edit')->with('item', $item)->with('npf_fonds', NpfFond::pluck('name', 'id'));
//        } else {
//            return view($this->table . '.edit')->with('item', $item)->with('npf_fonds', NpfFond::where('id', '<>', 8)->lists('name', 'id'));
//        }
//        $res->with('npf_fonds', NpfFond::lists('name', 'id'));
//        return $res;
    }

    public function getList(Request $req)
    {
        parent::getList($req);
        $cols = [
            'npf_contracts.id as npfid',
            'npf_contracts.created_at as npfdate',
            'npf_fonds.name as fondname',
            'passports.fio as fio',
            'customers.snils as snils',
            'npf_fonds.contract_form_id as contract_form_id',
            'npf_fonds.claim_from_npf_id as npf_form_id',
            'npf_fonds.claim_from_pfr_id as pfr_form_id',
            'npf_fonds.pd_agreement_id as pd_form_id',
            'npf_fonds.anketa_id as anketa_id',
            'npf_contracts.claimed_for_remove as npfclaimed',
            'npf_contracts.subdivision_id as subdivision'
        ];
        $items = NpfContract::select($cols)
            ->leftJoin('passports', 'passports.id', '=', 'npf_contracts.passport_id')
            ->leftJoin('customers', 'customers.id', '=', 'passports.customer_id')
            ->leftJoin('npf_fonds', 'npf_contracts.npf_fond_id', '=', 'npf_fonds.id');
        $collection = Datatables::of($items)
            ->editColumn('npfdate', function ($item) {
                return with(new Carbon($item->npfdate))->format('d.m.Y');
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group">';
                if (Auth::user()->isAdmin()) {
                    $html .= HtmlHelper::Buttton(url('npf/edit?id=' . $item->npfid),
                        ['size' => 'sm', 'glyph' => 'pencil']);
                    $html .= HtmlHelper::Buttton(url('npf/remove?id=' . $item->npfid),
                        ['size' => 'sm', 'glyph' => 'remove']);
                }
                if (is_null($item->npfclaimed)) {
                    $html .= HtmlHelper::Buttton(null, [
                        'size' => 'sm',
                        'glyph' => 'exclamation-sign',
                        'onclick' => '$.uReqsCtrl.claimForRemove(' . $item->npfid . ',' . MySoap::ITEM_NPF . '); return false;'
                    ]);
                } else {
                    $html .= HtmlHelper::Buttton(null, [
                        'size' => 'sm',
                        'glyph' => 'exclamation-sign',
                        'disabled' => true,
                        'class' => 'btn-danger btn'
                    ]);
                }
                if (strstr($item->fondname, 'САФМАР') !== false) {
                    $html .= HtmlHelper::Buttton(asset('files/pdf/safmar.pdf'),
                        ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Согласие и договор']);
                } else {
                    if (strstr($item->fondname, 'ГАЗФОНД') !== false) {
                        $html .= HtmlHelper::Buttton(asset('files/pdf/gazfond_contract.pdf'), [
                            'size' => 'sm',
                            'glyph' => 'print',
                            'target' => '_blank',
                            'text' => ' Согласие и договор'
                        ]);
                    } else {
                        if (strstr($item->fondname, 'Аквилон') === false) {
                            $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->contract_form_id . '/' . $item->npfid),
                                ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Договор']);
                            $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->pd_form_id . '/' . $item->npfid), [
                                'size' => 'sm',
                                'glyph' => 'print',
                                'target' => '_blank',
                                'text' => ' Согласие на обработку ПД'
                            ]);
                            if (strstr($item->fondname, 'Доверие') !== false) {
                            }
                        } else {
                            $html .= HtmlHelper::Buttton(asset('files/pdf/akvilon_fond.pdf'), [
                                'size' => 'sm',
                                'glyph' => 'print',
                                'target' => '_blank',
                                'text' => ' Договор для фонда (2 шт.)'
                            ]);
                            $html .= HtmlHelper::Buttton(asset('files/pdf/akvilon_client.pdf'), [
                                'size' => 'sm',
                                'glyph' => 'print',
                                'target' => '_blank',
                                'text' => ' Договор для клиента'
                            ]);
                        }
                    }
                }
                $html .= '</div>';
                return $html;
            })
            ->removeColumn('npf_form_id')
            ->removeColumn('pfr_form_id')
            ->removeColumn('pd_form_id')
            ->removeColumn('contract_form_id')
            ->removeColumn('anketa_id')
            ->removeColumn('npfclaimed')
            ->removeColumn('subdivision')
            ->filter(function ($query) use ($req) {
                if ($req->has('fio')) {
                    $query->where('fio', 'like', "%" . $req->get('fio') . "%");
                }
                if (!Auth::user()->isAdmin() && Auth::user()->id != 229) {
                    $query->where('npf_contracts.subdivision_id', Auth::user()->subdivision_id);
                }
            })
            ->rawColumns(['actions'])
            ->toJson();
        return $collection;
    }

    public function removeItem(Request $req) {
        return parent::removeItem($req);
    }

    public function updateItem(Request $req) {
        parent::updateItem($req);
        $item = NpfContract::findOrNew($req->get('id'));
        $model = $item->toArray();
        $item->fill($req->all());
        $item->user_id = (is_null($item->user_id)) ? Auth::user()->id : $item->user_id;
        $item->subdivision_id = (is_null($item->subdivision_id)) ? Auth::user()->subdivision_id : $item->subdivision_id;
        $passport = Passport::find($item->passport_id);
        if (is_null($passport)) {
            Log::error('NpfController.updateItem паспорт не найден', ['req' => $req->all()]);
            return redirect()->back()->with('msg_err', StrLib::$ERR . StrLib::$ERR_NO_CUSTOMER . '(1)')->withInput(Input::all());
        }
        $customer = $passport->customer;
        $customerSnils = $customer->snils;
        $customer->snils = $req->snils;
        DB::beginTransaction();
        if (!$customer->save() || $customer->snils == "null" || is_null($customer->snils)) {
            Log::error('NpfController.updateItem Снилс не сохранился', ['customer' => $customer, 'req' => $req->all()]);
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::$ERR . '(2)')->withInput(Input::all());
        }
        $fond = NpfFond::find($item->npf_fond_id);
        if (is_null($fond)) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::$ERR . '(5)')->withInput(Input::all());
        }
        $res1c = MySoap::addNPF([
                    'customer_id' => $customer->id_1c,
                    'snils' => $customer->snils,
                    'fond' => $fond->id_1c,
                    'contract_id_1c' => (!is_null($item->id_1c)) ? $item->id_1c : '',
                    'user_id_1c' => $item->user->id_1c,
                    'subdivision_id_1c' => $item->subdivision->name_id
        ]);
        \PC::debug($res1c, 'npf res1c');
        if (!$res1c['res'] || $res1c['value'] == "false") {
            DB::rollback();
            Log::error('NpfController.updateItem НПФ не сохранился', ['npf' => $item, 'req' => $req->all(), 'res1c' => $res1c]);
            return redirect()->back()->with('msg_err', StrLib::$ERR . '(4): Зайдите в контрагента в физ.лицах и нажмите кнопку "Сохранить"')->withInput(Input::all());
        }
        $item->id_1c = $res1c['value'];
        Spylog::log(Spylog::ACTION_UPDATE, $this->table, $customer->id, json_encode(['before' => ['snils' => $customerSnils], 'after' => ['snils' => $customer->snils]]));
        if ($item->save()) {
            DB::commit();
            if (!array_key_exists('id', $model) || is_null($model['id'])) {
                \PC::debug($item, 'item');
                Spylog::logModelAction(Spylog::ACTION_CREATE, 'npf_contracts', $item->toArray());
            } else {
                \PC::debug($model, 'model');
                Spylog::logModelChange($this->table, $model, $item->toArray());
            }
            return redirect('npf')->with('msg_suc', StrLib::$SUC_SAVED);
        } else {
            DB::rollback();
            Log::error('NpfController.updateItem НПФ не сохранился', ['npf' => $item, 'req' => $req->all()]);
            return redirect()->back()->with('msg_err', StrLib::$ERR . '(3)')->withInput(Input::all());
        }
    }

    public function createPdf($contract_id, $npf_id) {
        $contract = ContractForm::find((int) $contract_id);
        $npf = NpfContract::find((int) $npf_id);
        if (is_null($contract) || is_null($npf)) {
            abort(404);
        }
        $html = $contract->template;
        $about = \App\about_client::where('customer_id', $npf->passport->customer->id)->first();
        $params = [
            'npf_contracts' => $npf->toArray(),
            'customers' => $npf->passport->customer->toArray(),
            'passports' => $npf->passport->toArray(),
            'about_clients' => (is_null($about)) ? [] : $about->toArray(),
        ];
        $snils = $params['customers']['snils'];
        if (strlen($snils) >= 9) {
            $params['customers']['snils'] = substr($snils, 0, 3) . '-' . substr($snils, 3, 3) . '-' . substr($snils, 6, 3) . ' ' . substr($snils, 9);
        }
        $html = ContractEditorController::processParams($params, $html);
        $html = str_replace('{{full_address}}', ContractEditorController::getFullAddressString($params['passports']), $html);
        $html = str_replace('{{full_fact_address}}', ContractEditorController::getFullAddressString($params['passports'], true), $html);
        if (!empty($params['about_clients'])) {
            if ($params['about_clients']['sex']) {
                $html = str_replace('{{gender_flag1}}', 'X', $html);
                $html = str_replace('{{gender_flag0}}', '', $html);
            } else {
                $html = str_replace('{{gender_flag0}}', 'X', $html);
                $html = str_replace('{{gender_flag1}}', '', $html);
            }
        }
//        $firstPassport = Passport::where('customer_id', $params['customers']['id'])->orderBy('created_at', 'desc')->first();
//        if ($firstPassport->fio != $params['passports']['fio']) {
//            $html = str_replace('{{old_fio}}', $firstPassport->fio, $html);
//        } else {
//            $html = str_replace('{{old_fio}}', $params['passports']['fio'], $html);
//        }
        if (!is_null($npf->old_fio) && $npf->old_fio != '') {
            $html = str_replace('{{old_fio}}', $npf->old_fio, $html);
        }
        $html = ContractEditorController::clearTags($html);
        $opts = [];
        if (!is_null($npf->npf_fond) && $contract_id == $npf->npf_fond->contract_form_id) {
            $opts['margin-top'] = "0.25cm";
            $opts['margin-right'] = "0.1cm";
            $opts['margin-bottom'] = "0.25cm";
            $opts['margin-left'] = "0.1cm";
        }
        return \App\Utils\PdfUtil::getPdf($html, $opts);
    }

}
