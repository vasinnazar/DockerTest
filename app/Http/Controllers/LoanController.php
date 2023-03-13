<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Illuminate\Support\Facades\Storage,
    Log,
    Auth,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Claim,
    App\Card,
    App\Cashbook,
    App\Order,
    App\OrderType,
    App\Repayment,
    App\RepaymentType,
    App\Photo,
    App\Passport,
    App\Customer,
    App\LoanType,
    App\Subdivision,
    App\User,
    App\PeacePay,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    Artisaninweb\SoapWrapper\Facades\SoapWrapper,
    App\MySoap,
    Yajra\Datatables\Facades\Datatables,
    Carbon\Carbon,
    App\StrUtils,
    App\about_client,
    App\AdSource,
    App\LiveCondition,
    App\EducationLevel,
    App\Synchronizer,
    App\Utils\StrLib,
    App\Promocode;

class LoanController extends Controller {

    public function __construct() {
        //$this->middleware('auth');
    }

    public function index() {
        
    }

    public function create() {
        if (!is_null(Loan::where('claim_id', Input::get('claim_id'))->first())) {
            return redirect('home')->with('msg_err', StrLib::ERR_CLAIM_HAS_LOAN);
        }
        $claim = Claim::find(Input::get('claim_id'));
        if (is_null($claim)) {
            return redirect('home')->with('msg_err', StrLib::ERR_NULL);
        }
        if ($claim->srok < Input::get('time')) {
            return redirect('home')->with('msg_err', StrLib::ERR_TIME_GT);
        }
        if ((is_null($claim->max_money) && $claim->summa < Input::get('money')) || (!is_null($claim->max_money) && $claim->max_money > 0 && $claim->max_money < Input::get('money'))) {
            return redirect('home')->with('msg_err', StrLib::ERR_MONEY_GT);
        }
        DB::beginTransaction();
        $loan = new Loan();
        $loan->fill(Input::all());
        if ($claim->subdivision->is_terminal) {
            $loan->subdivision_id = $claim->subdivision_id;
        } else {
            $loan->subdivision_id = Auth::user()->subdivision_id;
        }
        $loan->user_id = Auth::user()->id;
        //если в заявке есть процент для спец условий, то добавляем его в кредитник
        if (!is_null($claim->special_percent)) {
            $loan->special_percent = $claim->special_percent;
        }

        if (Input::get('in_cash') == 0) {
            if ($loan->money < 2000) {
                Log::warning('LoanController.create', ['error' => 'Нельзя создать договор на карту с суммой менее 2000 рублей.', 'loan' => $loan->toArray()]);
                return redirect('home')->with('msg_err', "Нельзя создать договор на карту с суммой менее 2000 рублей.");
            }
            $card = Card::firstOrNew(['card_number' => Input::get('card_number')]);
            \PC::debug($card->id);
            if (!is_null($card->id) && $card->card_number == Input::get('card_number') && $card->customer_id != Input::get('customer_id')) {
                return redirect('home')->with('msg_err', StrLib::ERR_CARD_EXISTS);
            }

            $card->fill(Input::all());
            if (!$card->save()) {
                DB::rollback();
                return redirect('home')->with('msg_err', StrLib::ERR_CARD);
            }
            /**
             * блокируем все предыдущие карты
             */
            try {
                Card::where('customer_id', $card->customer_id)->where('card_number', '<>', $card->card_number)->update(['status' => Card::STATUS_CLOSED]);
            } catch (\Exception $ex) {
                Log::error('LoanController.create', ['error' => 'Не смогли выставить статусы в старых картах', 'card' => $card, 'exception' => $ex]);
            }

            $loan->card_id = $card->id;
            $loan->in_cash = 0;
        } else {
            $loan->in_cash = 1;
            $loan->card_id = null;
        }

        $loan->uki = $claim->uki;


        //****************
        //отправляем данные по займу в 1с, получаем созданный в 1с идентификатор и записываем в базу
        $promocode = (Input::has('promocode') && Input::get('promocode') == 1) ? 1 : 0;
        //если клиент не постоянный, то все равно добавить промокод

        if (!$promocode &&
                !$claim->customer->isPostClient() &&
                $claim->subdivision->is_terminal == 0 &&
                (!is_null($loan->loantype) && in_array($loan->loantype->id_1c, ['ARM000004', 'ARM000021']))) {
            $promocode = 1;
        }
        if (config('admin.orders_without_1c') == 1) {
            $this->id_1c = Loan::getNextNumber();
            if (!$this->in_cash) {
                $this->tranche_number = $loan->getNextTrancheNumber();
            }
            if ($promocode) {
                $promo = new Promocode();
                $promo->number = Promocode::generateNumber();
                if ($promo->save()) {
                    $loan->promocode_id = $promo->id;
                }
            }
        } else {
            $res1c = $this->sendLoanTo1c($loan, ((isset($card)) ? $card : null), $promocode, false);
            if ($res1c['res'] == 0) {
                Log::error('Ошибка при запросе к 1с! Займ не был оформлен. ' . $res1c['msg_err'], $res1c);
                DB::rollback();
                return redirect('home')->with('msg_err', StrLib::ERR_1C . ' ' . $res1c['msg_err']);
            }
            if (!array_key_exists('loan_id_1c', $res1c) || $res1c['loan_id_1c'] == '') {
                DB::rollback();
                Spylog::logError(json_encode(['loan' => $loan->toArray(), 'res1c' => $res1c, 'error' => 'Нет номера кредитного договора!']));
                return redirect('home')->with('msg_err', StrLib::ERR_1C . ' Нет номера кредитного договора!');
            }
            if (array_key_exists('promocode_number', $res1c) && $res1c['promocode_number'] != "") {
                $promo = new Promocode();
                $promo->number = $res1c['promocode_number'];
                if ($promo->save()) {
                    $loan->promocode_id = $promo->id;
                }
            }
            if (!array_key_exists('loan_id_1c', $res1c)) {
                DB::rollback();
                return redirect('home')->with('msg_err', StrLib::ERR_DUPLICATE_1C);
            }
            $loan->id_1c = $res1c['loan_id_1c'];
            if (array_key_exists('tranche_number', $res1c)) {
                $loan->tranche_number = $res1c['tranche_number'];
                if ($loan->tranche_number == 1) {
                    $loan->first_loan_id_1c = $loan->id_1c;
                    $loan->first_loan_date = Carbon::now()->format('Y-m-d H:i:s');
                } else {
                    $loan->first_loan_id_1c = $res1c['first_loan_id_1c'];
                    $loan->first_loan_date = with(new Carbon($res1c['first_loan_date']))->format('Y-m-d H:i:s');
                }
            }
        }

        //************************

        if ($loan->save()) {
//            $claim->summa = $loan->money;
//            $claim->srok = $loan->time;
            $claim->save();
            DB::commit();
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'loans', $loan);
            $promo_msg = (isset($promo) && !is_null($promo)) ? 'С промокодом: ' . $promo->number : '';
            Synchronizer::updateLoanRepayments(null, null, $loan->id_1c);
            return redirect('home')->with('msg_suc', 'Займ оформлен! ' . $promo_msg);
        } else {
            DB::rollback();
            return redirect('home')->with('msg_err', StrLib::ERR);
        }
    }

    /**
     * Зачислить средства по займу с переданным идентификатором
     * @param type $loan_id
     */
    public function enroll($loan_id) {
        $loan = Loan::find($loan_id);
        if (is_null($loan)) {
            return redirect('home')->with('msg_err', 'Займ с номером ' . $loan_id . ' не найден!');
        }
        if ((bool) $loan->enrolled) {
            return redirect('home')->with('msg_err', 'Средства по займу уже зачислены.');
        }
        $user = \App\User::select('id_1c')->where('id', $loan->user_id)->first();
        $subdiv = \App\Subdivision::select('name_id')->where('id', $loan->subdivision_id)->first();
        if (is_null($user) || is_null($subdiv)) {
            return redirect('home')->with('msg_err', 'Не найден пользователь или подразделение.');
        }

        $sendTo1c = !($loan->in_cash && Auth::user()->subdivision_id == 106);

        DB::beginTransaction();
        if ($loan->subdivision->is_terminal) {
            $res1c = MySoap::enrollTerminal($loan->id_1c, Carbon::now()->format('YmdHis'), $loan->user->id_1c, $loan->subdivision->name_id);
        } else {
            if ($sendTo1c) {
                $res1c = MySoap::enrollLoan([
                            'loan_id_1c' => $loan->id_1c,
                            'created_at' => Carbon::now()->format('YmdHis'),
                            'user_id_1c' => $user->id_1c,
                            'subdivision_id_1c' => $subdiv->name_id,
                            'customer_id_1c' => $loan->claim->customer->id_1c
                                ], !$loan->in_cash);
            }
        }
        if ($sendTo1c) {
            if ($res1c['res'] == 0) {
                Log::error('enrollLoan.mysoap', ['res1c' => $res1c, 'loan_id' => $loan_id]);
                return redirect('home')->with('msg_err', 'Ошибка при запросе к 1с! ' . $res1c['msg_err']);
            }
        }

        $order = new Order();
        $order->type = ($loan->in_cash) ? OrderType::getRKOid() : OrderType::getCARDid();
        $order->number = ($sendTo1c) ? $res1c['order_id_1c'] : '';
        $order->user_id = $loan->user_id;
        $order->subdivision_id = $loan->subdivision_id;
        $order->passport_id = $loan->claim->passport_id;
        $order->money = $loan->money * 100;
        $order->loan_id = $loan->id;
        if (!$sendTo1c) {
            $order->sync = 0;
        }
        \PC::debug($order, 'order');
        if (!$order->save()) {
            DB::rollback();
            Log::error('enrollLoan.order.cash', ['res1c' => $res1c, 'loan_id' => $loan_id, 'order' => $order]);
            return redirect('home')->with('msg_err', 'Ошибка при оформлении ордера! Займ не был оформлен.');
        }
        $loan->order_id = $order->id;
        $loan->enrolled = 1;

        if (!is_null($loan->promocode_id) && !$loan->claim->customer->isPostClient()) {
            TerminalController::sendSMS($loan->claim->customer->telephone, '500 рублей за друга! Ваш промо код ' . $loan->promocode->number);
        }

        if ($loan->save()) {
            DB::commit();
            Spylog::log(Spylog::ACTION_ENROLLED, 'loans', $loan->id);
            return redirect('home')->with('msg_suc', (($loan->in_cash) ? 'РКО сформирован' : 'Средства по займу зачислены.'));
        } else {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка при оформлении ордера! Займ не был оформлен.');
        }
    }

    public function remove($loan_id) {
        $loan = Loan::find($loan_id);
        if (is_null($loan)) {
            Log::error('LoanController.remove 1', ['loan_id' => $loan_id]);
            return redirect()->back()->with('msg', 'Ошибка! Займ не найден.')->with('class', 'alert-danger');
        }
        if ($loan->enrolled && !$loan->in_cash) {
            Log::error('LoanController.remove 2', ['loan_id' => $loan_id]);
            return redirect()->back()->with('msg', 'Средства по займу уже зачислены!')->with('class', 'alert-danger');
        }
        if (count($loan->repayments) > 0) {
            return redirect()->back()->with('msg', 'Ошибка! На договоре уже есть дополнительные документы.')->with('class', 'alert-danger');
        }
        DB::beginTransaction();
        $rko = Order::find($loan->order_id);
        if (!is_null($rko) && !$rko->deleteThrough1c()) {
            Log::error('Ошибка! Не удалось удалить расходник.', $rko->id);
            DB::rollback();
            return redirect()->back()->with('msg', 'Ошибка! Не удалось удалить.')->with('class', 'alert-danger');
        }

        if (!is_null($loan) && $loan->deleteThrough1c()) {
            Spylog::log(Spylog::ACTION_DELETE, 'loans', $loan_id);
            \App\RemoveRequest::setDone($loan_id, MySoap::ITEM_LOAN);
            DB::commit();
            return redirect()->back()->with('msg', 'Займ удалён.')->with('class', 'alert-success');
        } else {
            Log::error('LoanController.remove 3', ['loan_id' => $loan_id]);
            DB::rollback();
            return redirect()->back()->with('msg', 'Ошибка! Не удалось удалить.')->with('class', 'alert-danger');
        }
    }

    /**
     * удаление только с сайта
     * @param type $loan_id
     * @return type
     */
    public function removeOnlyFromDB($loan_id) {
        $loan = Loan::find($loan_id);
        if (is_null($loan)) {
            Log::error('LoanController.remove 1', ['loan_id' => $loan_id]);
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        if (count($loan->repayments) > 0) {
            return redirect()->back()->with('msg_err', StrLib::ERR_LOAN_HAS_REPS);
        }
        DB::beginTransaction();
        $rko = Order::find($loan->order_id);
        if (!is_null($rko) && !$rko->delete()) {
            Log::error('Ошибка! Не удалось удалить расходник.', $rko->id);
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_CANT_DELETE . ' (расходник)');
        }
        if (!is_null($loan) && $loan->delete()) {
            Spylog::log(Spylog::ACTION_DELETE, 'loans', $loan_id);
            \App\RemoveRequest::setDone($loan_id, MySoap::ITEM_LOAN);
            DB::commit();
            return redirect()->back()->with('msg_suc', StrLib::SUC);
        } else {
            Log::error('LoanController.remove 3', ['loan_id' => $loan_id]);
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_CANT_DELETE . '(договор)');
        }
    }

    /**
     * Открытие займа на редактирование
     * @param int $loan_id id займа
     * @return type
     */
    public function edit($loan_id) {
        $loan = Loan::find($loan_id);
        if (is_null($loan)) {
            return redirect('home')->with('msg', 'Займ не найден')->with('class', 'alert-danger');
        } else if ($loan->enrolled && !$loan->in_cash) {
            return redirect('home')->with('msg', 'Средства по займу уже зачислены!')->with('class', 'alert-danger');
        }
        Spylog::log(Spylog::ACTION_OPEN, 'loans', $loan_id);
        return view('loans.edit')
                        ->with('lastCard', Card::where('customer_id', $loan->claim->customer_id)->orderBy('created_at', 'desc')->first())
                        ->with('loan', $loan)
                        ->with('loantypes', \App\LoanType::pluck('name', 'id'));
    }

    public function update(Request $request) {
        $loan = Loan::find(Input::get('id'));
        if (is_null($loan)) {
            return redirect()->back()->with('msg', 'Ошибка! Займ не найден.')->with('class', 'alert-danger');
        }
//        else if ($loan->enrolled) {
//            return redirect()->back()->with('msg', 'Средства по займу уже зачислены!')->with('class', 'alert-danger');
//        } 
        else {
            $oldloan = $loan->toArray();
            $loan->fill(Input::all());
            DB::beginTransaction();
            if (is_null(Input::get('in_cash')) && !is_null(Input::get('card_number')) && Input::get('card_number') != '') {
                $card = Card::firstOrNew(['card_number' => Input::get('card_number')]);
                if (!is_null($card->id) && $card->card_number == Input::get('card_number') && $card->customer_id != $loan->claim->customer_id) {
                    return redirect()->back()->with('msg', 'Ошибка! Карта уже активирована на другого клиента!')->with('class', 'alert-danger');
                }
                $card->fill(Input::all());
                $card->customer_id = $loan->claim->customer_id;
                if (!$card->save()) {
                    DB::rollback();
                    return redirect()->back()->with('msg', 'Ошибка при оформлении карты! Займ не был оформлен.')->with('class', 'alert-danger');
                }
                $loan->card_id = $card->id;
                $loan->in_cash = 0;
            } else {
                $loan->in_cash = 1;
                $loan->card_id = null;
            }
            $promocode = ($request->has('promocode') && $request->get('promocode') == 1) ? 1 : 0;
            $loan->uki = (Input::has('uki')) ? 1 : 0;
            $res1c = $this->sendLoanTo1c($loan, ((isset($card)) ? $card : null), $promocode, true);
            if ($res1c['res'] == 0) {
                Log::error('Ошибка при редактировании займа! ' . $res1c['msg_err'], $request->all());
                return redirect()->back()->with('msg', 'Ошибка при редактировании займа! ' . $res1c['msg_err'])->with('class', 'alert-danger');
            }
            if (array_key_exists('promocode_number', $res1c) && $res1c['promocode_number'] != "") {
                $promo = new Promocode();
                $promo->number = $res1c['promocode_number'];
                if ($promo->save()) {
                    $loan->promocode_id = $promo->id;
                }
            }
            if ($loan->save()) {
                Spylog::logModelChange('loans', $oldloan, Input::all());
                DB::commit();
                $promocode_number = (isset($promo) && !is_null($promo)) ? ' С промокодом: ' . $promo->number : '';
                return redirect()->back()->with('msg', 'Сохранено!' . $promocode_number)->with('class', 'alert-success');
            } else {
                DB::rollback();
                Log::error('Ошибка при редактировании займа!', $request->all());
                return redirect()->back()->with('msg', 'Ошибка при редактировании займа!')->with('class', 'alert-danger');
            }
        }
    }

    /**
     * Отправляет займ в 1С
     * @param \App\Loan $loan займ
     * @param \App\Card $card
     * @param boolean $promocode
     * @param boolean $update
     * @return type
     */
    public function sendLoanTo1c($loan, $card, $promocode, $update = false) {
        $data = [
            'money' => $loan->money,
            'time' => $loan->time,
            'claim_id_1c' => $loan->claim->id_1c,
            'loantype_id_1c' => $loan->loantype->id_1c,
            'card_number' => (isset($card) && !is_null($card) && !$loan->in_cash) ? $card->card_number : '',
            'subdivision_id_1c' => $loan->subdivision->name_id,
            'secret_word' => (isset($card) && !is_null($card) && !$loan->in_cash) ? $card->secret_word : '',
            'created_at' => ($update) ? with(new Carbon($loan->created_at))->format('YmdHis') : Carbon::now()->format('YmdHis'),
            'user_id_1c' => ($update) ? $loan->user->id_1c : Auth::user()->id_1c,
            'promocode_number' => (int) $promocode,
            'uki' => $loan->uki
        ];
        \PC::debug($data);
        return MySoap::updateLoan($data);
    }

    public function inCash($loan_id) {
        
    }

    public function onCard($loan_id) {
        
    }

    /**
     * Возвращает уникальный шестизначный номер промокода
     * @return type
     */
    public function genPromocode() {
        $tablename = with(new Promocode)->getTable();
        $res = DB::table($tablename)
                ->select(DB::raw('FLOOR(100000 + RAND() * 899999) as rand'))
                ->whereRaw('"rand" not in (select number from ' . $tablename . ')')
                ->first();
        return (!is_null($res) ? $res->rand : '');
    }

    /**
     * Создаёт уникальный по базе на сайте промокод, присваивает его переданному 
     * в пост запросе займу, и возвращает объект промокода массивом. 
     * Используется для аякс запроса.
     * @param Request $request
     * @return type
     */
    public function createPromocode(Request $request) {
        if ($request->has('loan_id')) {
            $loan = Loan::find($request->loan_id);
            $promo = new Promocode();
            $promo->number = $this->genPromocode();
            DB::beginTransaction();
            if (!$promo->save()) {
                DB::rollback();
                return null;
            }
            $loan->promocode_id = $promo->id;
            if (!$loan->save()) {
                DB::rollback();
                return null;
            }
            DB::commit();
            return $promo->toArray();
        }
    }

    public function getLoans() {
        return view('loans.loans');
    }

    /**
     * 
     * @param Request $request
     * @param boolean $getFrom1cOnFail если не найдено ничего и выставлено в TRUE, то делает запрос в 1с и сохраняет пришедшие оттуда документы, а потом повторно делает запрос списка кредитников
     * @return type
     */
    public function getLoansList(Request $request, $getFrom1cOnFail = true)
    {
        $loans = Loan::select(DB::raw('loans.id as loan_id, loans.created_at as loans_created_at, passports.fio as passports_fio, loans.money as loans_money, loans.time as loans_time, loans.closed as loans_status, loans.claim_id as claim_id'))
            ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
            ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
            ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
            ->limit(50);
        if (!$request->has('fio') && !$request->has('telephone') && !$request->has('series') && !$request->has('number')) {
            $loans->where('loans.subdivision_id', Auth::user()->subdivision_id);
        }
        if (!$request->has('fio') && !$request->has('telephone') && !$request->has('series') && !$request->has('number') && !$request->has('loan_id')) {
            if (!config('app.dev')) {
                $loans->whereBetween('loans.created_at', [
                    Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                    Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                ]);
            }
        }
        $collection = Datatables::of($loans)
            ->editColumn('passports_fio', function ($loan) {
                $html = '';
                $html .= $loan->passports_fio;
                return $html;
            })
            ->editColumn('loans_created_at', function ($loan) {
                return with(new Carbon($loan->loans_created_at))->format('d.m.Y H:i:s');
            })
            ->editColumn('loans_money', function ($loan) {
                return ($loan->loans_money) . ' руб.';
            })
            ->editColumn('loans_status', function ($loan) {
                return ($loan->loans_status) ? '<span class="label label-success">Закрыт</span>' : '<span class="label label-default">Открыт</span>';
            })
            ->addColumn('actions', function ($loan) {
                $html = '';
                $html .= '<a href="' . url('loans/summary/' . $loan->loan_id) . '" '
                    . 'class="btn btn-default btn-sm" onclick="$.app.blockScreen(true);">'
                    . '<span class="glyphicon glyphicon-eye-open"></span></a>';
                if (Auth::user()->isAdmin()) {
                    $html .= ' ID: ' . $loan->loan_id;
                }
                return $html;
            })
            ->removeColumn('loan_id')
            ->removeColumn('rep_created_at')
            ->removeColumn('claim_id')
            ->filter(function ($query) use ($request) {
                if ($request->has('fio')) {
                    $query->where('passports.fio', 'like', "%" . $request->get('fio') . "%");
                }
                if ($request->has('telephone')) {
                    $query->where('customers.telephone', 'like', "%" . $request->get('telephone') . "%");
                }
                if ($request->has('series')) {
                    $query->where('passports.series', '=', $request->get('series'));
                }
                if ($request->has('number')) {
                    $query->where('passports.number', '=', $request->get('number'));
                }
                if ($request->has('loan_id')) {
                    $query->where('loans.id', '=', $request->get('loan_id'));
                }
                if ($request->has('date_start')) {
                    $dateStart = with(new Carbon($request->get('date_start')));
                    $query->where('loans.created_at', '>', $dateStart->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
                    $query->where('loans.created_at', '<', $dateStart->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
                }
                if ($request->has('loan_id_1c')) {
                    \PC::debug($request->all(), 'loan_id_1c');
                    $query->where('loans.id_1c', 'LIKE', '%' . $request->get('loan_id_1c') . '%');
                }
            })
            ->setTotalRecords(1000)
            ->make();
        //если не найдено договоров, то добавить договора из 1С
        $colObj = $collection->getData();
        if ($getFrom1cOnFail && (!$request->has('without1c') || !$request->without1c)) {
            if ($request->has('series') && $request->has('number') && $request->series != '' && $request->number != '') {
                if (!is_null($this->updateLoanRepayments($request->get('series'), $request->get('number')))) {
                    return $this->getLoansList($request, false);
                }
            } else {
                if ($request->has('fio') && $request->fio != '') {
                    $res1c = MySoap::getPassportsByFio($request->fio);
                    Log::info('GET PASSPORTS BY FIO', ['res1c' => $res1c]);
                    if (array_key_exists('fio', $res1c)) {
                        foreach ($res1c['fio'] as $item) {
                            if (is_array($item) && array_key_exists('passport_series',
                                    $item) && array_key_exists('passport_number', $item)) {
                                $this->updateLoanRepayments(StrUtils::removeNonDigits($item['passport_series']),
                                    StrUtils::removeNonDigits($item['passport_number']));
                            }
                        }
                        return $this->getLoansList($request, false);
                    }
                }
            }
        }
        return $collection;
    }

    public function getLoanSummary($loan_id) {
        $loan = Loan::find($loan_id);

        if (is_null($loan) || is_null($loan->claim) || is_null($loan->claim->passport)) {
//            Log::error('LoanController.getLoanSummary', ['loan' => $loan, 'claim' => (!is_null($loan)) ? $loan->claim : '', 'passport' => (!is_null($loan->claim)) ? $loan->claim->passport : '']);
            return redirect('loans')->with('msg_err', StrLib::ERR_NULL);
        }
        if (!is_null($loan->id_1c)) {
            $repsUpdateData = $this->updateLoanRepayments(null, null, $loan->id_1c);
            if (is_null($repsUpdateData)) {
                return redirect('loans')->with('msg_err', StrLib::ERR_1C);
            }
        } else {
            $repsUpdateData = $this->updateLoanRepayments($loan->claim->passport->series, $loan->claim->passport->number);
            if (is_null($repsUpdateData)) {
                return redirect('loans')->with('msg_err', StrLib::ERR_1C);
            }
        }
        $loans = Loan::where('claims.customer_id', $loan->claim->customer_id)->where('loans.id', '<>', $loan_id)->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->get();
        $claims = Claim::where('customer_id', $loan->claim->customer_id)->get();
        $photos = Photo::where('claim_id', $loan->claim->id)->get();
        $photo_res = [];
        foreach ($photos as $p) {
            if (Storage::exists($p->path)) {
                //после переезда убралось images из пути, но при показе оно должно быть чтобы нормально отработала функция в путях. 
                $p->src = url(((strpos($p->path, 'images') === FALSE) ? 'images/' : '') . $p->path);
                $photo_res[] = $p;
            }
        }
        /* ==Акции по сузу================================================================ */
        $importantMoneyData = null;
        $akcsuzst46 = false;
        $stockName = '';
        $stockType = '';
        foreach (['akcsuzst46' => 'СузСт46', 'ngbezdolgov' => 'НовыйГодБезДолгов'] as $snk => $snv) {
            if (array_key_exists($snk, $repsUpdateData)) {
                $stockName = $snk;
                $stockType = $snv;
            }
        }
        if (
                $stockName != '' &&
                is_array($repsUpdateData['contracts1c']) &&
                array_key_exists('repayments', $repsUpdateData['contracts1c']) &&
                array_key_exists('suz', $repsUpdateData['contracts1c']['repayments']) &&
                array_key_exists('stock_type', $repsUpdateData['contracts1c']['repayments']['suz']) &&
                $repsUpdateData['contracts1c']['repayments']['suz']['stock_type'] == '' &&
                array_key_exists($stockName, $repsUpdateData)
        ) {
            $importantMoneyData = $repsUpdateData[$stockName];
            if ($stockName == 'akcsuzst46') {
                $akcsuzst46 = true;
            }
        }
        /* =================================================================== */
        $loanData = (is_null($loan->data)) ? null : json_decode($loan->data);
        $reqMoneyDet = $loan->getRequiredMoneyDetails();
        if (!is_null($loanData) && isset($loanData->spisan->total)) {
            $reqMoneyDet->money = StrUtils::removeNonDigits($loanData->spisan->total);
        }
        \PC::debug($reqMoneyDet);
        $repayments = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'asc')->get();
        $repsNum = count($repayments);
        $debtorData = null;
        /**
         * если мировое или суз или срок просрочки меньше 21 дня или больше 81 дня, то выводить ответственных из 1с, иначе выводить телефон дежурного
         */
        if (isset($repsUpdateData) && array_key_exists('debtor', $repsUpdateData)) {
            if (array_key_exists('str_podr', $repsUpdateData['debtor'])) {
                if ($repsUpdateData['debtor']['str_podr'] != 'Отдел удаленного взыскания') {
                    $debtorData = $repsUpdateData['debtor'];
                } else {
                    $debtorData = ['debtor_fio_head' => 'Дежурный по отделу удаленного взыскания', 'debtor_tel_head' => '8-905-906-07-89'];
                }
            } else {
                $debtorData = $repsUpdateData['debtor'];
            }
        }
        \PC::debug($importantMoneyData, 'importantmoneydata');

        if ($loan->hasOverdue()) {
            $lastRep = $loan->getLastRepayment();
            if (is_null($lastRep) || $lastRep->repaymentType->isDopnik()) {
                $exDopnikData = MySoap::sendExchangeArm(MySoap::createXML(['type' => 'GetExDopData', 'customer_id_1c' => $loan->claim->customer->id_1c, 'date' => Carbon::now()->format('YmdHis')]));
                $exDopnikDataArray = (!is_null($exDopnikData) && (int) $exDopnikData->result == 1) ? json_decode(json_encode($exDopnikData), true) : null;
                if (is_array($exDopnikDataArray)) {
                    $exDopnikDataArray['dopnik_create_date'] = ($repsNum > 0) ? $repayments[$repsNum - 1]->getEndDate()->format('Y-m-d') : $loan->getEndDate()->format('Y-m-d');
                    \PC::debug($exDopnikDataArray, 'exdopnikdata');
                }
            }
        }
        $viewData = [
            'loan' => $loan, 'claims' => $claims, 'loans' => $loans,
            'photos' => $photo_res, 'percents' => $loan->getPercents(),
            'rtypes' => $this->getPossibleRepaymentTypes($loan_id, $loan, $repayments, $reqMoneyDet, ((isset($exDopnikData)) ? $exDopnikData : null)),
            'repayments' => $repayments,
            'reqMoneyDet' => $reqMoneyDet,
            'debtor' => $debtorData,
            'ngbezdolgov' => $importantMoneyData,
            'akcsuzst46' => $akcsuzst46,
            'exDopnikData' => (isset($exDopnikDataArray)) ? $exDopnikDataArray : null,
        ];
        if (isset($loanData) && !is_null($loanData)) {
            $viewData['curPayMoney'] = $loanData->spisan->total;
            $viewData['spisan'] = $loanData->spisan->total;
        }
        return view('loans.summary', $viewData);
    }

    /**
     * Возвращает массив возможных для создания документов 
     * @param int $loan_id 
     * @param \App\Loan $loan кредитник, чтобы 2 раза не искать
     * @param type $repayments
     * @return type
     */
    public function getPossibleRepaymentTypes($loan_id, $loan = null, $repayments = null, $mDet = null, $exDopnikData = null) {
        if (is_null($loan)) {
            $loan = Loan::find($loan_id);
        }
        if (is_null($repayments)) {
            $repayments = Repayment::where('loan_id', $loan_id)->orderBy('created_at', 'asc');
        }
        $rpLen = count($repayments);
        $rtypes = RepaymentType::all();
        $res = [];
        $lastrep = ($rpLen > 0) ? $repayments[$rpLen - 1] : $loan;
        $lastrep_text_id = ($rpLen > 0) ? $lastrep->repaymentType->text_id : 'loan';

        $is_2903_loan = $loan->created_at->gte(new Carbon(config('options.new_rules_day')));

        $is_loan_120_days = Carbon::now()->subDays(120)->gte($loan->created_at);
        $is_loan_105_days = Carbon::now()->subDays(105)->gt($loan->created_at);
        $is_loan_after_010117 = $loan->created_at->gte(new Carbon(config('options.new_rules_day_010117')));

        $is_loan_181_days = Carbon::now()->subDays(181)->gte($loan->created_at);

        $loanData = (!is_null($loan->data)) ? json_decode($loan->data) : null;
        $spisan = (!is_null($loanData) && isset($loanData->spisan->total)) ? true : false;
        foreach ($rtypes as $rt) {
            if ($spisan) {
                continue;
            }
            if ($rt->isDopnik()) {
                if ((($rpLen > 0 && ($lastrep->repaymentType->isDopnik() || $lastrep->repaymentType->isDopCommission())) || $lastrep_text_id == 'loan')) {
                    if ($is_2903_loan && $is_loan_120_days) {
                        continue;
                    }
                    if ($is_2903_loan && $is_loan_105_days) {
                        continue;
                    }
                    if ($rt->isExDopnik()) {
                        if (!is_null($exDopnikData) && (intval($exDopnikData->result) == 1)) {
                            if ($lastrep->getOverdueDays() > 0) {
                                $res[] = $rt;
                            }
                        }
                    }
                    if ($lastrep->getOverdueDays() < 21) {
                        if ($loan->created_at->gte(new Carbon(config('options.new_rules_day'))) && $loan->hasOverduedDopnik()) {
                            if ($is_loan_after_010117 && $rt->text_id == config('options.rtype_dopnik7')) {
                                $res[] = $rt;
                            } else if ($rt->text_id == config('options.rtype_dopnik5')) {
                                $res[] = $rt;
                            }
                        } else if (Carbon::now()->gte(new Carbon(config('options.card_nal_dop_day')))) {
                            if ($rt->text_id == config('options.rtype_dopnik6')) {
                                $res[] = $rt;
                            }
                        } else if (Carbon::now()->gte(new Carbon(config('options.perm_new_rules_day')))) {
                            if ($rt->text_id == config('options.rtype_dopnik4')) {
                                $res[] = $rt;
                            }
                        } else if ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
                            if ($rt->text_id == config('options.rtype_dopnik2')) {
                                $res[] = $rt;
                            }
                        } else {
                            if ($rt->text_id == config('options.rtype_dopnik3')) {
                                $res[] = $rt;
                            }
                        }
                    }
//                    if($rt->text_id == config('options.rtype_dopnik7')){
//                        $res[] = $rt;
//                    }
                }
            } else if ($rt->isClaim()) {
                //заявление
                //проверить есть ли на договоре заявления
                $hasClaim = false;
                foreach ($repayments as $rep) {
                    if ($rep->repaymentType->isClaim()) {
                        $hasClaim = true;
                        break;
                    }
                }
                //никаких заявлений после суза или мирового
                if (!is_null($lastrep->repaymentType) && ($lastrep->repaymentType->isPeace() || $lastrep->repaymentType->isSUZ())) {
                    continue;
                }
                //заявление о приостановке процентов и пени
                if (!$hasClaim && $rt->text_id == config('options.rtype_claim')) {
                    if (!$is_2903_loan || ($is_2903_loan && !$is_loan_120_days) || ($is_2903_loan && !$is_loan_181_days) || Auth::user()->isAdmin()) {
                        $res[] = $rt;
                    }
                }
                if ($is_loan_120_days && Auth::user()->isAdmin() && $rt->text_id == config('options.rtype_claim') && !in_array($rt, $res)) {
                    $res[] = $rt;
                }
                if (!$hasClaim && $rt->text_id == config('options.rtype_claim2') && $is_2903_loan && $lastrep->getOverdueDays() >= 21) {
                    $res[] = $rt;
                }
                //соглашение с комиссией нельзя делать после 21 дня просрочки
//                if($rt->isDopCommission() && $is_loan_120_days && $is_2903_loan && $lastrep->getOverdueDays() < 21){
                if ($rt->isDopCommission() && $is_loan_105_days && $is_2903_loan && $lastrep->getOverdueDays() < 21) {
                    if ($rpLen == 0 || ((!$lastrep->repaymentType->isClaim() && !$lastrep->repaymentType->isPeace() && !$lastrep->repaymentType->isSUZ()) || $lastrep->repaymentType->isDopCommission())) {
                        //мужик сделал соглашение, хотя уже не должен был, запрет на соглашение еще раз (привет костыль)
                        if ($loan->id_1c != '00000535533') {
                            $res[] = $rt;
                        }
                    }
                }
            } else if ($rt->isClosing()) {
                //закрытие
                if (!$loan->closed) {
                    if (is_null($lastrep->repaymentType)) {
                        $res[] = $rt;
                    } else if ($lastrep->repaymentType->isPeace()) {
                        if (!is_null($mDet) && $mDet->money == 0) {
                            $res[] = $rt;
                        }
                    } else if (!$lastrep->repaymentType->isSUZ()) {
                        $res[] = $rt;
                    }
                }
            } else if ($rt->isPeace()) {
                //мировое
                /**
                 * если есть суз, то уже никаких мировых
                 * если договор после 29.03, то можно создавать только мировое с типом 3
                 * иначе мировое с типом 2, мировое с типом 1 уже не создаются (13.10.16)
                 */
                if (in_array($lastrep_text_id, [config('options.rtype_suz1'), config('options.rtype_suz2')])) {
                    continue;
                }
                if ($loan->created_at->gt(new Carbon(config('options.new_rules_day')))) {
                    if ($rt->text_id == config('options.rtype_peace3')) {
                        $res[] = $rt;
                    }
                } else {
                    if ($rt->text_id == config('options.rtype_peace2')) {
                        $res[] = $rt;
                    }
                }
            } else if (!$rt->isSUZ() && !$rt->isSuzStock()) {
                //все остальное кроме суза
                $res[] = $rt;
            }
        }
        return $res;
    }

    public function getDebt(Request $req) {
//        if(config('app.version_type')=='debtors'){
//            $debtData = \App\Utils\HelperUtil::SendPostByCurl('192.168.1.115/debtors/debt', [
//                'data'=>json_encode([
//                    'loan_id_1c'=>$req->get('loan_id_1c'),
//                    'customer_id_1c'=>$req->get('customer_id_1c'),
//                    'date'=>$req->get('date')
//                ])
//            ]);
//            \PC::debug($debtData,'getDebt');
//            return $debtData;
//        }
        if ($req->has('loan_id_1c') && $req->has('customer_id_1c')) {
            $loan = Loan::getById1cAndCustomerId1c($req->loan_id_1c, $req->customer_id_1c);
        } else {
            $loan = Loan::find($req->id);
        }
        if (!isset($loan) || is_null($loan)) {
            return json_encode([]);
        } else {
            if (config('app.version_type') == 'debtors') {
                return json_encode($loan->getDebtFrom1cWithoutRepayment($req->date));
            } else {
                return json_encode($loan->getDebtFrom1c($loan, $req->date));
            }
        }
    }

    public function close($loan_id) {
        $loan = Loan::find($loan_id);
        if (is_null($loan)) {
            return redirect()->back()->with('msg', 'Займ не найден.')->with('class', 'alert-danger');
        }
        $user = Auth::user();
        $mDet = $loan->getRequiredMoneyDetails();
        $payOrder = RepaymentType::DEF_PAY_ORDER;
        DB::beginTransaction();
        foreach ($payOrder as $item) {
            $orderM = $mDet->{$item};
            if ($orderM > 0) {
                $order = new Order();
                $order->fill([
                    'loan_id' => $loan_id,
                    'money' => $orderM, 'type' => OrderType::getPKOid(),
                    'passport_id' => $loan->claim->passport_id,
                    'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                    'purpose' => with(Order::getPurposeTypes())[$item]
                ]);
                if (!$order->save()) {
                    DB::rollback();
                    return redirect()->back()->with('msg', 'Займ не закрыт. Ошибка при сохранении приходника.')->with('class', 'alert-danger');
                }
            }
        }
        if (!$loan->update(['closed' => 1])) {
            DB::rollback();
            return redirect()->back()->with('msg', 'Займ не закрыт.')->with('class', 'alert-danger');
        }
        DB::commit();
        return redirect()->back()->with('msg', 'Займ закрыт.')->with('class', 'alert-success');
    }

    public function getLoan($loan_id) {
        return Loan::find($loan_id);
    }

    public function updateContracts() {
        
    }

    public function updateLoanRepayments($series = null, $number = null, $loan_id_1c = null) {
        return Synchronizer::updateLoanRepayments($series, $number, $loan_id_1c);
    }

    public function createAndEnroll($claim_id) {
        if (!is_null(Loan::where('claim_id', $claim_id)->first())) {
            return ['res' => 0, 'msg_err' => 'Займ уже оформлен'];
        }
        $claim = Claim::find($claim_id);
        if (is_null($claim) || $claim->srok < Input::get('time') || $claim->summa < Input::get('money')) {
            return ['res' => 0, 'msg_err' => 'Заявка не найдена или переданы неверные сумма или срок!'];
        }
        Log::info('Заявка с терминала!', ['claim_id' => $claim_id]);
//        if(!$claim->subdivision->is_terminal){
//            return ['res' => 0, 'msg_err' => 'Заявка не из терминала!'];
//        }
        DB::beginTransaction();
        $loan = new Loan();
        $loan->loantype_id = $claim->terminal_loantype_id;
        $loan->subdivision_id = Auth::user()->subdivision_id;
        $loan->user_id = Auth::user()->id;
        $loan->in_cash = 1;
        $loan->card_id = null;
        $loan->claim_id = $claim_id;
        $loan->money = $claim->summa;
        $loan->time = $claim->srok;

        //****************
        //отправляем данные по займу в 1с, получаем созданный в 1с идентификатор и записываем в базу
        $res1c = $this->sendLoanTo1c($loan, null, 0, false);
        if ($res1c['res'] == 0) {
            Log::error('Ошибка при запросе к 1с! Займ не был оформлен. ' . $res1c['msg_err'], $res1c);
            DB::rollback();
            return redirect('home')->with('msg', 'Ошибка при запросе к 1с! Займ не был оформлен. ' . $res1c['msg_err'])->with('class', 'alert-danger');
        }
        if (!array_key_exists('loan_id_1c', $res1c)) {
            DB::rollback();
            return redirect('home')->with('msg', 'Ошибка при запросе к 1с! Займ уже есть в 1с.')->with('class', 'alert-danger');
        }
        $loan->id_1c = $res1c['loan_id_1c'];
        //************************

        if (!$loan->save()) {
            DB::rollback();
            return redirect('home')->with('msg', 'Ошибка при сохранении займа! Займ не был оформлен.')->with('class', 'alert-danger');
        }
        /**
         * ЗАЧИСЛЕНИЕ
         */
        $user = \App\User::select('id_1c')->where('id', $loan->user_id)->first();
        $subdiv = \App\Subdivision::select('name_id')->where('id', $loan->subdivision_id)->first();
        if (is_null($user) || is_null($subdiv)) {
            DB::rollback();
            return redirect('home')->with('msg', 'Не найден пользователь или подразделение.')->with('class', 'alert-danger');
        }
        DB::beginTransaction();
        $resEnroll1c = MySoap::enrollLoan([
                    'loan_id_1c' => $loan->id_1c,
                    'created_at' => Carbon::now()->format('YmdHis'),
                    'user_id_1c' => $user->id_1c,
                    'subdivision_id_1c' => $subdiv->name_id
                        ], !$loan->in_cash);
        if ($resEnroll1c['res'] == 0) {
            DB::rollback();
            return redirect('home')->with('msg', 'Ошибка при запросе к 1с! ' . $resEnroll1c['msg_err'])->with('class', 'alert-danger');
        }
        //если наличкой, то создать расходник с номером пришедшим из 1с
        $order = Order::create([
                    'type' => OrderType::getRKOid(), 'number' => $resEnroll1c['order_id_1c'],
                    'user_id' => $loan->user_id, 'subdivision_id' => $loan->subdivision_id,
                    'passport_id' => $loan->claim->passport_id, 'money' => $loan->money * 100,
                    'loan_id' => $loan->id
        ]);
        if (is_null($order)) {
            DB::rollback();
            return redirect('home')->with('msg', 'Ошибка при оформлении ордера! Займ не был оформлен.')->with('class', 'alert-danger');
        }
        $loan->order_id = $order->id;
        $loan->enrolled = 1;
        if ($loan->save()) {
            \PC::debug($loan, 'loan');
            \PC::debug($order, 'order');
            DB::commit();
            Spylog::log(Spylog::ACTION_ENROLLED, 'loans', $loan->id);
            return redirect('home')->with('msg_suc', (($loan->in_cash) ? 'РКО сформирован' : 'Средства по займу зачислены.'));
        } else {
            DB::rollback();
            return redirect('home')->with('msg_err', 'Ошибка при оформлении ордера! Займ не был оформлен.');
        }
//
//        Spylog::logModelAction(Spylog::ACTION_CREATE, 'loans', $loan);
//        DB::commit();
//        return redirect('home')->with('msg', 'Займ оформлен!')->with('class', 'alert-success');
    }

    public function sendMoneyToBalance($loan_id) {
        $loan = Loan::find($loan_id);
        if (is_null($loan)) {
            return redirect()->back()->with('msg', 'Кредитный договор не найден')->with('class', 'alert-danger');
        }
        if ($loan->on_balance) {
            return redirect()->back()->with('msg', 'Средства по этому договору уже были переведены на баланс контрагента')->with('class', 'alert-danger');
        }
        DB::beginTransaction();
        $customer = $loan->claim->customer;
        $customer->balance += $loan->money * 100;
        $loan->on_balance = 1;
        if ($customer->save() && $loan->save()) {
            DB::commit();
            TerminalController::sendSMS($customer->telephone, 'Ваш баланс пополнен на ' . $loan->money . '. Дата возврата: '
                    . (with(new Carbon($loan->created_at))->addDays($loan->time)->format('d.m.Y'))
                    . 'г. Для получения денежных средств обратитесь к ближайшему терминалу.');
            return redirect()->back()->with('msg', 'Средства перечислены на баланс контрагента')->with('class', 'alert-success');
        } else {
            DB::rollback();
            return redirect()->back()->with('msg', 'Кредитный договор не найден')->with('class', 'alert-danger');
        }
    }

    public function clearEnroll($id) {
        $loan = Loan::find($id);
        if (is_null($loan)) {
            return redirect()->back()->with('msg_err', StrLib::$ERR_NULL);
        }
        if (!is_null($loan->order_id)) {
            return redirect()->back()->with('msg_err', StrLib::$ERR . ' На договоре уже есть расходник.');
        }
        $loan->enrolled = 0;
        if (!$loan->save()) {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        } else {
            Log::info('LoanController.clearEnroll: ' . $id);
            return redirect()->back()->with('msg_suc', StrLib::$SUC_SAVED);
        }
    }

    public function getLoanByPassport($passport_series, $passport_number) {
        $contracts = Synchronizer::updateLoanRepayments($passport_series, $passport_number);
        if (is_array($contracts) && array_key_exists('loan', $contracts)) {
            return redirect('loans/summary/' . $contracts['loan']->id);
        }
        return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
    }

}
