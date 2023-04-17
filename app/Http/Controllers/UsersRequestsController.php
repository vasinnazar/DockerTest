<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\RemoveRequest;
use App\MySoap;
use Illuminate\Support\Facades\DB;
use Auth;
use App\Spylog\Spylog;
use App\Utils\HtmlHelper;

class UsersRequestsController extends Controller {

    public function __construct() {
        
    }
    /**
     * страница запросов на удаление
     * @return type
     */
    public function remove() {
        return view('usersreqs/remove');
    }
    /**
     * список для таблицы запросов на удаление
     * @param Request $request
     * @return type
     */
    public function removeRequestsList(Request $request)
    {
        $cols = [
            'remove_requests.status as req_status',
            'remove_requests.created_at as req_created_at',
            'remove_requests.doc_type as req_doc_type',
            'remove_requests.doc_id as req_doc_id',
            'users.name as requester',
            'remove_requests.comment as req_comment',
            'remove_requests.id as req_id'
        ];
        $remreqs = RemoveRequest::select($cols)->leftJoin('users', 'users.id', '=', 'remove_requests.requester_id');
        return Datatables::of($remreqs)
            ->removeColumn('req_id')
            ->editColumn('req_doc_id', function ($item) {
                $html = $item->req_doc_id;
                if ($item->req_doc_type == MySoap::ITEM_PKO || $item->req_doc_type == MySoap::ITEM_RKO) {
                    $obj = \App\Order::find($item->req_doc_id);
                    if (!is_null($obj)) {
                        $html .= '<br>' . $obj->fio;
                    }
                } else {
                    if ($item->req_doc_type >= MySoap::ITEM_REP_CLAIM && $item->req_doc_type <= MySoap::ITEM_REP_CLOSING) {
                        $obj = \App\Repayment::find($item->req_doc_id);
                        if (!is_null($obj) && !is_null($obj->loan) && !is_null($obj->loan->claim) && !is_null($obj->loan->claim->passport)) {
                            $html .= '<br>' . $obj->loan->claim->passport->fio;
                        }
                    } else {
                        if ($item->req_doc_type == MySoap::ITEM_CLAIM) {
                            $obj = \App\Claim::find($item->req_doc_id);
                            if (!is_null($obj) && !is_null($obj->passport)) {
                                $html .= '<br>' . $obj->passport->fio;
                            }
                        } else {
                            if ($item->req_doc_type == MySoap::ITEM_LOAN) {
                                $obj = \App\Loan::find($item->req_doc_id);
                                if (!is_null($obj) && !is_null($obj->claim) && !is_null($obj->claim->passport)) {
                                    $html .= '<br>' . $obj->claim->passport->fio;
                                }
                            } else {
                                if ($item->req_doc_type == MySoap::ITEM_NPF) {
                                    $obj = \App\NpfContract::find($item->req_doc_id);
                                    if (!is_null($obj)) {
                                        $html .= '<br>' . $obj->passport->fio;
                                    }
                                } else {
                                    if ($item->req_doc_type == MySoap::ITEM_ISSUE_CLAIM) {
                                        $obj = \App\IssueClaim::find($item->req_doc_id);
                                        if (!is_null($obj)) {
                                            $html .= '<br>' . $obj->user->name;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $html;
            })
            ->editColumn('req_status', function ($item) {
                $html = '';
                if ($item->req_status == RemoveRequest::STATUS_CLAIMED) {
                    $html .= '<big><span class="glyphicon glyphicon-question-sign info"></span></big>';
                } else {
                    if ($item->req_status == RemoveRequest::STATUS_DONE) {
                        $html .= '<big><span class="glyphicon glyphicon-ok-sign success"></span></big>';
                    }
                }
                $html .= '<div class="visible-xs hidden-sm hidden-lg">';
                if (Auth::user()->isAdmin()) {
                    if ($item->req_doc_type == MySoap::ITEM_PKO || $item->req_doc_type == MySoap::ITEM_RKO) {
                        if ($item->req_status == RemoveRequest::STATUS_DONE) {
                            $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                        } else {
                            $html .= HtmlHelper::Buttton(url('orders/remove/' . $item->req_doc_id),
                                ['glyph' => 'remove']);
                            $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                        }
                    } else {
                        if ($item->req_doc_type >= MySoap::ITEM_REP_CLAIM && $item->req_doc_type <= MySoap::ITEM_REP_CLOSING) {
                            if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                            } else {
                                $html .= HtmlHelper::Buttton(url('repayments/remove/' . $item->req_doc_id),
                                    ['glyph' => 'remove']);
                                $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                    ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                            }
                        } else {
                            if ($item->req_doc_type == MySoap::ITEM_CLAIM) {
                                if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                    $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                                } else {
                                    $html .= HtmlHelper::Buttton(url('claims/mark4remove/' . $item->req_doc_id),
                                        ['glyph' => 'remove']);
                                    $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                        ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                }
                            } else {
                                if ($item->req_doc_type == MySoap::ITEM_LOAN) {
                                    if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                        $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                                    } else {
                                        $html .= HtmlHelper::Buttton(url('loans/remove/' . $item->req_doc_id),
                                            ['glyph' => 'remove']);
                                        $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                            ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                    }
                                } else {
                                    if ($item->req_doc_type == MySoap::ITEM_NPF) {
                                        if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                            $html .= HtmlHelper::Buttton(null,
                                                ['glyph' => 'remove', 'disabled' => true]);
                                        } else {
                                            $html .= HtmlHelper::Buttton(url('npf/remove?id=' . $item->req_doc_id),
                                                ['glyph' => 'remove']);
                                            $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                                ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                        }
                                    } else {
                                        if ($item->req_doc_type == MySoap::ITEM_ISSUE_CLAIM) {
                                            if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                                $html .= HtmlHelper::Buttton(null,
                                                    ['glyph' => 'remove', 'disabled' => true]);
                                            } else {
                                                $html .= HtmlHelper::Buttton(url('orders/issueclaims/delete/' . $item->req_doc_id),
                                                    ['glyph' => 'remove']);
                                                $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                                    ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($item->req_status == RemoveRequest::STATUS_DONE) {
                    $html .= HtmlHelper::Buttton(null, ['disabled' => true, 'glyph' => 'eye-open']);
                } else {
                    $html .= HtmlHelper::Buttton(null, [
                        'onclick' => '$.uReqsCtrl.showInfo(' . $item->req_id . '); return false;',
                        'glyph' => 'eye-open'
                    ]);
                }
                if (Auth::user()->isAdmin()) {
                    $html .= '<a class="btn btn-default btn-sm" href="' . url('usersreqs/hide/' . $item->req_id) . '"><span class="glyphicon glyphicon-eye-close"></span></a>';
                }
                $html .= '</div>';
                return $html;
            })
            ->editColumn('req_created_at', function ($item) {
                return with(new Carbon($item->req_created_at))->format('d.m.Y H:i:s');
            })
            ->editColumn('req_doc_type', function ($item) {
                switch ($item->req_doc_type) {
                    case MySoap::ITEM_CLAIM:
                        $res = 'Заявка';
                        break;
                    case MySoap::ITEM_LOAN:
                        $res = 'Кредитный договор';
                        break;
                    case MySoap::ITEM_RKO:
                        $res = 'Расходный кассовый ордер';
                        break;
                    case MySoap::ITEM_REP_CLAIM:
                        $res = 'Соглашение о приостановке процентов';
                        break;
                    case MySoap::ITEM_REP_DOP:
                        $res = 'Доп. договор';
                        break;
                    case MySoap::ITEM_REP_PEACE:
                        $res = 'Согл. об урег-ии задолж';
                        break;
                    case MySoap::ITEM_REP_SUZ:
                        $res = 'СУЗ';
                        break;
                    case MySoap::ITEM_REP_CLOSING:
                        $res = 'Закрытие';
                        break;
                    case MySoap::ITEM_PKO:
                        $res = 'Приход';
                        break;
                    case MySoap::ITEM_NPF:
                        $res = 'Договор НПФ';
                        break;
                    case MySoap::ITEM_ISSUE_CLAIM:
                        $res = 'Заявка на подотчет';
                        break;
                }
                return $res;
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group btn-group-sm">';
                if (Auth::user()->isAdmin()) {
                    if ($item->req_doc_type == MySoap::ITEM_PKO || $item->req_doc_type == MySoap::ITEM_RKO) {
                        if ($item->req_status == RemoveRequest::STATUS_DONE) {
                            $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                        } else {
                            $html .= HtmlHelper::Buttton(url('orders/remove/' . $item->req_doc_id),
                                ['glyph' => 'remove']);
                            $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                        }
                    } else {
                        if ($item->req_doc_type >= MySoap::ITEM_REP_CLAIM && $item->req_doc_type <= MySoap::ITEM_REP_CLOSING) {
                            if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                            } else {
                                $html .= HtmlHelper::Buttton(url('repayments/remove/' . $item->req_doc_id),
                                    ['glyph' => 'remove']);
                                $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                    ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                            }
                        } else {
                            if ($item->req_doc_type == MySoap::ITEM_CLAIM) {
                                if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                    $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                                } else {
                                    $html .= HtmlHelper::Buttton(url('claims/mark4remove/' . $item->req_doc_id),
                                        ['glyph' => 'remove']);
                                    $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                        ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                }
                            } else {
                                if ($item->req_doc_type == MySoap::ITEM_LOAN) {
                                    if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                        $html .= HtmlHelper::Buttton(null, ['glyph' => 'remove', 'disabled' => true]);
                                    } else {
                                        $html .= HtmlHelper::Buttton(url('loans/remove/' . $item->req_doc_id),
                                            ['glyph' => 'remove']);
                                        $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                            ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                    }
                                } else {
                                    if ($item->req_doc_type == MySoap::ITEM_NPF) {
                                        if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                            $html .= HtmlHelper::Buttton(null,
                                                ['glyph' => 'remove', 'disabled' => true]);
                                        } else {
                                            $html .= HtmlHelper::Buttton(url('npf/remove?id=' . $item->req_doc_id),
                                                ['glyph' => 'remove']);
                                            $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                                ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                        }
                                    } else {
                                        if ($item->req_doc_type == MySoap::ITEM_ISSUE_CLAIM) {
                                            if ($item->req_status == RemoveRequest::STATUS_DONE) {
                                                $html .= HtmlHelper::Buttton(null,
                                                    ['glyph' => 'remove', 'disabled' => true]);
                                            } else {
                                                $html .= HtmlHelper::Buttton(url('orders/issueclaims/delete/' . $item->req_doc_id),
                                                    ['glyph' => 'remove']);
                                                $html .= HtmlHelper::Buttton(url('usersreqs/remove/request?id=' . $item->req_id),
                                                    ['glyph' => 'minus', 'title' => 'Убрать пометку']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($item->req_status == RemoveRequest::STATUS_DONE) {
                    $html .= HtmlHelper::Buttton(null, ['disabled' => true, 'text' => 'Подробности']);
                } else {
                    $html .= HtmlHelper::Buttton(null, [
                        'onclick' => '$.uReqsCtrl.showInfo(' . $item->req_id . '); return false;',
                        'text' => 'Подробности'
                    ]);
                }
                if (Auth::user()->isAdmin()) {
                    $html .= '<a class="btn btn-default btn-sm" href="' . url('usersreqs/hide/' . $item->req_id) . '"><span class="glyphicon glyphicon-eye-close"></span> Скрыть запрос</a>';
                }
                $html .= '</div>';
                return $html;
            })
            ->filter(function ($query) use ($request) {

            })
            ->setTotalRecords(1000)
            ->make();
    }
    /**
     * подать заявку на удаление
     * @param Request $req
     * @return type
     */
    public function claimForRemove(Request $req) {
        if (!$req->has('id') || !$req->has('comment') || !$req->has('doctype')) {
            return redirect()->back()->with('msg', 'Не все параметры')->with('class', 'alert-danger');
        }
        if (RemoveRequest::where('id', $req->id)->where('doc_type', $req->doctype)->count() > 0) {
            return redirect()->back()->with('msg', 'Заявка уже подана')->with('class', 'alert-danger');
        }
        $remreq = new RemoveRequest();
        $remreq->doc_id = $req->id;
        $remreq->doc_type = $req->doctype;
        $remreq->requester_id = Auth::user()->id;
        $remreq->status = RemoveRequest::STATUS_CLAIMED;
        $remreq->comment = $req->comment;

        if ($req->doctype == MySoap::ITEM_RKO || $req->doctype == MySoap::ITEM_PKO) {
            $item = \App\Order::find($req->id);
            $table = 'orders';
        } else if ($req->doctype >= MySoap::ITEM_REP_CLAIM && $req->doctype <= MySoap::ITEM_REP_CLOSING) {
            $item = \App\Repayment::find($req->id);
            $table = 'repayments';
        } else if ($req->doctype == MySoap::ITEM_CLAIM) {
            $item = \App\Claim::find($req->id);
            $table = 'claims';
        } else if ($req->doctype == MySoap::ITEM_LOAN) {
            $item = \App\Loan::find($req->id);
            $table = 'loans';
        } else if ($req->doctype == MySoap::ITEM_NPF) {
            $item = \App\NpfContract::find($req->id);
            $table = 'npf_contracts';
        }
        if (is_null($item)) {
            return redirect()->back()->with('msg', 'Объект не найден')->with('class', 'alert-danger');
        }
        $item->claimed_for_remove = Carbon::now()->format('Y-m-d H:i:s');
        DB::beginTransaction();
        if (!$item->save()) {
            DB::rollback();
            Log::error('ошибка при подаче заявки на удаление', $item->toArray());
            return redirect()->back()->with('msg', 'Заявка уже подана')->with('class', 'alert-danger');
        }
        if (!$remreq->save()) {
            DB::rollback();
            Log::error('ошибка при подаче заявки на удаление', $item->toArray());
            return redirect()->back()->with('msg', 'Заявка уже подана')->with('class', 'alert-danger');
        }
        Spylog::log(Spylog::ACTION_CLAIM_FOR_REMOVE, $table, $req->id);
        DB::commit();
        return redirect()->back()->with('msg', 'Заявка принята на рассмотрение')->with('class', 'alert-success');
    }
    /**
     * Скрыть запрос на удаление
     * @param type $id
     * @return type
     */
    public function hideRequest($id) {
        $remreq = RemoveRequest::find($id);
        if (!is_null($remreq)) {
            if ($remreq->delete()) {
                return redirect()->back()->with('msg', 'Запрос успешно скрыт')->with('class', 'alert-success');
            } else {
                return redirect()->back()->with('msg', 'Ошибка!')->with('class', 'alert-danger');
            }
        } else {
            return redirect()->back()->with('msg', 'Запрос не найден')->with('class', 'alert-danger');
        }
    }
    /**
     * Удалить запрос на удаление
     * @param Request $req
     * @return type
     */
    public function removeRequest(Request $req) {
        $userreq = RemoveRequest::where('id', $req->id)->where('status', '<>', RemoveRequest::STATUS_DONE)->first();
        if (!is_null($userreq)) {
            if ($userreq->doc_type == MySoap::ITEM_PKO || $userreq->doc_type == MySoap::ITEM_RKO) {
                $item = \App\Order::find($userreq->doc_id);
                if(!is_null($item)){
                    $item->claimed_for_remove = null;
                    $item->save();
                } else {
                    return redirect()->back()->with('msg_err',  \App\Utils\StrLib::ERR_NULL);
                }
            } else if ($userreq->doc_type >= MySoap::ITEM_REP_CLAIM && $userreq->doc_type <= MySoap::ITEM_REP_CLOSING) {
                $item = \App\Repayment::find($userreq->doc_id);
                if(!is_null($item)){
                    $item->claimed_for_remove = null;
                    $item->save();
                } else {
                    return redirect()->back()->with('msg_err',  \App\Utils\StrLib::ERR_NULL);
                }
            } else if ($userreq->doc_type == MySoap::ITEM_CLAIM) {
                $item = \App\Claim::find($userreq->doc_id);
                if(!is_null($item)){
                    $item->claimed_for_remove = null;
                    $item->save();
                } else {
                    return redirect()->back()->with('msg_err',  \App\Utils\StrLib::ERR_NULL);
                }
            } else if ($userreq->doc_type == MySoap::ITEM_LOAN) {
                $item = \App\Loan::find($userreq->doc_id);
                if(!is_null($item)){
                    $item->claimed_for_remove = null;
                    $item->save();
                } else {
                    return redirect()->back()->with('msg_err',  \App\Utils\StrLib::ERR_NULL);
                }
            } else if ($userreq->doc_type == MySoap::ITEM_NPF) {
                $item = \App\NpfContract::find($userreq->doc_id);
                if(!is_null($item)){
                    $item->claimed_for_remove = null;
                    $item->save();
                } else {
                    return redirect()->back()->with('msg_err',  \App\Utils\StrLib::ERR_NULL);
                }
            } else if ($userreq->doc_type == MySoap::ITEM_ISSUE_CLAIM) {
                $item = \App\IssueClaim::find($userreq->doc_id);
                if(!is_null($item)){
                    $item->claimed_for_remove = null;
                    $item->save();
                } else {
                    return redirect()->back()->with('msg_err',  \App\Utils\StrLib::ERR_NULL);
                }
            }
            $userreq->delete();
            return redirect()->back()->with('msg_suc','Пометка на удаление снята');
        } else {
            return redirect()->back()->with('msg_err', \App\Utils\StrLib::ERR_NULL);
        }
    }
    /**
     * Получить инфу по запросу на удаление
     * @param int $id
     * @return string
     */
    public function getRemoveRequestInfo($id) {
        $remreq = RemoveRequest::find($id);
        if (is_null($remreq)) {
            return 'Запрос не найден';
        }
        $html = '';
        switch ($remreq->doc_type) {
            case MySoap::ITEM_CLAIM:
                $claim = \App\Claim::find($remreq->doc_id);
                if (is_null($claim)) {
                    return 'Заявка не найдена';
                }
                $id_1c = $claim->id_1c;
                $passport = $claim->passport;
                break;
            case MySoap::ITEM_LOAN:
                $loan = \App\Loan::find($remreq->doc_id);
                if (is_null($loan)) {
                    return 'Кредитный договор не найден';
                } else if (is_null($loan->claim)) {
                    return 'Заявка не найдена';
                }
                $id_1c = $loan->id_1c;
                $passport = $loan->claim->passport;
                break;
            case MySoap::ITEM_RKO:
            case MySoap::ITEM_PKO:
                $order = \App\Order::find($remreq->doc_id);
                if (is_null($order)) {
                    return 'Ордер не найден';
                }
                $id_1c = $order->number;
                $passport = $order->passport;
                break;
            case MySoap::ITEM_REP_CLAIM:
            case MySoap::ITEM_REP_DOP:
            case MySoap::ITEM_REP_PEACE:
            case MySoap::ITEM_REP_SUZ:
            case MySoap::ITEM_REP_CLOSING:
                $rep = \App\Repayment::find($remreq->doc_id);
                if (is_null($rep)) {
                    return 'Договор не найден';
                }
                $id_1c = $rep->id_1c;
                if (is_null($rep->loan)) {
                    return 'Кредитный договор не найден';
                }
                if (is_null($rep->loan->claim)) {
                    return 'Заявка не найдена';
                }
                $html .= '<p>Идентификатор кредитного договора: ' . $rep->loan->id . '</p>';
                $passport = $rep->loan->claim->passport;
                break;
            case MySoap::ITEM_NPF:
                $npf = \App\NpfContract::find($remreq->doc_id);
                if (is_null($npf)) {
                    return 'Договор не найден';
                }
                $id_1c = $npf->id;
                $passport = $npf->passport;
                break;
        }
        if (!isset($passport) || is_null($passport)) {
            return 'Паспорт не найден';
        }
        $html .= '<p>Контрагент: ' . $passport->fio . '</p>'
                . '<p>Серия: ' . $passport->series . '</p>'
                . '<p>Номер: ' . $passport->number . '</p>'
                . ((isset($claim) && !is_null($claim)) ? '<p>Статус:' . \App\Claim::getStatusName($claim->status) . '</p>' : '')
                . '<p>Идентификатор объекта в 1С: ' . $id_1c . '</p>';
        return $html;
    }

}
