<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Auth,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Card,
    App\User,
    App\Cashbook,
    App\Subdivision,
    App\Order,
    App\OrderType,
    App\ContractForm,
    App\Repayment,
    App\StrUtils,
    App\MySoap,
    App\Passport,
    App\RemoveRequest,
    Illuminate\Support\Facades\DB,
    yajra\Datatables\Datatables,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    mikehaertl\wkhtmlto\Pdf,
    Illuminate\Support\Facades\Response,
    Log,
    App\Utils\StrLib,
    App\Utils\FileToPdfUtil,
    Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use App\Permission;

class OrdersController extends BasicController {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Открывает страницу с ордерами, либо для подразделения на котором зарегестрирован пользователь, либо для подразделения с переданным идентификатором
     * @param int $id идентификатор подразделения
     * @return \Illuminate\Support\Facades\View
     */
    public function getOrdersAjax($id = null) {
        if (Auth::user()->isAdmin()) {
            $order_types = OrderType::whereNotIn('text_id', [OrderType::URALINCASS, OrderType::SALARY91])->get();
        } else {
            $order_types = OrderType::whereNotIn('text_id', [OrderType::URALINCASS, OrderType::SALARY91, OrderType::PKO, OrderType::RASHOD, OrderType::VPKO,  OrderType::BANK1, OrderType::PODOTCHET, OrderType::DEFICITRKO])->get();
        }

        return view('orders.orders', ['subdivision_id' => ((is_null($id)) ? Auth::user()->subdivision_id : $id), 'order_types' => $order_types]);
    }

    public function getOrders(Request $request) {
        return $this->getOrdersAjax($request);
//        return $this->getList($request);
    }

    public function getList(Request $request) {
        $search_params = ['order_number', 'fio', 'series', 'number', 'responsible', 'created_at_min', 'created_at_max', 'subdivision_id', 'plus'];
        if (Auth::user()->isAdmin()) {
            $subdiv_id = ($request->has('subdivision_id')) ? (($request->subdivision_id == Auth::user()->subdivision_id) ? null : $request->subdivision_id) : null;
        } else {
            $subdiv_id = Auth::user()->subdivision_id;
        }
        $cols = ['orders.created_at as order_created_at',
            'order_types.name as order_type',
            'order_types.plus as order_plus',
            'orders.number as order_number',
            'orders.money as order_money',
            'orders.id as order_id',
            'users.name as responsible',
            'passports.fio as customer_fio',
            'orders.claimed_for_remove as order_claimed_for_remove'
        ];
        $orders = Order::select($cols)
                ->leftJoin('passports', 'passports.id', '=', 'orders.passport_id')
                ->leftJoin('users', 'users.id', '=', 'orders.user_id')
                ->leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                ->orderBy('orders.created_at', 'desc')
                ->whereNotNull('orders.number')
                ->where('orders.number', '<>', '');
        if (!is_null($subdiv_id)) {
            $orders->where('orders.subdivision_id', $subdiv_id);
        }
        if ($request->has('order_number')) {
            $orders->where('orders.number', 'like', "%" . $request->get('order_number') . "%");
        }
        if ($request->has('fio')) {
            $orders->where('passports.fio', 'like', "%" . $request->get('fio') . "%");
        }
        if ($request->has('series')) {
            $orders->where('passports.series', '=', $request->get('series'));
        }
        if ($request->has('number')) {
            $orders->where('passports.number', '=', $request->get('number'));
        }
        if ($request->has('responsible')) {
            $orders->where('users.name', 'like', "%" . $request->get('responsible') . "%");
        }
        if ($request->has('created_at_min')) {
            $orders->where('orders.created_at', '>=', with(new Carbon($request->get('created_at_min')))->format('Y-m-d'));
            \PC::debug('date');
            if (!$request->has('fio') && !$request->has('series') && !$request->has('number') && !$request->has('responsible')) {
                if ($request->has('subdivision_id')) {
                    $subdiv = Subdivision::find($request->subdivision_id);
                } else {
                    $subdiv = Auth::user()->subdivision;
                }
                if (!is_null($subdiv)) {
                    \PC::debug($subdiv, 'search');
                    \App\Synchronizer::updateOrders($request->created_at_min, null, null, $subdiv->name_id);
                }
            }
        }
        if ($request->has('created_at_max')) {
            $orders->where('orders.created_at', '<=', with(new Carbon($request->get('created_at_max')))->format('Y-m-d'));
        }
        if ($request->has('subdivision_id')) {
            $orders->where('orders.subdivision_id', $request->get('subdivision_id'));
        }
        if ($request->has('plus') && $request->get('plus') != '') {
            $orders->where('order_types.plus', $request->get('plus'));
        }
        $onlymonth = true;
        foreach ($search_params as $sp) {
            if ($request->has($sp)) {
                $onlymonth = false;
                break;
            }
        }
        if ($onlymonth) {
            $orders->where('orders.created_at', '>=', Carbon::now()->subMonth()->format('Y-m-d'));
        }
        return view('orders.orders_noajax', [
            'subdivision_id' => Auth::user()->subdivision_id,
            'order_types' => OrderType::whereNotIn('text_id', ['URALINCASS', 'SALARY91'])->get(),
            'orders' => $orders->paginate(25)
        ]);
    }

    /**
     * возвращает ордер
     * @param int $id идентификатор ордера
     * @return \App\Order
     */
    public function getOrder($id) {
        return Order::where('id', $id)->with('passport')->with('subdivision')->with('user')->first();
    }

    /**
     * Получает список ордеров аяксом для таблицы
     * @param Request $request
     * @return \yajra\Datatables\Facades\Datatables
     */
    public function getOrdersList(Request $request) {
        $search_params = ['order_number', 'fio', 'series', 'number', 'responsible', 'created_at_min', 'created_at_max', 'subdivision_id', 'plus'];
//        if (Auth::user()->isAdmin()) {
//            $subdiv_id = ($request->has('subdivision_id')) ? (($request->subdivision_id == Auth::user()->subdivision_id) ? null : $request->subdivision_id) : null;
//        } else {
//            $subdiv_id = Auth::user()->subdivision_id;
//        }
        if (Auth::user()->hasPermission(Permission::makeName(Permission::ACTION_SELECT, 'orders', Permission::COND_ALL, Permission::TIME_ALL))) {
            \PC::debug('wow');
            $subdiv_id = ($request->has('subdivision_id')) ? (($request->subdivision_id == Auth::user()->subdivision_id) ? null : $request->subdivision_id) : null;
        } else {
            \PC::debug('no');
            $subdiv_id = Auth::user()->subdivision_id;
        }
        if ($request->has('created_at_min')) {
            if ($request->has('subdivision_id')) {
                $subdiv = Subdivision::find($request->subdivision_id);
            } else {
                $subdiv = Auth::user()->subdivision;
            }
            if (!is_null($subdiv)) {
                \App\Synchronizer::updateOrders($request->created_at_min, null, null, $subdiv->name_id);
            }
        }
//        $subdiv_id = ($request->has('subdivision_id') && Auth::user()->isAdmin()) ? $request->subdivision_id : Auth::user()->subdivision_id;
        $cols = ['orders.created_at as order_created_at',
            'order_types.name as order_type',
            'order_types.plus as order_plus',
            'orders.number as order_number',
            'orders.money as order_money',
            'orders.id as order_id',
            'users.name as responsible',
            'passports.fio as customer_fio',
            'orders.fio as order_fio',
            'orders.claimed_for_remove as order_claimed_for_remove',
            'orders.repayment_id as order_repayment_id'
        ];
        $orders = Order::select($cols)
                ->leftJoin('passports', 'passports.id', '=', 'orders.passport_id')
                ->leftJoin('users', 'users.id', '=', 'orders.user_id')
                ->leftJoin('order_types', 'order_types.id', '=', 'orders.type')
                ->whereNotNull('orders.number')
                ->where('orders.number', '<>', '');
        if (!is_null($subdiv_id)) {
            $orders->where('orders.subdivision_id', $subdiv_id);
        }
        if ($request->has('order_number')) {
            $orders->where('orders.number', 'like', "%" . $request->get('order_number') . "%");
        }
        if ($request->has('fio')) {
//            $orders->where('passports.fio', 'like', "%" . $request->get('fio') . "%");
            $orders->whereRaw('(passports.fio like "%' . $request->get('fio') . '%" or orders.fio like "%' . $request->get('fio') . '%")');
        }
        if ($request->has('series')) {
            $orders->where('passports.series', '=', $request->get('series'));
        }
        if ($request->has('number')) {
            $orders->where('passports.number', '=', $request->get('number'));
        }
        if ($request->has('responsible')) {
            $orders->where('users.name', 'like', "%" . $request->get('responsible') . "%");
        }
        if ($request->has('created_at_min')) {
            $orders->where('orders.created_at', '>=', with(new Carbon($request->get('created_at_min')))->format('Y-m-d'));
        }
        if ($request->has('created_at_max')) {
            $orders->where('orders.created_at', '<=', with(new Carbon($request->get('created_at_max')))->format('Y-m-d'));
        }
        if ($request->has('subdivision_id')) {
            $orders->where('orders.subdivision_id', $request->get('subdivision_id'));
        }
        if ($request->has('plus') && $request->get('plus') != '') {
            $orders->where('order_types.plus', $request->get('plus'));
        }
        if ($request->has('series') && $request->has('number') && $request->has('created_at_min')) {
            \App\Synchronizer::updateOrders($request->created_at_min, $request->series, $request->number);
        }
        $onlymonth = true;
        foreach ($search_params as $sp) {
            if ($request->has($sp)) {
                $onlymonth = false;
                break;
            }
        }
        if ($onlymonth) {
            $orders->where('orders.created_at', '>=', Carbon::now()->subDay()->format('Y-m-d'));
        }
        if (Auth::user()->isAdmin()) {
            $orders->limit(100);
        }
        return Datatables::of($orders)
                        ->editColumn('order_created_at', function($order) {
                            return with(new Carbon($order->order_created_at))->format('d.m.Y H:i:s');
                        })
                        ->editColumn('order_money', function($order) {
                            return ($order->order_money / 100) . ' руб.';
                        })
                        ->editColumn('customer_fio', function($order) {
                            if (is_null($order->customer_fio) || $order->customer_fio == '') {
                                return $order->order_fio;
                            }
                            return $order->customer_fio;
                        })
                        ->addColumn('actions', function($order) {
                            $html = '<div class="btn-group">';
                            $html .= '<a href="' . url('orders/pdf/' . $order->order_id) . '" '
                                    . 'class="btn btn-default btn-sm" target="_blank">'
                                    . '<span class="glyphicon glyphicon-print"></span></a>';
                            if (is_null($order->order_claimed_for_remove) && with(new Carbon($order->order_created_at))->isToday() && (Auth::user()->isAdmin() || is_null($order->order_repayment_id))) {
                                $html .= '<button onclick="$.uReqsCtrl.claimForRemove(' . $order->order_id . ',' . (($order->order_plus) ? MySoap::ITEM_PKO : MySoap::ITEM_RKO) . '); return false;"'
                                        . 'class="btn btn-default btn-sm">'
                                        . '<span class="glyphicon glyphicon-exclamation-sign"></span></button>';
                            } else {
                                if (!is_null($order->order_claimed_for_remove)) {
                                    $html.='<button disabled class="btn btn-danger btn-sm"  title="Было запрошено удаление"><span class="glyphicon glyphicon-exclamation-sign"></span></button>';
                                } else {
                                    $html .= '<button disabled class="btn btn-default btn-sm"><span class="glyphicon glyphicon-exclamation-sign"></span></button>';
                                }
                            }
                            if (Auth::user()->isAdmin()) {
                                $html .= '<button onclick="$.ordersCtrl.editOrder(' . $order->order_id . '); return false;" '
                                        . 'class="btn btn-default btn-sm edit-order-btn"><span class="glyphicon glyphicon-pencil"></span></button>';
                                $html .= '<a href="' . url('orders/remove/' . $order->order_id) . '" '
                                        . 'class="btn btn-default btn-sm">'
                                        . '<span class="glyphicon glyphicon-remove"></span></a>';
                            }
                            $html .= '</div>';
                            return $html;
                        })
                        ->removeColumn('order_id')
                        ->removeColumn('passports.fio')
                        ->removeColumn('order_fio')
                        ->removeColumn('passports.series')
                        ->removeColumn('passports.number')
                        ->removeColumn('order_claimed_for_remove')
                        ->removeColumn('order_plus')
                        ->removeColumn('order_repayment_id')
                        ->make();
    }

    /**
     * Редактирование ордера
     * @param Request $req
     * @return \Illuminate\Support\Facades\View
     */
    public function updateOrder(Request $req) {
        $order = Order::findOrNew($req->get('id', NULL));
        if($req->has('type') && \App\IssueClaim::isOrderTypeForIssueClaim($req->get('type')) && is_null($order->id)){
            return $this->createIssueClaim($req);
        }
        $input = $req->all();
        $input["money"] = StrUtils::parseMoney($input["money"]);
        if (array_key_exists('created_at', $input) && $input["created_at"] == '0000-00-00') {
            unset($input['created_at']);
        }
        if (array_key_exists('created_at', $input) && $input['created_at'] != $order->created_at) {
            $input['created_at'] = with(new Carbon($input['created_at']))->format('Y-m-d H:i:s');
        }

        DB::beginTransaction();
        Spylog::logModelChange('orders', $order, $input);
        $order->fill($input);
        $user = User::find(Input::get('user_id', null));
        if (is_null($user)) {
            $user = Auth::user();
        }
        $subdivision = Subdivision::find(Input::get('subdivision_id', null));
        $subdivision_id = (is_null($subdivision)) ? Auth::user()->subdivision_id : $subdivision->id;
        $order->subdivision_id = (is_null($order->subdivision_id)) ? $subdivision_id : $order->subdivision_id;
        $order->user_id = (is_null($order->user_id)) ? $user->id : $order->user_id;
        if (!is_int(Input::get('passport_id'))) {
            if ($order->eqType([OrderType::VPKO, OrderType::RASHOD])) {
                //если тип ордера приход внутренние перемещения то в ответственном должна стоять только Стельман
                $passport = Passport::getResponsibleBuh();
                if (is_null($passport)) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка! Контрагент не найден.');
                } else {
                    $order->passport_id = $passport->id;
                }
            } else if (Input::has('passport_series') && Input::has('passport_number')) {
                $passport = Passport::where('series', $input["passport_series"])->where('number', $input["passport_number"])->first();
                if (!is_null($passport)) {
                    $order->passport_id = $passport->id;
                } else {
                    if (is_null($order->fio) || $order->fio == "") {
                        DB::rollback();
                        return redirect()->back()->with('msg_err', 'Ошибка! Контрагент не найден.');
                    }
                }
            } else {
                if (is_null($order->fio) || $order->fio == "") {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка! Контрагент не найден.');
                }
            }
        } else {
            $order->passport_id = Input::get('passport_id', null);
        }

        /**
         * Проверки на возможность создать ордер
         */
        if ($passport->isResponsibleBuh() && $order->eqType([
                    OrderType::PODOTCHET,
                    OrderType::SALARY,
                    OrderType::INCASS,
                    OrderType::COMIS,
                    OrderType::VOZVRAT,
                    OrderType::SBERINCASS,
                    OrderType::DEFICIT,
                    OrderType::OVERAGE,
                    OrderType::CANC
                ])) {
            return redirect()->back()->with('msg_err', 'Ошибка! Ордер не может быть создан на ' . Passport::getResponsibleBuhFIO());
        }
        if (!$passport->isResponsibleBuh() && $order->eqType([OrderType::VPKO, OrderType::RASHOD])) {
            return redirect()->back()->with('msg_err', 'Ошибка! Ордер может быть создан ТОЛЬКО на ' . Passport::getResponsibleBuhFIO());
        }

        if (is_null($order->loan_id) && is_null($order->repayment_id) && $order->orderType->text_id == OrderType::PKO) {
            if (!is_null($order->passport)) {
                $docs = \App\Synchronizer::updateLoanRepayments($order->passport->series, $order->passport->number);
                if (!is_null($docs)) {
                    if (array_key_exists('loan', $docs)) {
                        $order->loan_id = $docs['loan']->id;
                    }
                    if (array_key_exists('repayments', $docs) && count($docs['repayments']) > 0) {
                        $order->repayment_id = $docs['repayments'][count($docs['repayments']) - 1]->id;
                    }
                }
            } else {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR);
            }
        }
        /**
         * Если основание не заполнено
         */
        if (is_null($order->reason) || $order->reason == '') {
            if ($order->orderType->text_id == OrderType::PODOTCHET && !is_null($order->passport)) {
                $order->reason = 'Выдача подотчётному лицу ' . $order->passport->fio . ' из кассы подразделения ' . $order->subdivision->address;
            }
            if ($order->orderType->text_id == OrderType::RASHOD && !is_null($order->passport)) {
                $order->reason = 'Выдача подотчётному лицу ' . $order->passport->fio . ' из кассы подразделения ' . $order->subdivision->address;
            }
            if (in_array($order->orderType->text_id, [OrderType::OVERAGE, OrderType::DEFICIT])) {
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка! Поле основание не может быть пустым');
            }
            if ($order->orderType->text_id == OrderType::COMIS) {
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка! Поле основание не может быть пустым');
            }
        }
        if (!array_key_exists('created_at', $input) || is_null($input['created_at']) || $input['created_at'] == '') {
            $order->created_at = Carbon::now()->format('Y-m-d H:i:s');
        }
        if (!is_null($order->number) && $order->number != '') {
            if ($order->save()) {
                DB::commit();
                return redirect()->back()->with('msg', 'Ордер сохранён ТОЛЬКО В АРМ. Отредактируйте в 1с')->with('class', 'alert-success');
            } else {
                DB::rollback();
                return redirect()->back()->with('msg', 'Ошибка! Ордер не сохранён.')->with('class', 'alert-danger');
            }
        } else {
            if ($req->only_arm) {
                if ($order->save()) {
                    DB::commit();
                    return redirect()->back()->with('msg', 'Ордер сохранён ТОЛЬКО В АРМ. Отредактируйте в 1с')->with('class', 'alert-success');
                } else {
                    DB::rollback();
                    return redirect()->back()->with('msg', 'Ошибка! Ордер не сохранён.')->with('class', 'alert-danger');
                }
            }
            if ($req->has('loan_id_1c')) {
                $loan = Loan::where('id_1c', $req->loan_id_1c)->first();
                if (!is_null($loan)) {
                    $order->loan_id = $loan->id;
                }
            }
            if ($order->saveThrough1c()) {
                DB::commit();
                return redirect()->back()->with('msg', 'Ордер сохранён.')->with('class', 'alert-success');
            } else {
                DB::rollback();
                return redirect()->back()->with('msg', 'Ошибка! Ордер не сохранён.')->with('class', 'alert-danger');
            }
        }
    }

    /**
     * удаление ордера 
     * при удалении так же прибавляетк остаткам в договорах и платежам в мировом
     * @param type $id идентификатор ордера
     * @return type
     */
    public function removeOrder($id) {
        $order = Order::find($id);
        if (is_null($order)) {
            return redirect()->back()->with('msg_err', 'Ордер не найден');
        }
        if (with(new Carbon($order->created_at))->setTime(0, 0, 0)->ne(Carbon::now()->setTime(0, 0, 0))) {
            return redirect()->back()->with('msg_err', 'Можно удалить ордер только за сегодня.');
        }
        $loan = $order->loan;
        DB::beginTransaction();
        if ($order->purpose != Order::P_UKI_NDS) {
            if (!is_null($order->loan_id)) {
                //делаем кредитник незачисленным если ордер стоит в кредитнике как расходник
                if (!is_null($loan) && $loan->order_id == $order->id) {
                    $loan->enrolled = 0;
                    if (!$loan->save()) {
                        DB::rollback();
                        return redirect()->back()->with('msg_err', 'Не получилось удалить ордер. Ошибка на сохранении кредитника');
                    }
                }
            }
        }
        $deleteResult = (!is_null($order->number) && $order->number != '' && $order->sync == 1) ? $order->deleteThrough1c() : $order->delete();
        if ($deleteResult) {
            $remreq = RemoveRequest::where('doc_id', $order->id)->where('doc_type', $order->getMySoapItemID())->first();
            if (!is_null($remreq)) {
                $remreq->update(['status' => RemoveRequest::STATUS_DONE, 'user_id' => Auth::user()->id]);
            }
            Spylog::logModelAction(Spylog::ACTION_DELETE, 'orders', $order);
            DB::commit();
            return redirect()->back()->with('msg_suc', 'Ордер удалён');
        } else {
            DB::rollback();
            return redirect()->back()->with('msg_err', 'Не получилось удалить ордер.');
        }
    }

    /**
     * Генерирует и отображает пдф с ордером
     * @param type $id идентификатор ордера
     * @return string
     */
    public function createPDF($id) {
        $order = Order::find($id);
        if (is_null($order)) {
            return 'Ордер не найден';
        }
        $contract = ContractForm::where('text_id', ($order->orderType->plus) ? config('options.orderPKO') : config('options.orderRKO'))->first();
        if (is_null($contract)) {
            return 'Форма ордера не найдена';
        }
        $objects = [];
        $objects['orders'] = $order->toArray();
        $objects['orders']['nds'] = '(без налога) 0-00 руб.';
        $objects['order_types'] = $order->orderType->toArray();
        if (is_null($objects['order_types']['invoice'])) {
            $objects['order_types']['invoice'] = $order->getInvoice();
        }
        $passport = $order->passport;
        if (!is_null($passport)) {
            $objects['passports'] = $passport->toArray();
            if (is_null($objects['orders']['fio'])) {
                $objects['orders']['fio'] = $passport->fio;
            }
            $objects['orders']['passport_data'] = 'Паспорт гражданина Российской Федерации, серия: '
                    . $passport->series . ', № ' . $passport->number . ', выдан: '
                    . with(new Carbon($passport->issued_date))->format('d.m.Y') . ' года, ' . $passport->issued . ', № подр. '
                    . $passport->subdivision_code;
        }
        $subdivision = $order->subdivision;
        if (!is_null($subdivision)) {
            $objects['subdivisions'] = $subdivision->toArray();
        }
        $user = $order->user;
        if (!is_null($user)) {
            $objects['users'] = $user->toArray();
        }
        if ($order->type == OrderType::getRKOid()) {
            $loan = Loan::where('order_id', $id)->first();
            if (!is_null($loan)) {
                $objects['orders']['reason'] = 'На основании договора №' . $loan->id_1c . ' от ' . (with(new Carbon($loan->created_at))->format('d.m.Y')) . ' г.';
            }
        } else if ($order->type == OrderType::getPKOid()) {
//            $opts['orientation'] = 'landscape';
            if (!is_null($order->loan)) {
                $objects['orders']['reason'] = $order->getPurposeName() . ' по договору №' . $order->loan->id_1c . ' от ' . (with(new Carbon($order->loan->created_at))->format('d.m.Y')) . ' г.';
            }
            if ((is_null($order->repayment) && !is_null($order->loan))) {
//                $objects['orders']['reason'] = 'На основании договора №' . $order->loan->id_1c . ' от ' . (with(new Carbon($order->loan->created_at))->format('d.m.Y')) . ' г.';
            } else if (!is_null($order->repayment)) {
                if ($order->repayment->repaymentType->isClosing()) {
                    $reps = Repayment::where('loan_id', $order->loan_id)->orderBy('created_at', 'desc')->get();
                    if (count($reps) > 1) {
                        $objects['orders']['doc_reason'] = $reps[1]->repaymentType->name . ' №' . $reps[1]->id_1c . ' от ' . (with(new Carbon($reps[1]->created_at))->format('d.m.Y')) . ' г.';
                    } else {
                        if (is_null($order->loan_id)) {
                            $objects['orders']['doc_reason'] = $objects['orders']['reason'];
                        } else {
                            $objects['orders']['doc_reason'] = $order->repayment->repaymentType->name . ' №' . $order->repayment->id_1c . ' от ' . (with(new Carbon($order->repayment->created_at))->format('d.m.Y')) . ' г.';
                        }
                    }
                } else {
                    if (is_null($order->repayment_id)) {
                        $objects['orders']['doc_reason'] = $objects['orders']['reason'];
                    } else {
                        $objects['orders']['doc_reason'] = $order->repayment->repaymentType->name . ' №' . $order->repayment->id_1c . ' от ' . (with(new Carbon($order->repayment->created_at))->format('d.m.Y')) . ' г.';
                    }
                }
            }
            if ($order->purpose == Order::P_UKI) {
//                $objects['orders']['reason'] = 'Комиссия за услугу "Улучшение кредитной истории" по Соглашению об улучшении кредитной истории Справка №' . $order->loan->id_1c;
//                $objects['orders']['nds'] = '106-20 руб.';
            }
            if ($order->purpose == Order::P_UKI_NDS) {
//                $objects['orders']['reason'] = 'Комиссия за услугу "Улучшение кредитной истории" по Соглашению об улучшении кредитной истории Справка №' . $order->loan->id_1c;
                $objects['orders']['nds'] = '90 руб.';
            }
        }
        if (!array_key_exists('doc_reason', $objects['orders']) || is_null($objects['orders']['doc_reason'])) {
            $objects['orders']['doc_reason'] = '';
        }
        if ($order->type == OrderType::getRKOid()) {
            $loan = Loan::where('order_id', $id)->first();
            if (!is_null($loan)) {
                $objects['orders']['reason'] = 'На основании договора №' . $loan->id_1c . ' от ' . (with(new Carbon($loan->created_at))->format('d.m.Y')) . ' г.';
            }
        }
        
        if (!is_null($contract->tplFileName) && mb_strlen($contract->tplFileName) > 0) {
            if (file_exists(FileToPdfUtil::getPathToTpl() . $contract->tplFileName)) {
                $this->createPdfFromFile($contract, $objects);
            }
        }

        $html = $contract->template;
        $html = ContractEditorController::processParams($objects, $html);
        $html = ContractEditorController::replaceConfigVars($html);
        $opts = [];
        $opts['margin-top'] = "0.25cm";
        $opts['margin-right'] = "0.2cm";
        $opts['margin-bottom'] = "0.25cm";
        $opts['margin-left'] = "0.1cm";
        return \App\Utils\PdfUtil::getPdf($html, $opts);
    }
    
    // формирование PDF из ODS-шаблона или ODT-шаблона
    public function createPdfFromFile($contract, $objects) {
        \App\Utils\FileToPdfUtil::replaceKeys($contract->tplFileName, $objects, 'order');
    }
    /**
     * Создает заявку на создание ордера на подотчет
     * @param Request $req
     * @return type
     */
    public function createIssueClaim(Request $req){
        $input = $req->input();
        \PC::debug($input);
        $issueClaim = new \App\IssueClaim();
        $issueClaim->fill($input);
        $issueClaim->money = StrUtils::rubToKop($req->get('money',0));
        if(!is_int($req->get('passport_id'))){
            if($req->has('passport_series') && $req->has('passport_number')){
                $passport = Passport::where('series',$req->get('passport_series'))->where('number',$req->get('passport_number'))->first();
                if(is_null($passport)){
                    return redirect()->back()->with('msg_suc', StrLib::ERR_NO_CUSTOMER);
                } else {
                    $issueClaim->passport_id = $passport->id;
                }                
            } else {
                return redirect()->back()->with('msg_suc', StrLib::ERR_NO_CUSTOMER);
            }
        } else {
            $issueClaim->passport_id = $req->get('passport_id');
        }
        $issueClaim->order_type_id = $req->get('type');
        $user = User::find($req->get('user_id', null));
        if (is_null($user)) {
            $user = Auth::user();
        }
        $subdivision = Subdivision::find($req->get('subdivision_id', null));
        if(is_null($subdivision)){
            $subdivision = Auth::user()->subdivision;
        }
        $issueClaim->subdivision_id = $subdivision->id;
        $issueClaim->user_id = $user->id;
        
        $issueClaim->data = $req->get('issue_claim_data');
        if(!empty($issueClaim->data)){
            $items = json_decode($issueClaim->data);
            $total = 0;
            foreach($items as $item){
                $total += floatval($item->ic_money);
            }
            $issueClaim->money = $total*100;
        }
        \PC::debug($issueClaim);
//        $issueClaim->azaza();
        $res = $issueClaim->saveThrough1c(true);
        if($res->result){
            return redirect()->back()->with('msg_suc',  StrLib::SUC_SAVED);
        } else {
            return redirect()->back()->with('msg_err',  $res->error);
        }
    }
}
