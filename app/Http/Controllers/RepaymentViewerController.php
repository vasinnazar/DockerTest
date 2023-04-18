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
    App\Repayment,
    Carbon\Carbon;

class RepaymentViewerController extends BasicController {

    public function __construct() {
        $this->table = 'repayments';
        $this->model = new Repayment();
        $this->useDatatables = FALSE;
    }

    public function getTableView(Request $req) {
        $cols = [
            'repayments.created_at as rep_created_at', 'repayments.id as rep_id',
            'repayments.id_1c as rep_id_1c',
            'loans.id as _loan_id', 'passports.fio as pass_fio',
            'repayment_types.name as reptype_name', 'repayment_types.text_id as reptype_text_id',
            'loans.closed as loan_closed'
        ];
        $items = Repayment::select($cols)
                ->leftJoin('loans', 'loans.id', '=', 'repayments.loan_id')
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->leftJoin('repayment_types', 'repayment_types.id', '=', 'repayments.repayment_type_id')
                ->orderBy('repayments.created_at','desc');
        if ($req->has('fio')) {
            $items->where('passports.fio', 'like', '%' . $req->fio . '%');
        }
        if ($req->has('closed')) {
            $items->where('loans.closed', $req->closed);
        }
        if ($req->has('reptype_text_id')) {
            $items->where('repayment_types.text_id', $req->reptype_text_id);
        }
        if ($req->has('loan_id')) {
            $items->where('loans.id', $req->loan_id);
        }
        if ($req->has('loan_id_1c')) {
            $items->where('loans.id_1c', $req->loan_id_1c);
        }
        return view('repayments.table')->with('items', $items->paginate(25))->with('repayment_types',  \App\RepaymentType::pluck('text_id'));
    }

    public function editItem(Request $req) {
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
        return view($this->table . '.edit')->with('item', $item);
    }

    public function getList(Request $req)
    {
        parent::getList($req);
        $cols = [
            'repayments.created_at as rep_created_at',
            'repayments.id as rep_id',
            'repayments.id_1c as rep_id_1c',
            'loans.id_1c as loan_id_1c',
            'loans.id as loan_id',
            'passports.fio as pass_fio',
            'repayment_types.name as reptype_name',
            'repayment_types.text_id as reptype_text_id'
        ];
        $items = Repayment::select($cols)
            ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('repayment_types', 'repayment_types.id', '=', 'repayment_types.loan_id');
        $collection = Datatables::of($items)
            ->editColumn('rep_created_at', function ($item) {
                return with(new Carbon($item->rep_created_at))->format('d.m.Y');
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
                $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->contract_form_id . '/' . $item->npfid),
                    ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Договор']);
                $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->npf_form_id . '/' . $item->npfid),
                    ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Заявление (из НПФ)']);
                $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->pfr_form_id . '/' . $item->npfid),
                    ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Заявление (из ПФР)']);
                $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->pd_form_id . '/' . $item->npfid),
                    ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Согласие на обработку ПД']);
                if (strstr($item->fondname, 'Доверие') !== false) {
                    $html .= HtmlHelper::Buttton(url('npf/pdf/' . $item->anketa_id . '/' . $item->npfid),
                        ['size' => 'sm', 'glyph' => 'print', 'target' => '_blank', 'text' => ' Анкета']);
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
                if (!Auth::user()->isAdmin()) {
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

}
