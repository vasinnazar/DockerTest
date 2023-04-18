<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\IssueClaim;
use Yajra\DataTables\Facades\DataTables;
use Auth;
use Carbon\Carbon;
use App\MySoap;
use App\Utils\StrLib;

class IssueClaimController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
        $this->mysoapItemID = MySoap::ITEM_ISSUE_CLAIM;
        $this->model = new IssueClaim();
        $this->table = 'issue_claims';
    }

    public function index() {
        $data = [];
        return view('orders.issue_claims', $data);
    }
    /**
     * Список заявок для таблицы
     * @param Request $req
     * @return type
     */
    public function ajaxList(Request $req) {
        $cols = [
            'issue_claims.created_at as ic_created_at',
            'order_types.name as ot_name',
            'issue_claims.id_1c as ic_number',
            'issue_claims.money as ic_money',
            'users.name as u_name',
            'passports.fio as p_fio',
            'issue_claims.id as ic_id',
            'issue_claims.claimed_for_remove as ic_claimed_for_remove'
        ];
        $today = Carbon::today();
        $issueClaims = IssueClaim::select($cols)
                ->leftJoin('order_types', 'order_types.id', '=', 'issue_claims.order_type_id')
                ->leftJoin('users', 'users.id', '=', 'issue_claims.user_id')
                ->leftJoin('passports', 'passports.id', '=', 'issue_claims.passport_id')
                ->whereBetween('issue_claims.created_at', [Carbon::today()->setDate($today->year, 1, 1)->subMonth(), Carbon::tomorrow()])
                ->limit(300)
                ->orderBy('issue_claims.created_at', 'desc');
        if (!Auth::user()->isAdmin()) {
            $issueClaims->where('issue_claims.subdivision_id', Auth::user()->subdivision_id);
        }
        if ($req->has('username')) {
            $issueClaims->where('users.name', 'like', '%' . $req->get('username') . '%');
        }
        if ($req->has('issue_claim_id_1c')) {
            $issueClaims->where('issue_claims.id_1c', '=', $req->get('issue_claim_id_1c'));
        }
        if ($req->has('issue_claim_created_at_min')) {
            $issueClaims->where('issue_claims.created_at', '>=', $req->get('issue_claim_created_at_min'));
        }
        if ($req->has('issue_claim_created_at_max')) {
            $issueClaims->where('issue_claims.created_at', '<=', with(new Carbon($req->get('issue_claim_created_at_min')))->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
        }
        return Datatables::of($issueClaims)
            ->editColumn('ic_created_at', function ($item) {
                return with(new Carbon($item->ic_created_at))->format('d.m.Y H:i:s');
            })
            ->editColumn('ic_money', function ($item) {
                return ($item->ic_money / 100) . ' руб.';
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group">';
                if (Auth::user()->isAdmin()) {
                    $html .= \App\Utils\HtmlHelper::Buttton(url('orders/issueclaims/delete/' . $item->ic_id),
                        ['glyph' => 'remove']);
                }
                $html .= \App\Utils\HtmlHelper::Buttton(url('orders/issueclaims/pdf/' . $item->ic_id),
                    ['glyph' => 'print', 'target' => '_blank']);
                if (Auth::user()->isAdmin()) {
                    $html .= \App\Utils\HtmlHelper::Buttton(url('orders/issueclaims/pdf/' . $item->ic_id . '?type=2'),
                        ['glyph' => 'print', 'target' => '_blank']);
                }
                $html .= \App\Utils\HtmlHelper::Buttton(null,
                    ['glyph' => 'eye-open', 'onclick' => '$.issueClaimCtrl.openClaim(' . $item->ic_id . ')']);
                if (Auth::user()->isAdmin()) {
                    $html .= \App\Utils\HtmlHelper::Buttton(null,
                        ['glyph' => 'pencil', 'onclick' => '$.issueClaimCtrl.editClaim(' . $item->ic_id . ')']);
                }
                if (!empty($item->ic_claimed_for_remove)) {
                    $html .= \App\Utils\HtmlHelper::Buttton(null,
                        ['glyph' => 'alert', 'class' => 'btn btn-danger', 'disabled' => true]);
                } else {
                    $html .= \App\Utils\HtmlHelper::Buttton(url('orders/issueclaims/claimforremove/' . $item->ic_id),
                        ['glyph' => 'alert']);
                }
                $html .= '</div>';
                return $html;
            })
            ->removeColumn('ic_id')
            ->removeColumn('ic_claimed_for_remove')
            ->rawColumn(['actions'])
            ->toJson();
    }

    /**
     * Запрос данных по заявке аяксом (на форму редактирования)
     * @param int $id
     * @param Request $req
     * @return int
     */
    public function ajaxView($id, Request $req) {
        $issueClaim = IssueClaim::with('subdivision', 'user', 'passport')->find($id);
        if (is_null($issueClaim)) {
            return 0;
        }
        if ($req->has('only_claim')) {
            return $issueClaim;
        }
        $res1c = MySoap::sendExchangeArm(MySoap::createXML([
                            'type' => 'CheckClaimForIssue',
                            'claim_id' => $issueClaim->id_1c
        ]));
        if (Auth::user()->id == 5) {
//            if (isset($res1c->agreed) && (string) $res1c->agreed == 'Да') {
//                \App\Synchronizer::updateOrders($issueClaim->created_at->format('Y-m-d'), null, null, $issueClaim->subdivision->name_id);
//            }
        }
        return json_decode(json_encode($res1c), true);
    }

    /**
     * удаление заявки из арма
     * @param integer $id идентификатор заявки
     * @return type
     */
    public function delete($id) {
        $item = IssueClaim::find($id);
        if (is_null($item)) {
            return $this->backWithErr(\App\Utils\StrLib::ERR_NULL);
        }
        $delete = $item->deleteThrough1c();
        if ($delete->result) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr($delete->error);
        }
    }
    /**
     * Подать заявку на подотчет на удаление
     * @param int $id
     * @return type
     */
    public function claimForRemove($id) {
        $item = IssueClaim::find($id);
        if (is_null($item)) {
            return $this->backWithErr(\App\Utils\StrLib::ERR_NULL);
        }
        $res = $item->claimForRemove();
        if ($res->result) {
            return $this->backWithSuc();
        } else {
            return $this->backWithErr($delete->error);
        }
    }

    /**
     * Обновить заявку на подотчет
     * @param Request $req
     * @return int
     */
    public function update(Request $req) {
        $ic = IssueClaim::find($req->get('id'));
        if (is_null($ic)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $ic->fill($req->input());
        $ic->save();
        \PC::debug($ic);
        return 1;
    }

    /**
     * Открыть пдф с заявкой на подотчет
     * Существует две формы: для командировочных и для всего остального.
     * @param integer $id
     * @param Request $req
     * @return type
     */
    public function createPdf($id, Request $req) {
        $issueClaim = IssueClaim::find($id);
        if (is_null($issueClaim)) {
            return $this->backWithErr(\App\Utils\StrLib::ERR_NULL);
        }
        if ($req->has('type') && $req->type == '2') {
            $contractForm = \App\ContractForm::where('text_id', 'issue_claim2')->first();
        } else {
            $contractForm = \App\ContractForm::where('text_id', 'issue_claim')->first();
        }
        if (is_null($contractForm)) {
            return $this->backWithErr('Форма не найдена');
        }
        $html = $contractForm->template;
        if (!is_null($issueClaim->data)) {
            $ic_items = json_decode($issueClaim->data);
            $issue_claims_items_table = '<table class="items-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>№ п./п.</th>
                                <th>Цели, на которые выдается сумма в подотчет</th>
                                <th>Сумма, руб.</th>
                            </tr>
                        </thead>
                        <tbody>';
            $i = 1;
            foreach ($ic_items as $item) {
                $issue_claims_items_table.='<tr><td>' . $i . '</td><td>' . $item->ic_goal . '</td><td>' . $item->ic_money . '</td></tr>';
                $i++;
            }
            $issue_claims_items_table .= '</tbody></table>';
            $html = str_replace('{{issue_claims_items_table}}', $issue_claims_items_table, $html);
        }
        $html = str_replace('{{issue_claims.money}}', \App\StrUtils::kopToRub($issueClaim->money), $html);
        $html = str_replace('{{issue_claims.created_at}}', $issueClaim->created_at->format('d.m.Y'), $html);
        $html = str_replace('{{num2str(issue_claims.money,true)}}', \App\StrUtils::num2str(\App\StrUtils::kopToRub($issueClaim->money), true), $html);
        $html = str_replace('{{users.name}}', $issueClaim->user->name, $html);
        $html = str_replace('{{passports.fio}}', $issueClaim->passport->fio, $html);
        $html = ContractEditorController::replaceConfigVars($html);
        $html = ContractEditorController::clearTags($html);
        return \App\Utils\PdfUtil::getPdf($html);
    }

}
