<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Auth,
    Log,
    Input,
    Carbon\Carbon,
    App\Order,
    App\OrderType,
    App\Loan,
    App\StrUtils,
    App\Repayment,
    App\Claim,
    App\Cashbook,
    App\PeacePay,
    Illuminate\Support\Facades\DB,
    App\MySoap,
    App\RepaymentType,
    App\RemoveRequest,
    App\Utils\StrLib,
    App\PaySaving,
    App\Synchronizer,
    App\Utils\HelperUtil,
    App\Spylog\Spylog;

class RepaymentController extends Controller {

    public function __construct() {
        
    }

    /**
     * Создание договора (допник\мировое\заявление)
     * @param Request $req
     * @return type
     */
    public function create(Request $req) {
        Log::info('RepaymentController.create', $req->all());
        $loan = Loan::find($req->get('loan_id'));
        $loanOverdue = with(new Carbon($loan->created_at))->setTime(0, 0, 0)->diffInDays(Carbon::now()->setTime(0, 0, 0)) - ($loan->time + 1);
        if (is_null($loan)) {
            return redirect()->back()->with('msg_err', 'Ошибка! Договор не был сохранён.');
        }
        if (is_null($loan->claim)) {
            return redirect()->back()->with('msg_err', 'Ошибка! Заявка не найдена!');
        }
        if (is_null($loan->claim->passport)) {
            return redirect()->back()->with('msg_err', 'Ошибка! Паспорт не найден!');
        }
        $prevRepayment = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'desc')->first();
        if (!is_null($prevRepayment) && $prevRepayment->created_at->diffInMinutes(Carbon::now()) < 1) {
            return redirect()->back()->with('msg_err', 'Создание доп. документов для данного договора приостановлено на 1 минуту.');
        }
        $repayment = new Repayment();
        $repayment->fill($req->all());

        //если введена дата и специалист и подразделение
        if ($req->has('create_date') && !empty($req->create_date) && $repayment->repaymentType->isExDopnik()) {
            \PC::debug("creating on date");
            return $this->createOnDate($req);
        }

        $repayment->tax = 0;
        $repayment->paid_money = StrUtils::parseMoney($req->get('paid_money'));

        /**
         * АКЦИЯ НГ БЕЗ ДОЛГОВ исправляем суммы если закрытие и пришли другие суммы
         */
        $importantMoneyData = null;
        $stockType = '';
        $stockName = '';
        if ($repayment->repaymentType->isClosing()) {
            $contracts1c = Synchronizer::getContractsFrom1c(null, null, $loan->id_1c);
            if (!is_null($contracts1c)) {
                if (array_key_exists('stockData', $contracts1c)) {
                    $importantMoneyData = $contracts1c['stockData']['importantMoneyData'];
                    $stockType = $contracts1c['stockData']['stockType'];
                    $stockName = $contracts1c['stockData']['stockName'];
                }
            }
        }
        /**
         * =======================================================
         */
        $mDet = $loan->getRequiredMoneyDetails($importantMoneyData);

        if ($mDet->pc == 0) {
            $repayment->discount = 0;
        }
        $repayment->req_money = $mDet->money;
        $repayment->fine = (is_null($prevRepayment)) ? $loan->fine : $prevRepayment->fine;
        $orders = [];
        $peacePays = [];

        if ($repayment->repaymentType->text_id == config('options.rtype_claim2')) {
            if ((!is_null($prevRepayment) && $prevRepayment->getOverdueDays() < 21) || $loan->getOverdueDays() < 21) {
                return redirect()->back()->with('msg_err', 'Соглашение об исполнении может быть создано только после 21 дня просрочки');
            }
        }

        $discountedMoney = round($mDet->pc * ($repayment->discount / 100));
        if ($discountedMoney > $repayment->paid_money) {
            Log::error('RepaymentController.create4', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment]);
            return redirect()->back()->with('msg_err', 'Ошибка! Сумма взноса меньше суммы скидки. Проверьте вводимую сумму');
        }
        if ($repayment->repaymentType->isClosing() && Repayment::where('loan_id', $loan->id)->where('repayment_type_id', $repayment->repayment_type_id)->count() > 0) {
            return redirect()->back()->with('msg_err', 'Ошибка! На договоре уже есть закрытие');
        }
        //отнимаем от процентов сумму по промокоду, если такой имеется в заявке
        //промокод применяется к кредитникам если делается закрытие и промокод 
        //доступен и процент вида займа не меньше двух и у кредитника нет 
        //доп документов и кредитник не просрочен
        //либо админ может поставить галочку применить промокод в любом случае и тогда он применится
        $promo_discount = 0;
        if ($repayment->repaymentType->isClosing() && $loan->canCloseWithPromocode($mDet)) {
            $promo_discount = 500;
            $discountedMoney = ($mDet->pc - $discountedMoney - config('options.promocode_discount') < 0) ? $mDet->pc : $discountedMoney + config('options.promocode_discount');
        } else if ($req->has('promocode_anyway')) {
            $promo_discount = 500;
            $discountedMoney = ($mDet->pc - $discountedMoney - config('options.promocode_discount') < 0) ? $mDet->pc : $discountedMoney + config('options.promocode_discount');
        }
        if ($repayment->paid_money + $discountedMoney > $mDet->money) {
            Log::error('RepaymentController.create1', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment]);
            return redirect()->back()->with('msg_err', 'Ошибка! Сумма договора не может быть больше необходимой суммы.');
        } else if ((int) $repayment->paid_money + (int) $discountedMoney != (int) $mDet->money && $repayment->repaymentType->isClosing()) {
            Log::error('RepaymentController.create2', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment]);
            return redirect()->back()->with('msg_err', 'Ошибка! Для закрытия договора необходимо: ' . ($mDet->money / 100 - $discountedMoney) . ' руб.');
        }
        $minReqMoney = ($repayment->repaymentType->mandatory_percents) ? ($mDet->all_pc - $discountedMoney) : 0;
        if ($repayment->repaymentType->mandatory_percents && ($repayment->paid_money - $minReqMoney) < 0) {
            Log::error('RepaymentController.create3', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment, 'minreqmoney' => $minReqMoney]);
            return redirect()->back()->with('msg_err', 'Ошибка! Сумма договора не может быть меньше ' . ($minReqMoney / 100) . ' руб.');
        }
        //нельзя создавать в один день два допника с комиссией
        if ($repayment->repaymentType->isDopCommission() && !is_null($prevRepayment) && $prevRepayment->repaymentType->isDopCommission() && HelperUtil::DateIsToday($prevRepayment->created_at)) {
            return redirect()->back()->with('msg_err', 'Ошибка! Для создания еще одного соглашения за сегодня, необходимо подать на удаление предыдущее');
        }

        //нельзя делать допник на полную сумму задолженности
        if ($repayment->repaymentType->isDopnik() && ($repayment->paid_money + $discountedMoney) == $mDet->money) {
            return redirect()->back()->with('msg_err', 'Ошибка! Оплаченная сумма покрывает задолженность. Создайте закрытие.');
        }

        //отнимаем от необходимых процентов сумму скидки
        $mDet->pc -= $discountedMoney;
        if (is_null(Auth::user())) {
            return redirect()->back()->with('msg_err', 'Ошибка аутентификации');
        }
        $user = \App\User::find(Input::get('user_id', null));
        $user_id = (is_null($user)) ? Auth::user()->id : $user->id;
        $subdiv = \App\Subdivision::find(Input::get('subdivision_id', null));
        $subdiv_id = (is_null($user)) ? Auth::user()->subdivision_id : $subdiv->id;

        $repayment->user_id = $user_id;
        $repayment->subdivision_id = $subdiv_id;
        $rep1cParams = [
            'created_at' => Carbon::now()->format('YmdHis'),
            'Number' => '',
            'passport_series' => $loan->claim->passport->series,
            'passport_number' => $loan->claim->passport->number,
            'money' => $mDet->od,
            'loan_id_1c' => $loan->id_1c,
            'subdivision_id_1c' => $repayment->subdivision->name_id,
            'user_id_1c' => $repayment->user->id_1c,
            'od' => 0,
            'pc' => 0,
            'exp_pc' => 0,
            'fine' => 0,
            'comment' => $repayment->comment,
            'time' => 0
        ];
        if ($repayment->repaymentType->isClaim() || $repayment->repaymentType->isPeace() || $repayment->repaymentType->isClosing()) {
            $rep1cParams['money'] = $mDet->money;
            $rep1cParams['od'] = $mDet->od;
            $rep1cParams['pc'] = $mDet->pc;
            $rep1cParams['exp_pc'] = $mDet->exp_pc;
            $rep1cParams['fine'] = $mDet->fine;
            $rep1cParams['time'] = $mDet->exp_days;
        } else if ($repayment->repaymentType->isDopnik()) {
            $rep1cParams['fine'] = $mDet->fine;
            if ($mDet->exp_days < 0 && Carbon::now()->gte(new Carbon(config('options.new_rules_day')))) {
//                    $repayment->time += $mDet->exp_days;
            }
        }
        if ($repayment->repaymentType->isDopnik() || $repayment->repaymentType->isClosing()) {
            $rep1cParams['discount'] = $repayment->discount;
            $rep1cParams['loantype_id_1c'] = '';
        }
        if ($repayment->time > config('options.max_dopnik_time')) {
            return redirect()->back()->with('msg_err', 'Срок не может быть больше ' . config('options.max_dopnik_time') . ' дней');
        }

        $payOrder = $repayment->repaymentType->getPaymentsOrder();

        //добавляем госпошлину если закрывают СУЗ
        if (!is_null($importantMoneyData) && array_key_exists('tax', $importantMoneyData)) {
            array_unshift($payOrder, 'tax');
        }

        $mTemp = $repayment->paid_money;
        //сначала отнимаем деньги по уки=========================================
        if ($loan->isUkiActive() && $mTemp >= 59000 && $repayment->repaymentType->isClosing()) {
            $ukiOrder = new Order();
            $orderM = 50000;
            $ukiOrder->fill([
                'loan_id' => $loan->id,
                'money' => $orderM, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                'purpose' => Order::P_UKI
            ]);
            $mTemp -= $orderM;
            $orders[] = $ukiOrder;

            $ukiOrder2 = new Order();
            $orderM = 9000;
            $ukiOrder2->fill([
                'loan_id' => $loan->id,
                'money' => $orderM, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                'purpose' => Order::P_UKI_NDS
            ]);
            $mTemp -= $orderM;
            $orders[] = $ukiOrder2;
        }
        //=======================================================================
        //отнимаем комиссию если вдруг это допник с комиссией
        if ($repayment->repaymentType->isDopCommission() || $repayment->repaymentType->isClosing()) {
            $dopComCalc = new \App\Utils\DopCommissionCalculation($loan, $repayment->time, $mDet->od, $prevRepayment);
            $money_to_pay_in_rubs = $dopComCalc->money_to_pay / 100;
            if (!Auth::user()->isAdmin() && ceil($money_to_pay_in_rubs) != $money_to_pay_in_rubs && $repayment->repaymentType->isDopCommission()) {
                return redirect()->back()->with('msg_err', 'Внимание, сумма комиссии не может быть с копейками (' . $money_to_pay_in_rubs . ')! Обратитесь в тех. поддержку');
            }
            //если количество неиспользованных дней по прошлому соглашению равна нулю, то ничего не делать
            if ($dopComCalc->unused_days > 0 && !is_null($prevRepayment) && $prevRepayment->repaymentType->isDopCommission()) {
                if (!$this->createDopCommissionDerealization($repayment, $dopComCalc)) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка при возврате суммы');
                }
            }
        }
        if ($repayment->repaymentType->isDopCommission()) {
            $dopComMoney = $dopComCalc->money_to_pay;
            $dopComMoneyNDS = $dopComCalc->money_for_nds;
            $comOrder = new Order();
            $orderM = $dopComCalc->money_for_commission;
            $comOrder->fill([
                'loan_id' => $loan->id,
                'money' => $orderM, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                'purpose' => Order::P_COMMISSION
            ]);
            $mTemp -= $orderM;
            $orders[] = $comOrder;

            $comOrder2 = new Order();
            $orderM = $dopComMoneyNDS;
            $comOrder2->fill([
                'loan_id' => $loan->id,
                'money' => $orderM, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                'purpose' => Order::P_COMMISSION_NDS
            ]);
            $mTemp -= $orderM;
            $orders[] = $comOrder2;
        }

        foreach ($payOrder as $item) {
            if ($mTemp <= 0) {
                continue;
            }
            $repayment->{'was_' . $item} = $mDet->{$item};
            $orderM = round(($mTemp - $mDet->{$item} < 0) ? $mTemp : $mDet->{$item});
//                if ($item == 'pc' && $discountedMoney > 0) {
//                    $orderM -= $discountedMoney;
//                }
            if ($orderM > 0) {
                $order = new Order();
                $order->fill([
                    'loan_id' => $loan->id,
                    'money' => $orderM, 'type' => OrderType::getPKOid(),
                    'passport_id' => $loan->claim->passport_id,
                    'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                    'purpose' => with(Order::getPurposeTypes())[$item]
                ]);
                $orders[] = $order;
                $repayment->{$item} = $orderM;
                if ($item == 'fine') {
                    $repayment->fine = $mDet->fine - $orderM;
                }
                if ($repayment->repaymentType->isDopnik()) {
                    $rep1cParams[$item] = $order->money;
                    if ($item == 'od') {
                        $rep1cParams['money'] -= $order->money;
                    }
                } else if ($repayment->repaymentType->isClaim()) {
//                        $rep1cParams[$item] -= $order->money;
//                        $rep1cParams['money'] -= $order->money;
                } else if ($repayment->repaymentType->isPeace()) {
                    $rep1cParams[$item] -= $order->money;
                    $rep1cParams['money'] -= $order->money;
                }
            }
            $mTemp -= $orderM;
        }

        if ($req->has('months')) {
            $months = (int) $req->months;
            $repayment->time = Carbon::now()->addMonths($months)->diffInDays(Carbon::now());

            //создать график платежей для мирового
            if ($repayment->repaymentType->isPeace() && $months > 0) {
                $sum = (int) $repayment->req_money - (int) $repayment->paid_money;
                $pay = round($sum / $months);
                $lastpay = $pay * $months - $pay * ($months - 1);
                $mdiff = $sum - $pay * ($months - 1) - $lastpay;
                if ($mdiff != 0) {
                    $lastpay += $mdiff;
                }
                $paysList = [];
                if (is_null($repayment->created_at)) {
                    $repayment->created_at = Carbon::now();
                }
                for ($m = 0; $m < $months; $m++) {
                    $payMoney = (($m == $months - 1) ? $lastpay : $pay);
                    $payday = new Carbon($repayment->created_at);
                    //если дата создания документа - последний день месяца
                    //то платеж ставить на последний день месяца
                    $payday->day = 1;
                    $payday->addMonths($m + 1);
                    \PC::debug([$repayment->created_at, $payday]);
                    if ($repayment->created_at->day > $payday->daysInMonth) {
                        $payday = $payday->endOfMonth();
                    } else {
                        $payday->day = $repayment->created_at->day;
                    }
                    $peacePays[] = new PeacePay([
                        'money' => $payMoney,
                        'end_date' => $payday->format('Y-m-d'),
                        'total' => $payMoney,
                        'last_payday' => with(new Carbon($repayment->created_at))->format('Y-m-d')
                    ]);
                    $paysList[] = [
                        'exp_pc' => 0, 'fine' => 0,
                        'money' => $payMoney,
                        'end_date' => $payday->format('YmdHis'),
                        'closed' => 0,
                        'total' => $payMoney
                    ];
                }
                $rep1cParams['pays'] = json_encode($paysList);
            }
        }
        $rep1cParams['time'] = $repayment->time;
        DB::beginTransaction();
        try {
            //если заплаченные деньги равны требуемой сумме то делать кредитник закрытым
            if ($repayment->paid_money + $discountedMoney == $mDet->money) {
                if (!$loan->close(($repayment->repaymentType->isClosing()) ? false : true)) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка на закрытии кредитного договора! Договор не был сохранён.');
                }
            }
            //если мировое, а график пустой - выдавать ошибку
            if ($repayment->repaymentType->isPeace() && count($peacePays) == 0) {
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка! Не хватает мировых платежей.');
            }
            /**
             * ОТПРАВЛЯЕМ ВСЕ ДОГОВОРА\ОРДЕРЫ\ГРАФИК ПЛАТЕЖЕЙ В 1С
             */
            //если какой то из этих параметров по какой то причине стал нулем то сделать его равным 0
            foreach (['pc', 'exp_pc', 'od', 'fine', 'tax'] as $i) {
                if (is_null($i) || $i == 'null') {
                    $i = 0;
                }
            }
            if ($repayment->repaymentType->isDopnik()) {
                //отправляем ДОПНИК в 1С
                if ($rep1cParams['time'] > config('options.max_dopnik_time')) {
                    $rep1cParams['time'] = config('options.max_dopnik_time');
                    $repayment->time = config('options.max_dopnik_time');
                }
                $res1c = MySoap::createRepayment($rep1cParams);
                if ($res1c['res'] == 0) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка связи с 1с. ');
                } else {
                    $repayment->id_1c = $res1c['value'];
                }
//                $this->addSaving($repayment,$orders);
            } else if ($repayment->repaymentType->isClaim()) {
                //отправляем ЗАЯВЛЕНИЕ в 1С
                $rep1cParams['time'] = $mDet->exp_days;
//                $rep1cParams['claim_type'] = ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) ? 1 : 0;
                $rep1cParams['claim_type'] = 0;
                if ($repayment->repaymentType->text_id == config('options.rtype_claim3')) {
                    $rep1cParams['claim_type'] = 2;
                    $rep1cParams['time'] = $repayment->time;
                }
                $res1c = MySoap::createClaimRepayment($rep1cParams);
                if ($res1c['res'] == 0) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', StrLib::ERR_1C);
                } else {
                    $repayment->id_1c = $res1c['value'];
                }
            } else if ($repayment->repaymentType->isPeace()) {
                $rep1cParams['time'] = $mDet->exp_days;
                //отправляем МИРОВОЕ в 1С
                $res1c = MySoap::createPeaceRepayment($rep1cParams);
                if ((int) $res1c->result == 0) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', StrLib::ERR_1C);
                } else {
                    $repayment->id_1c = (string) $res1c->value;
                }
            } else if ($repayment->repaymentType->isClosing()) {
                //отправляем ЗАКРЫТИЕ в 1С
                $rep1cParams['time'] = 0;
                $repayment->time = 0;
                $rep1cParams['uki'] = $loan->uki;
                if ($loan->isUkiActive()) {
                    $rep1cParams['uki_money'] = config('options.uki_money') / 100;
                } else {
                    $rep1cParams['uki_money'] = 0;
                }
                if (!is_null($importantMoneyData) && !is_null($prevRepayment) && $prevRepayment->repaymentType->isSUZ()) {
                    if (!$this->closeSUZ($req, $prevRepayment, $repayment->paid_money, $mDet, $stockType)) {
                        return redirect()->back()->with('msg_err', 'Ошибка при перезаписи СУЗа');
                    }
                }
                if (isset($contracts1c) && is_array($contracts1c)) {
                    if (array_key_exists('ngbezdolgov', $contracts1c)) {
                        $rep1cParams['loantype_id_1c'] = 'ARM000015';
                    } else if (array_key_exists('akcsuzst46', $contracts1c)) {
                        $rep1cParams['loantype_id_1c'] = 'ARM000018';
                    }
//                    $rep1cParams['comment'] = 'По акции. Были суммы: Проценты ' . $prevRepayment->pc . ', пр.проценты ' . $prevRepayment->exp_pc . ', ОД ' . $prevRepayment->od . ', пеня ' . $prevRepayment->fine;
                }
//                $res1c = MySoap::createClosingRepayment($rep1cParams);
                $res1c = $this->sendClosingTo1c($rep1cParams, true, $promo_discount);
                if ($res1c['res'] == 0) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка связи с 1C при создании документа закрытия.');
                } else {
                    if (strstr('//', $res1c['value']) !== false) {
                        DB::rollback();
                        return redirect()->back()->with('msg_err', 'Ошибка! Необходимо проверить номер контрагента и кредитный договор!');
                    }
                    $repayment->id_1c = $res1c['value'];
                }
            } else if ($repayment->repaymentType->isSUZ()) {
                //отправляем СУЗ в 1С
                $res1c = MySoap::createSuzRepayment($rep1cParams);
                if ($res1c['res'] == 0) {
                    return redirect()->back()->with('msg_err', 'Ошибка связи с 1с.');
                }
            }
            //сохраняем договор в базу
            $repayment->fine = $mDet->fine;
            if (Repayment::where('id_1c', $repayment->id_1c)->where('repayment_type_id', $repayment->repayment_type_id)->count() > 0) {
                return redirect()->back()->with('msg_err', 'Задвоение договора. Обратитесь в поддержку.');
            }
            if (!$repayment->save()) {
                \PC::debug($repayment, 'repayment');
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка на сохранении договора! Договор не был сохранён.');
            }
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'repayments', $repayment);
            //сохраняем платежи в базу
            foreach ($peacePays as $peacePay) {
                $peacePay->repayment_id = $repayment->id;
                if (!$peacePay->save()) {
                    \PC::debug($peacePay, 'peace pay');
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка при сохранении мировых платежей! Договор не был сохранён.');
                }
//                Spylog::logModelAction(Spylog::ACTION_CREATE, 'peace_pays', $order);
            }
            //сохраняем все ордеры в базу попутно отправляя их в 1С
            if (!$this->saveRepaymentOrders($orders, $repayment)) {
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка на сохранении ордера! Договор не был сохранён.');
            }
            /**
             * СОЗДАНИЕ РЕАЛИЗАЦИИ И ДЕРЕАЛИЗАЦИИ ПРИ СОЗДАНИИ ДОПНИКА С КОМИССИЕЙ
             */
            if ($repayment->repaymentType->isDopCommission() && isset($dopComMoney)) {
                //отправляем запрос на создание реализации
                if (!$this->createDopCommissionRealization($repayment, $dopComMoney)) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка при создании реализации');
                }
            }
            //=====================================================================
            /**
             * Если создается закрытие то отдельно его проводим
             */
            if ($repayment->repaymentType->isClosing()) {
                $this->conductClosing($repayment->id_1c, $loan->claim->customer->id_1c);
            }
            /**
             * Если создается допник и не допник с комиссией то отдельно его проводим
             */
            if ($repayment->repaymentType->isDopnik() && !$repayment->repaymentType->isDopCommission()) {
                $this->conductDopnik($repayment->id_1c, $loan->claim->customer->id_1c);
            }
            /**
             * Если создается допник или закрытие то прикрепляем к нему приходники,
             * которые были внесены отдельно через добавить пко
             */
            if ($repayment->repaymentType->isDopnik() || $repayment->repaymentType->isClosing()) {
                $freeOrdersDate = (!isset($prevRepayment) || is_null($prevRepayment)) ? $loan->created_at : $prevRepayment->created_at;
                $freeOrders = $this->getFreeOrders($freeOrdersDate, $loan);
                foreach ($freeOrders as $fo) {
                    $fo->repayment_id = $repayment->id;
                    $fo->save();
                }
            }
            /**
             * Если делаем закрытие на суз по акции, то делаем соглашение о реконструкции задолженности
             */
            if ($repayment->repaymentType->isClosing() && is_array($importantMoneyData)) {
                $this->createReconstrAgreement($loan, $user_id, $subdiv_id, $prevRepayment, $importantMoneyData, $repayment, $stockType, $stockName);
            }
            DB::commit();
            return redirect()->back()->with('msg_suc', 'Договор успешно сохранён.');
        } catch (Exception $exc) {
            DB::rollback();
            return redirect()->back()->with('msg_err', 'Ошибка! Договор не был сохранён.');
        }
    }

    /**
     * Сохраняет переданные ордеры для переданного доп документа
     * Для мирового к дате ордера прибавляет минуту
     * Для закрытия отнимает 10 минут, для того чтобы проводки в 1с ее не вешали
     * @param array $orders
     * @param \App\Repayment $repayment
     * @return boolean
     */
    function saveRepaymentOrders($orders, $repayment) {
        $date = $repayment->created_at->format('Y-m-d H:i:s');
        if ($repayment->repaymentType->isPeace()) {
            $date = $repayment->created_at->addSecond()->format('Y-m-d H:i:s');
        } else if ($repayment->repaymentType->isClosing()) {
            $date = $repayment->created_at->subSecond()->format('Y-m-d H:i:s');
        } else if($repayment->repaymentType->isDopnik() && !$repayment->repaymentType->isDopCommission()){
            $date = $repayment->created_at->subSecond()->format('Y-m-d H:i:s');
        }

        foreach ($orders as $order) {
            $order->repayment_id = $repayment->id;
            if ($repayment->repaymentType->isClaim() && !$repayment->repaymentType->isDopCommission()) {
                if (!$order->saveThrough1cWithRegister($date)) {
                    \PC::debug($order, 'unsaved order');
                    return false;
                }
            } else {
                if (!$order->saveThrough1c($date)) {
                    \PC::debug($order, 'unsaved order');
                    return false;
                }
            }
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'orders', $order);
        }
        return true;
    }

    /**
     * Создает соглашение о реконструкции задолженности 
     * при закрытии суза единовременным платежом по акции
     * Оно кстати не отправляется в 1с и создается по сути 
     * только для печати документа
     * @param \App\Loan $loan
     * @param type $user_id
     * @param type $subdiv_id
     * @param \App\Repayment $prevRepayment тут скорее всего СУЗ
     * @param type $importantMoneyData массив с данными об изменении задолженности
     * @param \App\Repayment $repayment
     * @param type $stockType
     * @param type $stockName
     * @return Repayment
     */
    function createReconstrAgreement($loan, $user_id, $subdiv_id, $prevRepayment, $importantMoneyData, $repayment, $stockType, $stockName) {
        $reconstrSogl = new Repayment();
        $reconstrSogl->created_at = Carbon::now()->subSeconds(10)->format('Y-m-d H:i:s');
        $reconstrSogl->loan_id = $loan->id;
        $reconstrSogl->user_id = $user_id;
        $reconstrSogl->subdivision_id = $subdiv_id;
        $reconstrSogl->was_pc = $prevRepayment->pc;
        $reconstrSogl->was_exp_pc = $prevRepayment->exp_pc;
        $reconstrSogl->was_od = $prevRepayment->od;
        $reconstrSogl->was_fine = $prevRepayment->fine;
        $reconstrSogl->was_tax = $prevRepayment->tax;
        $reconstrSogl->id_1c = $prevRepayment->id_1c;
        $reconstrSogl->pc = $importantMoneyData['pc'];
        $reconstrSogl->exp_pc = $importantMoneyData['exp_pc'];
        $reconstrSogl->od = $importantMoneyData['od'];
        $reconstrSogl->fine = $importantMoneyData['fine'];
        $reconstrSogl->tax = $importantMoneyData['tax'];
        $reconstrSogl->paid_money = $repayment->paid_money;
        $reconstrSogl->req_money = $reconstrSogl->pc + $reconstrSogl->exp_pc + $reconstrSogl->od + $reconstrSogl->fine + $reconstrSogl->tax;
        $reconstrSogl->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_suzstock1'))->first())->id;
        $reconstrSogl->time = 1;
        $reconstrSogl->data = json_encode(['stockType' => $stockType, 'stockName' => $stockName]);
        $reconstrSogl->save();
        \PC::debug($reconstrSogl, 'reconstr');
        return $reconstrSogl;
    }

    /**
     * Создает реализацию на допник с комиссией
     * @param \App\Repayment $repayment допник с комиссией
     * @param type $dopComMoney сумма комиссии вместе с ндс
     * @return boolean
     */
    function createDopCommissionRealization($repayment, $dopComMoney) {
        $loan = $repayment->loan;
        $createRealizXml = [
            'created_at' => Carbon::now()->format('YmdHis'),
            'Number' => '',
            'passport_series' => $loan->claim->passport->series,
            'passport_number' => $loan->claim->passport->number,
            'money' => $dopComMoney,
            'reason' => '',
            'repayment_id_1c' => $repayment->id_1c,
            'loan_id_1c' => $repayment->loan->id_1c,
            'peace_pay_id_1c' => '',
            'order_Type' => OrderType::getPKOid(),
            'type' => 'Create_Realization',
            'purpose' => Order::P_COMMISSION,
            'subdivision_id_1c' => $repayment->subdivision->name_id,
            'user_id_1c' => $repayment->user->id_1c,
            'doc_type' => $repayment->repaymentType->getMySoapItemID()
        ];
        $resRealiz = MySoap::sendExchangeArm(MySoap::createXML($createRealizXml));
        return ((int) $resRealiz->result == 1);
    }

    /**
     * Правит реализацию на прошлый допник с комиссией 
     * и создает расходник на оставшуюся сумму
     * @param \App\Repayment $repayment
     * @param \App\Utils\DopCommissionCalculation $dopComCalc
     * @return boolean
     */
    function createDopCommissionDerealization($repayment, $dopComCalc) {
        $loan = $repayment->loan;
        $xml = [
            'created_at' => Carbon::now()->format('YmdHis'),
            'Number' => '',
            'customer_id' => $loan->claim->customer->id_1c,
            'money' => StrUtils::kopToRub($dopComCalc->money_spent), //сумма которую платит
            'moneyRKO' => StrUtils::kopToRub($dopComCalc->money_to_return), //сумма которая осталась
            'loan_id_1c' => $loan->id_1c,
            'type' => 'CreateDeRealization',
            'subdivision_id_1c' => $repayment->subdivision->name_id,
            'user_id_1c' => $repayment->user->id_1c
        ];
        if($dopComCalc->money_spent<=0){
            Log::error('RepaymentController.createDopCommissionDerealization dopcomcalc.money_spent<=0',['dopcomcalc'=>$dopComCalc,'repayment'=>$repayment]);
            return 0;
        }
        $resRealiz = MySoap::sendExchangeArm(MySoap::createXML($xml));
        return ((int) $resRealiz->result == 1);
    }

    /**
     * Проводит закрытие в 1с
     * @param string $repayment_id_1c номер закрытия в 1с
     * @param string $customer_id_1c номер контрагента в 1с
     * @return type
     */
    public function conductClosing($repayment_id_1c, $customer_id_1c) {
        return MySoap::sendExchangeArm(MySoap::createXML(['Number' => $repayment_id_1c, 'customer_id_1c' => $customer_id_1c, 'type' => 'ActivateClose']));
    }
    /**
     * Проводит допник в 1с
     * @param string $repayment_id_1c
     * @param string $customer_id_1c
     * @return type
     */
    public function conductDopnik($repayment_id_1c, $customer_id_1c) {
        return MySoap::sendExchangeArm(MySoap::createXML(['Number' => $repayment_id_1c, 'customer_id_1c' => $customer_id_1c, 'type' => 'ActivateDopnik']));
    }

    public function generateOrdersForRepayment($repayment, $mDet, $user_id, $subdiv_id) {
        $orders = [];
        $payOrder = $repayment->repaymentType->getPaymentsOrder();
        $mTemp = $repayment->paid_money;
        //сначала отнимаем деньги по уки=========================================
        if ($repayment->loan->isUkiActive() && $mTemp >= 59000 && $repayment->repaymentType->isClosing()) {
            $ukiOrder = new Order();
            $orderM = 50000;
            $ukiOrderDefParams = [
                'loan_id' => $loan->id,
                'type' => OrderType::getPKOid(),
                'passport_id' => $repayment->loan->claim->passport_id,
                'user_id' => $user_id, 'subdivision_id' => $subdiv_id
            ];
            $ukiOrder->fill($ukiOrderDefParams);
            $ukiOrder->fill(['money' => $orderM, 'purpose' => Order::P_UKI]);
            $mTemp -= $orderM;
            $orders[] = $ukiOrder;

            $ukiOrder2 = new Order();
            $orderM = 9000;
            $ukiOrder2->fill($ukiOrderDefParams);
            $ukiOrder2->fill(['money' => $orderM, 'purpose' => Order::P_UKI_NDS]);
            $mTemp -= $orderM;
            $orders[] = $ukiOrder2;
        }
        //=======================================================================
        foreach ($payOrder as $item) {
            if ($mTemp <= 0) {
                continue;
            }
            $repayment->{'was_' . $item} = $mDet->{$item};
            $orderM = round(($mTemp - $mDet->{$item} < 0) ? $mTemp : $mDet->{$item});
            if ($orderM > 0) {
                $order = new Order();
                $order->fill([
                    'loan_id' => $loan->id,
                    'money' => $orderM, 'type' => OrderType::getPKOid(),
                    'passport_id' => $loan->claim->passport_id,
                    'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                    'purpose' => with(Order::getPurposeTypes())[$item]
                ]);
                $orders[] = $order;
                $repayment->{$item} = $orderM;
                if ($item == 'fine') {
                    $repayment->fine = $mDet->fine - $orderM;
                }
            }
            $mTemp -= $orderM;
        }
        return $orders;
    }

    public function attachFreeOrdersToRepayment($repayment, $prevRepayment) {
        $freeOrdersDate = (!isset($prevRepayment) || is_null($prevRepayment)) ? $repayment->loan->created_at : $prevRepayment->created_at;
        $freeOrders = $this->getFreeOrders($freeOrdersDate, $repayment->loan);
        foreach ($freeOrders as $fo) {
            $fo->repayment_id = $repayment->id;
            $fo->save();
        }
    }

    public function getFreeOrders($dateStart, $loan) {
        return Order::where('created_at', '>=', $dateStart)->where('type', OrderType::getPKOid())->whereNull('repayment_id')->where('loan_id', $loan->id)->get();
    }

    /**
     * Удаляет договор
     * @param type $id идентификатор договора
     * @return type
     */
    public function remove($id) {
        DB::beginTransaction();
        $rep = Repayment::find($id);
        if (is_null($rep)) {
            DB::rollback();
            Log::error('repaymentController.remove 2');
            return redirect()->back()->with('msg', 'Ошибка! Договор не найден.')->with('class', 'alert-danger');
        }
//        if (with(new Carbon($rep->created_at))->setTime(0, 0, 0)->ne(Carbon::now()->setTime(0, 0, 0))) {
//            return redirect()->back()->with('msg', 'Можно удалить ордер только за сегодня.')->with('class', 'alert-danger');
//        }
        //если удаляется договор закрытия, то выставлять флаг "не закрыт" для кредитника
        if ($rep->repaymentType->isClosing()) {
            try {
                $rep->loan->update(['closed' => 0]);
            } catch (Exception $ex) {
                DB::rollback();
                Log::error('repaymentController.remove 1');
                return redirect()->back()->with('msg', 'Ошибка! Договор не был удалён.')->with('class', 'alert-danger');
            }
        }
        if ($rep->repaymentType->isSuzStock()) {
            if ($rep->delete()) {
                DB::commit();
                return redirect()->back()->with('msg_suc', 'Договор успешно удалён.');
            } else {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR);
            }
        }
        if (!$rep->deleteThrough1c()) {
            DB::rollback();
            Log::error('repaymentController.remove 3');
            return redirect()->back()->with('msg_err', 'Договор не удален! НУЖНО ПРОВЕРИТЬ УДАЛИЛСЯ ЛИ ОН В 1С');
        }
        $remreq = RemoveRequest::where('doc_id', $rep->id)->where('doc_type', $rep->repaymentType->getMySoapItemID())->first();
        if (!is_null($remreq)) {
            $remreq->update(['status' => RemoveRequest::STATUS_DONE, 'user_id' => Auth::user()->id]);
        }
        Spylog::logModelAction(Spylog::ACTION_DELETE, 'repayments', $rep);
        DB::commit();
        return redirect()->back()->with('msg_suc', 'Договор успешно удалён.');
    }

    /**
     * получает данные по договору 
     * @param type $id идентификатор договора
     * @return type
     */
    public function getRepayment($id) {
        $rep = Repayment::find($id);
        if (!is_null($rep)) {
            $repayment = $rep->toArray();
            $repayment["created_at"] = with(new Carbon($repayment["created_at"]))->format('Y-m-d');
            $repayment['months'] = with(new Carbon($rep->created_at))->setTime(0, 0, 0)->addDays($rep->time + 1)->diffInMonths($rep->created_at);
            return $repayment;
        } else {
            return null;
        }
    }

    /**
     * Редактирование договора (допника\мирового\заявления)
     * @param Request $req
     * @return type
     */
    public function update(Request $req) {
        if (!$req->has('id')) {
            return redirect()->back()->with('msg', 'Ошибка! Договор не был сохранён.')->with('class', 'alert-danger');
        }
        $rep = Repayment::find($req->id);
        if (is_null($rep)) {
            return redirect()->back()->with('msg', 'Ошибка! Договор не был сохранён.')->with('class', 'alert-danger');
        }
        if (with(new Carbon($rep->created_at))->setTime(0, 0, 0)->lt(Carbon::now()->setTime(0, 0, 0))) {
            return $this->updateOnlyArm($req, $rep);
        }
        $input = Input::all();
        foreach ($input as $k => $v) {
            if (in_array($k, ['od', 'pc', 'exp_pc', 'fine', 'paid_money', 'req_money'])) {
                $input[$k] = StrUtils::parseMoney($v);
            }
        }
        $oldRep = $rep->toArray();
        $rep->fill($input);
        $rep1cParams = [
            'created_at' => with(new Carbon($rep->created_at))->format('YmdHis'),
            'Number' => $rep->id_1c,
            'passport_series' => $rep->loan->claim->passport->series,
            'passport_number' => $rep->loan->claim->passport->number,
            'money' => $rep->req_money,
            'loan_id_1c' => $rep->loan->id_1c,
            'subdivision_id_1c' => (is_null($rep->subdivision)) ? "" : $rep->subdivision->name_id,
            'user_id_1c' => (is_null($rep->user)) ? "" : $rep->user->id_1c,
            'od' => $rep->od,
            'pc' => $rep->pc,
            'exp_pc' => $rep->exp_pc,
            'fine' => $rep->fine,
            'comment' => $rep->comment,
            'time' => $rep->time
        ];
        $orders = $rep->orders;
        if (!is_null($orders) && count($orders) > 0) {
            foreach ($orders as $order) {
                if (with(new Carbon($order->created_at))->gt(with(new Carbon($rep->created_at))->addMinutes(5))) {
                    return redirect()->back()->with('msg', 'Ошибка! Невозможно изменить, по договору уже есть кассовые операции.')->with('class', 'alert-danger');
                }
            }
        }
        $newRepDate = new Carbon($rep->created_at);
        $loanDate = new Carbon($rep->loan->created_at);
        if ($newRepDate->setTime(0, 0, 0)->lt($loanDate->setTime(0, 0, 0))) {
            return redirect()->back()->with('msg', 'Ошибка! Дата договора меньше даты кредитника.')->with('class', 'alert-danger');
        }
        foreach ($rep->loan->repayments as $r) {
            if ($rep->id != $r->id && $newRepDate->lt($r->created_at->setTime(0, 0, 0))) {
                return redirect()->back()->with('msg', 'Ошибка! Дата договора меньше даты другого договора.')->with('class', 'alert-danger');
            }
        }

        DB::beginTransaction();
        if (!$rep->save()) {
            return redirect()->back()->with('msg', 'Ошибка! Договор не был сохранён.')->with('class', 'alert-danger');
        }

        if ($rep->repaymentType->isDopnik()) {
            $res1c = MySoap::createRepayment($rep1cParams);
            \PC::debug($res1c, 'результат');
            if ($res1c['res'] == 0) {
                DB::rollback();
                return redirect()->back()->with('msg', 'Ошибка связи с 1с.')->with('class', 'alert-danger');
            }
        } else if ($rep->repaymentType->isClaim()) {
            $rep1cParams['time'] = '';
            $res1c = MySoap::createClaimRepayment($rep1cParams);
            \PC::debug($res1c, 'результат');
            if ($res1c['res'] == 0) {
                DB::rollback();
                return redirect()->back()->with('msg', 'Ошибка связи с 1с.')->with('class', 'alert-danger');
            }
        } else if ($rep->repaymentType->isPeace()) {
            if ($req->has('months')) {
                $rep1cParams['time'] = $req->months;
                $months = $req->months;
            } else {
                return redirect()->back()->with('msg', 'Ошибка. Количество месяцев отстутствует.')->with('class', 'alert-danger');
            }
            $pays = $rep->peacePays;
            foreach ($pays as $p) {
                if ($p->fine > 0 || $p->closed || $p->exp_pc > 0) {
                    return redirect()->back()->with('msg', 'Ошибка. Невозможно отредактировать, есть изменения в графике.')->with('class', 'alert-danger');
                }
            }
            if (!PeacePay::where('repayment_id', $rep->id)->delete()) {
                return redirect()->back()->with('msg', 'Ошибка. Не удалось удалить график платежей')->with('class', 'alert-danger');
            }
            $sum = (int) $rep->req_money - (int) $rep->paid_money;
            $pay = round($sum / $months);
            $lastpay = $pay * $months - $pay * ($months - 1);
            $mdiff = $sum - $pay * ($months - 1) - $lastpay;
            if ($mdiff != 0) {
                $lastpay += $mdiff;
            }
            $paysList = [];
            for ($m = 0; $m < $months; $m++) {
                $payMoney = (($m == $months - 1) ? $lastpay : $pay);
                PeacePay::create([
                    'money' => $payMoney,
                    'end_date' => with(new Carbon($rep->created_at))->addMonths($m + 1)->format('Y-m-d'),
                    'total' => $payMoney,
                    'last_payday' => with(new Carbon($rep->created_at))->format('Y-m-d')
                ]);
                $paysList[] = [
                    'exp_pc' => 0, 'fine' => 0,
                    'money' => $payMoney,
                    'end_date' => with(new Carbon($rep->created_at))->addMonths($m + 1)->format('YmdHis'),
                    'closed' => 0,
                    'total' => $payMoney
                ];
            }
            $rep1cParams['pays'] = json_encode($paysList);

            $res1c = MySoap::createPeaceRepayment($rep1cParams);
            \PC::debug($res1c, 'результат');
            if ($res1c['res'] == 0) {
                DB::rollback();
                return redirect()->back()->with('msg', 'Ошибка связи с 1с.')->with('class', 'alert-danger');
            }
        } else if ($rep->repaymentType->isSUZ()) {
            $res1c = MySoap::createSuzRepayment($rep1cParams);
            \PC::debug($res1c, 'результат');
            if ($res1c['res'] == 0) {
                DB::rollback();
                return redirect()->back()->with('msg', 'Ошибка связи с 1с.')->with('class', 'alert-danger');
            }
        }
        Spylog::logModelChange('repayments', $rep, $input);
        DB::commit();
        return redirect()->back()->with('msg', 'Договор успешно отредактирован.')->with('class', 'alert-success');
    }

    public function updateOnlyArm(Request $req, $rep) {
        $input = $req->all();
        $oldrep = $rep->toArray();
        $rep->was_pc = StrUtils::parseMoney($input['was_pc']);
        $rep->was_exp_pc = StrUtils::parseMoney($input['was_exp_pc']);
        $rep->was_od = StrUtils::parseMoney($input['was_od']);
        $rep->was_fine = StrUtils::parseMoney($input['was_fine']);
        $rep->time = $input['time'];
        $rep->save();
        Spylog::logModelChange('repayments', $oldrep, $input);
        return redirect()->back()->with('msg', 'Договор успешно отредактирован.')->with('class', 'alert-success');
    }

    /**
     * добавляет платёж к текущему договору
     * @param Request $req
     * @return type
     */
    public function addPayment(Request $req) {
        if (!$req->has('loan_id')) {
            return redirect()->back()->with('msg_err', 'Ошибка! Платёж не был внесён. Кредитный договор не найден');
        }
        $loan = Loan::find($req->loan_id);
        if (is_null($loan)) {
            return redirect()->back()->with('msg_err', 'Ошибка! Платёж не был внесён. Кредитный договор не найден');
        }
        $repsNum = count($loan->repayments);
        if (!$req->has('repayment_id') && $repsNum > 0) {
            return redirect()->back()->with('msg_err', 'Ошибка! Платёж не был внесён.');
        }

        $mDet = $loan->getRequiredMoneyDetails();

        $paidMoney = StrUtils::parseMoney($req->money);
        
        //платеж по списанным должникам
        if(!is_null($loan->data)){
            $loanData = json_decode($loan->data,true);
            if(array_key_exists('spisan', $loanData) && array_key_exists('total', $loanData['spisan'])){
                if($paidMoney>(int)$loanData['spisan']['total']){
                    return redirect()->back()->with('msg_err', 'Внесённая сумма превышает необходимую');
                }
                return $this->addPaymentOnSpisan($paidMoney,$loan,($repsNum==0)?null:Repayment::find($req->get('repayment_id')));
            }
        }

        if ($paidMoney > $mDet->money) {
            return redirect()->back()->with('msg_err', 'Внесённая сумма превышает необходимую');
        }

        $user = Auth::user();
        if (is_null($user)) {
            return redirect()->back()->with('msg_err', 'Ошибка аутентификации');
        }
        
        $paidMoneyStartValue = $paidMoney;
        $orders = [];
        $pays = [];
        $moneyForBigOrders = 0;
        $loan->last_payday = Carbon::now()->format('Y-m-d H:i:s');

        if ($repsNum == 0) {
            //если доп договоров нету
//            if ($paidMoney >= ($mDet->pc + $mDet->exp_pc)) {
//                return redirect()->back()->with('msg_err', 'Внесённая сумма превышает сумму процентов. Необходимо создать дополнительное соглашение.');
//            } else {
            return $this->addFreePayment($loan, $paidMoney, $mDet, $req);
//            }
            $payOrder = ["0" => "exp_pc", "1" => "pc"];
            $moneyForBigOrders = $paidMoney;
            $loan->fine = $mDet->fine;
//            $loan->last_payday = Carbon::now()->format('Y-m-d H:i:s');
        } else {
            //если доп договора есть
            $rep = Repayment::find($req->get('repayment_id'));
//            if (!is_null($rep) && $rep->repaymentType->isDopnik() && $paidMoney < ($mDet->pc + $mDet->exp_pc)) {
            if (!is_null($rep) && ($rep->repaymentType->isDopnik() || $rep->repaymentType->isClaim())) {
                return $this->addFreePayment($loan, $paidMoney, $mDet, $req);
            }
            if (!is_null($rep) && $rep->repaymentType->isPeace() && $rep->repaymentType->text_id != config('options.rtype_peace')) {
//            if (!is_null($rep) && $rep->repaymentType->isPeace()) {
                if ($rep->isActive()) {
                    return $this->addPeacePayment($loan, $paidMoney, $mDet, $req);
                } else {
                    return $this->addFreePayment($loan, $paidMoney, $mDet, $req);
                }
            }
//            $rep->last_payday = Carbon::now()->format('Y-m-d H:i:s');
            if (!is_null($rep->repaymentType)) {
                $payOrder = $rep->repaymentType->getPaymentsOrder();
                if ($rep->isArhivUbitki()) {
                    $payOrder = ['0' => 'tax', '1' => 'exp_pc', '2' => 'pc', '3' => 'od', '4' => 'fine'];
                }
            } else {
                $payOrder = RepaymentType::DEF_PAY_ORDER;
            }
            if ($rep->repaymentType->isSUZ()) {
                return $this->addSuzPayment($req, $rep, $payOrder, $paidMoneyStartValue, $mDet);
            }
            $rep1cParams = [
                'created_at' => with(new Carbon($rep->created_at))->format('YmdHis'),
                'Number' => $rep->id_1c,
                'passport_series' => $rep->loan->claim->passport->series,
                'passport_number' => $rep->loan->claim->passport->number,
                'money' => $mDet->money,
                'loan_id_1c' => $rep->loan->id_1c,
                'subdivision_id_1c' => (is_null($rep->subdivision)) ? "" : $rep->subdivision->name_id,
                'user_id_1c' => (is_null($rep->user)) ? "" : $rep->user->id_1c,
                'od' => (is_null($rep->od)) ? 0 : $rep->od,
                'pc' => (is_null($rep->pc)) ? 0 : $rep->pc,
                'exp_pc' => (is_null($rep->exp_pc)) ? 0 : $rep->exp_pc,
                'fine' => (is_null($rep->fine)) ? 0 : $rep->fine,
                'comment' => $rep->comment,
                'time' => $rep->time
            ];
            if ($rep->repaymentType->isDopnik() || $rep->repaymentType->isClosing()) {
                $rep1cParams['od'] = 0;
                $rep1cParams['pc'] = 0;
                $rep1cParams['exp_pc'] = 0;
            } else {
                $rep1cParams['od'] = $mDet->od;
                $rep1cParams['pc'] = $mDet->pc;
                $rep1cParams['exp_pc'] = $mDet->exp_pc;
                $rep1cParams['fine'] = $mDet->fine;
            }
            if ($paidMoney >= ($mDet->pc + $mDet->exp_pc) && $rep->repaymentType->isDopnik()) {
                return redirect()->back()->with('msg', 'Внесённая сумма превышает сумму процентов и просроченных процентов. Вместо приходника необходимо создать договор.')->with('class', 'alert-danger');
            }
//            if ($paidMoney < $mDet->peace_pays_exp_pc + $mDet->peace_pays_fine) {
//                return redirect()->back()->with('msg', 'Внесённая сумма должна покрывать просроченные проценты и пеню для платежей')->with('class', 'alert-danger');
//            }

            if ($rep->repaymentType->isPeace()) {
                $rep1cParams['od'] = $mDet->od;
                $rep1cParams['pc'] = $mDet->pc;
                $rep1cParams['exp_pc'] = $mDet->exp_pc;
                $rep1cParams['fine'] = $mDet->fine;
                //сначала зачисляем за проценты по платежам
                $pays = $mDet->peace_pays;
                //считаем по платежам
                $paysNum = count($pays);
                $sled = 0;
                $per = 1;
                $pred = 1;
                $cur_pay_id = 0;
                $last_payday = Carbon::now()->format('Y-m-d');
                \PC::debug($pays);
                foreach ($pays as $pay) {
                    $rep1cParams['fine'] += $pay['fine'];
//                    $rep1cParams['fine'] += $pay['fine'] + $pay['exp_pc'];
//                    foreach (['exp_pc', 'fine'] as $item) {
                    foreach (['exp_pc', 'fine'] as $item) {
                        if ($paidMoney > 0) {
                            $orderMoney = ($paidMoney - $pay->{$item} < 0) ? $paidMoney : $pay->{$item};
                            if ($orderMoney > 0) {
                                $pay->{$item} -= $orderMoney;
//                                $pay->total -= $orderMoney;
                                $pay->last_payday = Carbon::now()->format('Y-m-d');
                                $order = new Order();
                                $order->fill([
                                    'loan_id' => $loan->id, 'repayment_id' => $rep->id,
                                    'money' => $orderMoney, 'type' => OrderType::getPKOid(),
                                    'passport_id' => $loan->claim->passport_id,
                                    'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                                    'purpose' => with(Order::getPurposeTypes())[$item],
                                    'peace_pay_id' => $pay->id
                                ]);
                                if (isset($rep1cParams) && !is_null($rep1cParams)) {
                                    $rep1cParams['fine'] -= $order->money;
//                                    $rep1cParams['money'] -= $order->money;
                                }
                                $orders[] = $order;
                            }
                            $paidMoney -= $orderMoney;
                        }
                    }
                    //для того чтобы сначала пройти все просроченные платежи по порядку
                    $pay_end_date = new Carbon($pay->end_date);
                    if ($pay_end_date->lt(Carbon::now())) {
                        $per = 1;
                        $sled = 0;
                    }
                    //если текущий месяц погашен и следом за ним есть еще месяц и он тоже погашен, 
                    //то установить переключатель на то чтобы брать платежи с конца
                    if ($sled == 0 && $pay->total == 0 && HelperUtil::DatesEqByYearAndMonth($pay_end_date, Carbon::now())) {
                        if ($cur_pay_id < $paysNum - 2) {
                            $nextPay = $pays[$cur_pay_id + 1];
                            if ($nextPay->total == 0 && HelperUtil::DatesEqByYearAndMonth(new Carbon($nextPay->end_date), Carbon::now()->addMonth())) {
                                $sled = 1;
                                $per = 0;
                            }
                        }
                    }
                    if ($sled == 1) {
                        $lastPay = $pays[$paysNum - $pred];
                        if ($paidMoney > 0) {
                            if ($lastPay->total > $paidMoney) {
                                $pay->last_payday = $last_payday;
                                $lastPay->total -= $paidMoney;
                                $moneyForBigOrders += $paidMoney;
                                $paidMoney = 0;
                                $sled = 0;
                                \PC::debug([$lastPay->total, $paidMoney, $lastPay->end_date], 'pay1');
                                break;
                            } else {
                                $pay->last_payday = $last_payday;
                                $paidMoney -= $lastPay->total;
                                $moneyForBigOrders += $lastPay->total;
                                $lastPay->total = 0;
                                $lastPay->closed = 1;
                                $pred = $pred + 1;
                                $sled = 1;
                                \PC::debug([$lastPay->total, $paidMoney, $lastPay->end_date], 'pay2');
                            }
                        }
                    }
                    if ($pay->total > 0 && $per == 1) {
                        if ($paidMoney > 0) {
                            if ($pay->total > $paidMoney) {
                                $pay->last_payday = $last_payday;
                                $pay->total -= $paidMoney;
                                $moneyForBigOrders += $paidMoney;
                                $paidMoney = 0;
                                \PC::debug([$pay->total, $paidMoney, $pay->end_date], 'pay3');
                                break;
                            } else {
                                $pay->last_payday = $last_payday;
                                $paidMoney -= $pay->total;
                                $moneyForBigOrders += $pay->total;
                                $pay->total = 0;
                                $pay->closed = 1;
                                $sled = 1;
                                \PC::debug([$pay->total, $paidMoney, $pay->end_date], 'pay4');
                            }
                        }
                        $per = 0;
                    }
                    $cur_pay_id++;
                }
//                //снимаем суммы пени с самого мирового
//                foreach (['exp_pc', 'fine'] as $item) {
//                    if ($paidMoney > 0) {
//                        $orderMoney = ($paidMoney - $rep1cParams[$item] < 0) ? $paidMoney : $rep1cParams[$item];
//                        if ($orderMoney > 0) {
//                            $rep1cParams[$item] -= $orderMoney;
//                            $order = new Order();
//                            $order->fill([
//                                'loan_id' => $loan->id, 'repayment_id' => $rep->id,
//                                'money' => $orderMoney, 'type' => OrderType::getPKOid(),
//                                'passport_id' => $loan->claim->passport_id,
//                                'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
//                                'purpose' => with(Order::getPurposeTypes())[$item]
//                            ]);
//                            $orders[] = $order;
//                        }
//                        $paidMoney -= $orderMoney;
//                    }
//                }
            } else if ($rep->repaymentType->isDopnik()) {
                //если допник, то отправляем в пене - остаток пени
                $rep1cParams['fine'] = $mDet->fine;
            } else {
                $moneyForBigOrders = $paidMoney;
            }
            if ($mDet->money - $paidMoneyStartValue <= 0 && ($rep->repaymentType->isPeace() || $rep->repaymentType->isClaim())) {
                $rep1cParams['od'] = 0;
                $rep1cParams['pc'] = 0;
                $rep1cParams['exp_pc'] = 0;
                $rep1cParams['fine'] = 0;
            }
        }
//        DB::rollback();
//        return false;

        if ($moneyForBigOrders > $paidMoneyStartValue) {
            return redirect()->back()->with('msg_err', 'Платёж не зачислен. Сумма денег на ордеры превысила сумму взноса');
        }
        foreach ($payOrder as $item) {
            if ($moneyForBigOrders <= 0) {
                break;
            }
            $orderMoney = ($moneyForBigOrders - $mDet->{$item} < 0) ? $moneyForBigOrders : $mDet->{$item};
            if ($orderMoney <= 0) {
                continue;
            }
            $order = new Order();
            $order->fill([
                'loan_id' => $loan->id, 'repayment_id' => ($repsNum == 0) ? NULL : $rep->id,
                'money' => $orderMoney, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                'purpose' => with(Order::getPurposeTypes())[$item]
            ]);
            $orders[] = $order;
            if ($repsNum > 0) {
                $rep->{$item} = (int) $rep->{$item} - $orderMoney;
            }
            $moneyForBigOrders -= $orderMoney;
            if (isset($rep1cParams) && !is_null($rep1cParams)) {
                if ($rep->repaymentType->isDopnik()) {
                    $rep1cParams[$item] = $order->money;
                    if ($item == 'od') {
                        $rep1cParams['money'] -= $order->money;
                    }
                } else if ($rep->repaymentType->isClaim()) {
                    $rep1cParams[$item] -= $order->money;
                    $rep1cParams['money'] -= $order->money;
                } else if ($rep->repaymentType->isPeace()) {
                    $rep1cParams[$item] -= $order->money;
                    $rep1cParams['money'] -= $order->money;
                }
            }
        }
        DB::beginTransaction();
        foreach ($orders as $order) {
            if (!$order->saveThrough1c()) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
            }
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'orders', $order);
        }
        $paysList = [];
        foreach ($pays as $pay) {
            $paysList[] = [
                'exp_pc' => $pay->exp_pc, 'fine' => $pay->fine,
                'money' => $pay->total,
                'end_date' => with(new Carbon($pay->end_date))->format('YmdHis'),
                'closed' => $pay->closed
            ];
            if (!$pay->save()) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
            }
//            Spylog::logModelAction(Spylog::ACTION_UPDATE, 'peace_pays', $pay);
        }
        if (isset($rep) && !is_null($rep)) {
            if (!$rep->save()) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
            }
            Spylog::logModelAction(Spylog::ACTION_UPDATE, 'repayments', $rep);
            //обновить заявление, если это не допник с комиссией
            if ($rep->repaymentType->isClaim() && $rep->repaymentType->text_id != config('options.rtype_claim3')) {
                //просроченное заявление нельзя редактировать
                if ($rep->getOverdueDays() == 0) {
                    $rep1cParams['time'] = '';
                    $res1c = MySoap::createClaimRepayment($rep1cParams);
                    \PC::debug($res1c, 'результат');
                    if ($res1c['res'] == 0) {
                        DB::rollback();
                        return redirect()->back()->with('msg_err', StrLib::ERR_1C);
                    }
                }
            } else if ($rep->repaymentType->isPeace()) {
                $rep1cParams['pays'] = json_encode($paysList);

//                if(config('app.dev')){
                $rep1cParams['orders'] = [];
                foreach ($orders as $order) {
                    $rep1cParams['orders'][] = [
                        'number' => $order->number,
                        'money' => $order->money
                    ];
                }
//                }

                $res1c = MySoap::createPeaceRepayment($rep1cParams);
                \PC::debug($res1c, 'результат');
//                if((config('app.dev') && (int)$res1c->result==0)||(!config('app.dev') && $res1c['res']==0)){
//                if ($res1c['res'] == 0) {
                if ((int) $res1c->result == 0) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', StrLib::ERR_1C);
                }
            }
        }
        if (!$loan->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
        }
        Spylog::logModelAction(Spylog::ACTION_UPDATE, 'loans', $loan);
        //Закрытие кредитника если после платежа задолженность равна нулю
        if ($mDet->money - $paidMoneyStartValue <= 0) {
            if (!$loan->close()) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
            }
            Spylog::logModelAction(Spylog::ACTION_UPDATE, 'loans', $loan);
            //ставим флаг - постоянный клиент, если количество закрытых займов равно количеству необходимых закрытых займов
            if (Loan::where('closed', '1')->leftJoin('claims', 'claims.customer_id', '=', DB::raw($loan->claim->customer_id))->count() > config('options.regular_client_loansnum')) {
                if (!$loan->claim->about_client->update(['postclient' => 1])) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
                }
                Spylog::logModelAction(Spylog::ACTION_UPDATE, 'about_clients', $loan->claim->about_client);
            }
            //создаем документ закрытия
            if (isset($rep1cParams)) {
                $closing = $this->createZeroClose($loan);
            }
            if (is_null($closing)) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_NO_PAY);
            }
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'repayments', $closing);
        }
        Spylog::log(Spylog::ACTION_PAYMENT, ($repsNum > 0) ? 'repayments' : 'loans', ($repsNum > 0 && isset($rep)) ? $rep->id : $loan->id, NULL);
        DB::commit();
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }
    /**
     * отправляет закрытие в 1с
     * @param type $rep1cParams параметры закрытия
     * @param boolean $exchange отправить через арм или через эксчендж
     * @param type $promo_discount
     * @return type
     */
    public function sendClosingTo1c($rep1cParams, $exchange = true, $promo_discount = 0) {
        if ($exchange) {
            $rep1cParams['type'] = 'CreateClose';
            if ($promo_discount > 0) {
                $rep1cParams['discount'] = $promo_discount;
            }
            $res1c = MySoap::sendExchangeArm(MySoap::createXML($rep1cParams));
            return ['res' => $res1c->result, 'value' => $res1c->value];
        } else {
            return MySoap::createClosingRepayment($rep1cParams);
        }
    }
    /**
     * Создание приходника по прочим приходам для списанных должников
     * @param integer $paidMoney
     * @param \App\Loan $loan
     * @param \App\Repayment $rep
     * @return type
     */
    public function addPaymentOnSpisan($paidMoney, $loan, $rep=null){
        $order = new Order();
        $order->fill([
            'loan_id' => (is_null($loan))?null:$loan->id, 
            'repayment_id' => (is_null($rep))?null:$rep->id,
            'money' => $paidMoney, 
            'type' => OrderType::getIdByTextId(OrderType::OPKO),
            'passport_id' => (is_null($loan))?null:$loan->claim->passport_id,
            'user_id' => Auth::user()->id, 
            'subdivision_id' => Auth::user()->subdivision_id
        ]);
        if($order->saveThrough1c()){
            return redirect()->back()->with('msg_suc',  StrLib::SUC);
        } else {
            return redirect()->back()->with('msg_err',  StrLib::ERR);
        }
    }
    /**
     * Делает платеж по СУЗу 
     * @param Request $req запрос на сервер
     * @param Repayment $rep суз для которого делается платеж
     * @param string $payOrder порядок списания
     * @param integer $paidMoney оплаченная сумма в копейках
     * @param \App\Utils\ReqMoneyDetails $mDet детализация по задолженности
     * @return type
     */
    public function addSuzPayment(Request $req, Repayment $rep, $payOrder, $paidMoney, $mDet = null) {
        $user = Auth::user();
        if (is_null($mDet)) {
            $mDet = $rep->loan->getRequiredMoneyDetails();
        }

        $orders = [];
        $rep1cParams = [
            'created_at' => with(new Carbon($rep->created_at))->format('YmdHis'),
            'Number' => $rep->id_1c,
            'passport_series' => $rep->loan->claim->passport->series,
            'passport_number' => $rep->loan->claim->passport->number,
            'money' => $rep->od + $rep->pc + $rep->exp_pc + $rep->fine + $rep->tax,
            'loan_id_1c' => $rep->loan->id_1c,
            'subdivision_id_1c' => (is_null($rep->subdivision)) ? "" : $rep->subdivision->name_id,
            'user_id_1c' => (is_null($rep->user)) ? "" : $rep->user->id_1c,
            'od' => (is_null($rep->od)) ? 0 : $rep->od,
            'pc' => (is_null($rep->pc)) ? 0 : $rep->pc,
            'exp_pc' => (is_null($rep->exp_pc)) ? 0 : $rep->exp_pc,
            'fine' => (is_null($rep->fine)) ? 0 : $rep->fine,
            'tax' => (is_null($rep->tax)) ? 0 : $rep->tax,
            'comment' => $rep->comment,
            'paid_money' => $paidMoney,
            'stock_type' => '',
            'stock_created_at' => ''
        ];
        $contracts1c = Synchronizer::getContractsFrom1c(null, null, $rep->loan->id_1c);
        $stockName = '';
        $stockType = '';
        foreach (['akcsuzst46' => 'СузСт46', 'ngbezdolgov' => 'НовыйГодБезДолгов'] as $snk => $snv) {
            if (array_key_exists($snk, $contracts1c)) {
                $stockName = $snk;
                $stockType = $snv;
            }
        }
        if (
                $stockName != '' &&
                is_array($contracts1c) &&
                array_key_exists('repayments', $contracts1c) &&
                array_key_exists('suz', $contracts1c['repayments']) &&
                array_key_exists('stock_type', $contracts1c['repayments']['suz']) &&
                $contracts1c['repayments']['suz']['stock_type'] == '' &&
                array_key_exists($stockName, $contracts1c)
        ) {
            $rep1cParams['stock_type'] = $stockType;
            $rep1cParams['stock_created_at'] = Carbon::now()->format('YmdHis');

            $mDet = Loan::rewriteDebtImportantData($mDet, $contracts1c[$stockName]);
            foreach (['od', 'pc', 'exp_pc', 'fine', 'tax', 'money'] as $purpose) {
                if (array_key_exists($purpose, $contracts1c[$stockName])) {
                    $rep1cParams[$purpose] = $contracts1c[$stockName][$purpose];
                }
            }

            $reconstrSogl = new Repayment();
            $reconstrSogl->created_at = Carbon::now()->subSeconds(10)->format('Y-m-d H:i:s');
            $reconstrSogl->loan_id = $rep->loan_id;
            $reconstrSogl->user_id = Auth::user()->id;
            $reconstrSogl->subdivision_id = Auth::user()->subdivision_id;
            $reconstrSogl->was_pc = $rep->pc;
            $reconstrSogl->was_exp_pc = $rep->exp_pc;
            $reconstrSogl->was_od = $rep->od;
            $reconstrSogl->was_fine = $rep->fine;
            $reconstrSogl->was_tax = $rep->tax;
            $reconstrSogl->id_1c = 'А' . $rep->id_1c;
            $reconstrSogl->pc = $mDet->pc;
            $reconstrSogl->exp_pc = $mDet->exp_pc;
            $reconstrSogl->od = $mDet->od;
            $reconstrSogl->fine = $mDet->fine;
            $reconstrSogl->tax = $mDet->tax;
            $reconstrSogl->paid_money = $paidMoney;
            $reconstrSogl->req_money = $reconstrSogl->pc + $reconstrSogl->exp_pc + $reconstrSogl->od + $reconstrSogl->fine + $reconstrSogl->tax;
            $reconstrSogl->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_suzstock2'))->first())->id;
            $reconstrSogl->time = 1;
            $reconstrSogl->data = json_encode(['stockType' => $stockType, 'stockName' => $stockName]);
            $reconstrSogl->save();
        } else if ($stockName == config('options.suz_arhiv_ub')) {
            
        }
        $repJsonData = $rep->getData();
        if(!is_null($repJsonData) && isset($repJsonData->stock_type) && $repJsonData->stock_type==config('options.suz_arhiv_ub')){
            $payOrder = ['0'=>'exp_pc','1'=>'pc','2'=>'od','3'=>'tax','4'=>'fine'];
        }

        foreach ($payOrder as $item) {
            if ($paidMoney <= 0) {
                break;
            }
            $orderMoney = ($paidMoney - $mDet->{$item} < 0) ? $paidMoney : $mDet->{$item};
            if ($orderMoney <= 0) {
                continue;
            }
            $order = new Order();
            $order->fill([
                'loan_id' => $rep->loan->id, 'repayment_id' => $rep->id,
                'money' => $orderMoney, 'type' => OrderType::getPKOid(),
                'passport_id' => $rep->loan->claim->passport_id,
                'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                'purpose' => with(Order::getPurposeTypes())[$item]
            ]);
            $orders[] = $order;
            $rep->{$item} = (int) $rep->{$item} - $orderMoney;
            $rep1cParams[$item] -= $orderMoney;
            $rep1cParams['money'] -= $orderMoney;
            $paidMoney -= $orderMoney;
        }

        DB::beginTransaction();
        foreach ($orders as $order) {
            if (!$order->saveThrough1c()) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR_1C);
            }
        }
        if (!$rep->save()) {
            DB::rollback();
            return redirect()->back()->with('msg_err', 'Ошибка при сохранении договора.');
        }
        $res1c = MySoap::addSuzPayment($rep1cParams);
        if (!$res1c['res']) {
            DB::rollback();
            return redirect()->back()->with('msg_err', StrLib::ERR_1C);
        }
        DB::commit();

        if ($rep1cParams['paid_money'] == $mDet->money) {
            if (!$this->createZeroClose($rep->loan)) {
                return redirect()->back()->with('msg_err', 'Платеж зачислен, но необходимо создать закрытие вручную!');
            }
        }
        return redirect()->back()->with('msg_suc', 'Платеж зачислен.');
    }

    public function closeSUZ(Request $req, Repayment $rep, $paidMoney, \App\Utils\ReqMoneyDetails $mDet, $stockName) {
        $user = Auth::user();
        $rep1cParams = [
            'created_at' => with(new Carbon($rep->created_at))->format('YmdHis'),
            'Number' => $rep->id_1c,
            'passport_series' => $rep->loan->claim->passport->series,
            'passport_number' => $rep->loan->claim->passport->number,
            'money' => $rep->od + $rep->pc + $rep->exp_pc + $rep->fine + $rep->tax,
            'loan_id_1c' => $rep->loan->id_1c,
            'subdivision_id_1c' => (is_null($rep->subdivision)) ? "" : $rep->subdivision->name_id,
            'user_id_1c' => (is_null($rep->user)) ? "" : $rep->user->id_1c,
            'od' => $rep->od,
            'pc' => $rep->pc,
            'exp_pc' => $rep->exp_pc,
            'fine' => $rep->fine,
            'tax' => $rep->tax,
            'comment' => $rep->comment,
            'paid_money' => 0,
            'stock_type' => $stockName,
            'stock_created_at' => Carbon::now()->format('YmdHis')
        ];

        $res1c = MySoap::addSuzPayment($rep1cParams);
        if (!$res1c['res']) {
            return false;
        }
        return true;
    }

    public function createZeroClose($loan, $created_at=null) {
        $rep1cParams = [
            'created_at' => (is_null($created_at))?Carbon::now()->format('YmdHis'):with(new Carbon($created_at))->format('YmdHis'),
            'Number' => '',
            'passport_series' => $loan->claim->passport->series,
            'passport_number' => $loan->claim->passport->number,
            'money' => 0,
            'loan_id_1c' => $loan->id_1c,
            'subdivision_id_1c' => Auth::user()->subdivision->name_id,
            'user_id_1c' => Auth::user()->id_1c,
            'od' => 0,
            'pc' => 0,
            'exp_pc' => 0,
            'fine' => 0,
            'comment' => '',
            'time' => 0,
            'discount' => 0,
            'loantype_id_1c' => '',
            'uki' => 0,
            'uki_money' => 0
        ];
//        $res1c = MySoap::createClosingRepayment($rep1cParams);
        $res1c = $this->sendClosingTo1c($rep1cParams, true);
        if ($res1c['res'] == 0) {
            return redirect()->back()->with('msg_err', 'Ошибка связи с 1C при создании документа закрытия.');
        } else {
            if (strstr('//', $res1c['value']) !== false) {
                return redirect()->back()->with('msg_err', 'Ошибка! Необходимо проверить номер контрагента и кредитный договор!');
            }
            $this->conductClosing($res1c['value'], $loan->claim->customer->id_1c);
            $repayment = new Repayment();
            $repayment->id_1c = $res1c['value'];
            $repayment->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_closing'))->first())->id;
            $repayment->loan_id = $loan->id;
            $repayment->subdivision_id = Auth::user()->subdivision_id;
            $repayment->user_id = Auth::user()->id;
            return $repayment->save();
        }
    }

    public function addFreePayment($loan, $paidMoney, $mDet, $req) {
        $orders = [];
        $user = Auth::user();
        $orderDate = ($req->has('created_at')) ? with(new Carbon($req->created_at))->format('Y-m-d H:i:s') : (Carbon::now()->format('Y-m-d H:i:s'));
        $orderParams = [
            'loan_id' => $loan->id,
            'type' => OrderType::getPKOid(),
            'passport_id' => $loan->claim->passport_id,
            'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
            'purpose' => Order::P_EXPPC
        ];
        $temp_money = $paidMoney;
        $lastRep = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'desc')->first();
//        if ($paidMoney > ($mDet->pc + $mDet->exp_pc)) {
//            if (is_null($lastRep)) {
//                return redirect()->back()->with('msg_err', 'Сумма превышает сумму процентов. Вносим только сумму процентов и просроченных, а сумму основного долга вносим через кассовые операции');
//            } else {
//                if ($lastRep->repaymentType->isClaim()) {
//                    return redirect()->back()->with('msg_err', 'Сумма превышает сумму процентов. Необходимо создать соглашение об урегулировании задолженности');
//                } else if ($lastRep->repaymentType->isDopnik()) {
//                    return redirect()->back()->with('msg_err', 'Сумма превышает сумму процентов. Необходимо создать дополнительное соглашение');
//                }
//            }
//        }
        $payOrder = ['exp_pc', 'pc', 'od', 'fine'];
        foreach ($payOrder as $item) {
            if ($temp_money <= 0) {
                break;
            }
            $orderMoney = ($temp_money - $mDet->{$item} < 0) ? $temp_money : $mDet->{$item};
            if ($orderMoney <= 0) {
                continue;
            }
            $order = new Order();
            $order->fill([
                'loan_id' => $loan->id,
                'money' => $orderMoney, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                'purpose' => with(Order::getPurposeTypes())[$item],
                'created_at' => $orderDate
            ]);
            if (isset($lastRep) && !is_null($lastRep) && $lastRep->repaymentType->isClaim()) {
                $order->repayment_id = $lastRep->id;
            }
            $orders[] = $order;
            $temp_money -= $orderMoney;
        }
        DB::beginTransaction();
        foreach ($orders as $item) {
            $created_at = null;
            if ($req->has('created_at')) {
                $created_at = $req->created_at;
            }
            if (!$item->saveThrough1cWithRegister($created_at)) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR);
            }
        }
        DB::commit();
        return redirect()->back()->with('msg_suc', StrLib::SUC_SAVED);
    }

    public function addPeacePayment($loan, $paidMoney, $mDet, $req) {
        $repsNum = Repayment::where('loan_id', $loan->id)->count();
        $lastRep = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'desc')->first();
        $orders = [];
        //сначала зачисляем за проценты по платежам
        $pays = $mDet->peace_pays;
        //считаем по платежам
        $paysNum = count($pays);
        $sled = 0;
        $per = 1;
        $pred = 1;
        $last_payday = Carbon::now()->format('Y-m-d');
        $moneyForBigOrders = 0;
        $user = Auth::user();
        $paid_money = $paidMoney;

        foreach ($pays as $pay) {
            foreach (['exp_pc', 'fine'] as $item) {
                if ($paidMoney > 0) {
                    $orderMoney = ($paidMoney - $pay->{$item} < 0) ? $paidMoney : $pay->{$item};
                    if ($orderMoney > 0) {
                        $pay->{$item} -= $orderMoney;
                        $pay->last_payday = Carbon::now()->format('Y-m-d');
                        $order = new Order();
                        $order->fill([
                            'loan_id' => $loan->id, 'repayment_id' => $lastRep->id,
                            'money' => $orderMoney, 'type' => OrderType::getPKOid(),
                            'passport_id' => $loan->claim->passport_id,
                            'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                            'purpose' => with(Order::getPurposeTypes())[$item],
                            'peace_pay_id' => $pay->id,
                        ]);
                        if ($item == 'exp_pc') {
                            $order->comment = 'peace_exp_pc';
                        }
                        $mDet->{$item} -= $orderMoney;
                        $orders[] = $order;
                    }
                    $paidMoney -= $orderMoney;
                }
            }
            //для того чтобы сначала пройти все просроченные платежи по порядку
            if (with(new Carbon($pay->end_date))->lt(Carbon::now())) {
                $per = 1;
                $sled = 0;
            }
            if ($sled == 1) {
                $lastPay = $pays[$paysNum - $pred];
                if ($paidMoney > 0) {
                    if ($lastPay->total > $paidMoney) {
                        $pay->last_payday = $last_payday;
                        $lastPay->total -= $paidMoney;
                        $moneyForBigOrders += $paidMoney;
                        $paidMoney = 0;
                        $sled = 0;
                        break;
                    } else {
                        $pay->last_payday = $last_payday;
                        $paidMoney -= $lastPay->total;
                        $moneyForBigOrders += $lastPay->total;
                        $lastPay->total = 0;
                        $lastPay->closed = 1;
                        $pred = $pred + 1;
                        $sled = 1;
                    }
                }
            }
            if ($pay->total > 0 && $per == 1) {
                if ($paidMoney > 0) {
                    if ($pay->total > $paidMoney) {
                        $pay->last_payday = $last_payday;
                        $pay->total -= $paidMoney;
                        $moneyForBigOrders += $paidMoney;
                        $paidMoney = 0;
                        break;
                    } else {
                        $pay->last_payday = $last_payday;
                        $paidMoney -= $pay->total;
                        $moneyForBigOrders += $pay->total;
                        $pay->total = 0;
                        $pay->closed = 1;
                        $sled = 1;
                    }
                }
                $per = 0;
            }
        }
        $payOrder = ['exp_pc', 'pc', 'od', 'fine'];
        foreach ($payOrder as $item) {
            if ($moneyForBigOrders <= 0) {
                break;
            }
            $orderMoney = ($moneyForBigOrders - $mDet->{$item} < 0) ? $moneyForBigOrders : $mDet->{$item};
            if ($orderMoney <= 0) {
                continue;
            }
            $order = new Order();
            $order->fill([
                'loan_id' => $loan->id, 'repayment_id' => ($repsNum == 0) ? NULL : $lastRep->id,
                'money' => $orderMoney, 'type' => OrderType::getPKOid(),
                'passport_id' => $loan->claim->passport_id,
                'user_id' => $user->id, 'subdivision_id' => $user->subdivision_id,
                'purpose' => with(Order::getPurposeTypes())[$item]
            ]);
            $orders[] = $order;
            if ($repsNum > 0) {
                $lastRep->{$item} = (int) $lastRep->{$item} - $orderMoney;
            }
            $moneyForBigOrders -= $orderMoney;
        }
        DB::beginTransaction();
        if($lastRep->created_at->copy()->setTime(0,0,0) == Carbon::today()){
            $created_at = $lastRep->created_at->copy()->addSecond()->format('Y-m-d H:i:s');
        } else {
            $created_at = Carbon::now()->format('Y-m-d H:i:s');
        }
        foreach ($orders as $item) {
            if ($req->has('created_at')) {
                $created_at = $req->created_at;
            }
            if (!$item->saveThrough1cWithRegister($created_at)) {
                DB::rollback();
                return redirect()->back()->with('msg_err', StrLib::ERR);
            }
        }
        DB::commit();

        if ($paid_money == $mDet->money) {
            
            if (!$this->createZeroClose($loan, with(new Carbon($created_at))->addSecond())) {
                return redirect()->back()->with('msg_err', 'Платеж зачислен, но необходимо создать закрытие с нулем вручную!');
            }
        }
        return redirect()->back()->with('msg_suc', StrLib::SUC_SAVED);
    }

    public function createOnDate(Request $req) {
//        if (!Auth::user()->isAdmin()) {
//            return redirect()->back()->with('msg_err', StrLib::ERR_NOT_ADMIN);
//        }
        $loan = Loan::find($req->get('loan_id'));
        if (is_null($loan)) {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        $mDet = $loan->getDebtFrom1c($loan, $req->create_date);
        $repayment = new Repayment();
        $repayment->tax = 0;
        $repayment->fill($req->all());
//        if ($repayment->time > config('options.max_dopnik_time')) {
//            return redirect()->back()->with('msg_err', 'Срок не может быть больше ' . config('options.max_dopnik_time') . ' дней');
//        }
        if ($mDet->pc == 0) {
            $repayment->discount = 0;
        }
        $repayment->paid_money = StrUtils::parseMoney($req->get('paid_money'));
        $repayment->req_money = $mDet->money;
        $prevRepayment = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'desc')->first();
        $repayment->fine = (is_null($prevRepayment)) ? $loan->fine : $prevRepayment->fine;
        $orders = [];
        $peacePays = [];
        if ($repayment->repaymentType->text_id == config('options.rtype_claim2')) {
            if ((!is_null($prevRepayment) && $prevRepayment->getOverdueDays() < 21) || $loan->getOverdueDays() < 21) {
                return redirect()->back()->with('msg_err', 'Соглашение об исполнении может быть создано только после 21 дня просрочки');
            }
        }

        $discountedMoney = round($mDet->pc * ($repayment->discount / 100));
        if ($discountedMoney > $repayment->paid_money) {
            return redirect()->back()->with('msg', 'Ошибка! Сумма взноса меньше суммы скидки. Проверьте вводимую сумму')->with('class', 'alert-danger');
        }
        if ($repayment->repaymentType->isClosing() && Repayment::where('loan_id', $loan->id)->where('repayment_type_id', $repayment->repayment_type_id)->count() > 0) {
            return redirect()->back()->with('msg', 'Ошибка! На договоре уже есть закрытие')->with('class', 'alert-danger');
        }
        //промокод применяется к кредитникам если делается закрытие и промокод 
        //доступен и процент вида займа не меньше двух и у кредитника нет 
        //доп документов и кредитник не просрочен
        //либо админ может поставить галочку применить промокод в любом случае и тогда он применится
        if ($repayment->repaymentType->isClosing() && $loan->canCloseWithPromocode($mDet)) {
            $discountedMoney = ($mDet->pc - $discountedMoney - config('options.promocode_discount') < 0) ? $mDet->pc : $discountedMoney + config('options.promocode_discount');
        } else if ($req->has('promocode_anyway')) {
            $discountedMoney = ($mDet->pc - $discountedMoney - config('options.promocode_discount') < 0) ? $mDet->pc : $discountedMoney + config('options.promocode_discount');
        }
        if ($repayment->paid_money + $discountedMoney > $mDet->money) {
            Log::error('RepaymentController.create1', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment]);
            return redirect()->back()->with('msg', 'Ошибка! Сумма договора не может быть больше необходимой суммы.')->with('class', 'alert-danger');
        } else if ((int) $repayment->paid_money + (int) $discountedMoney != (int) $mDet->money && $repayment->repaymentType->isClosing()) {
            Log::error('RepaymentController.create2', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment]);
            return redirect()->back()->with('msg', 'Ошибка! Для закрытия договора необходимо: ' . ($mDet->money / 100 - $discountedMoney) . ' руб.')->with('class', 'alert-danger');
        }
        $minReqMoney = ($repayment->repaymentType->mandatory_percents) ? ($mDet->pc + $mDet->exp_pc - $discountedMoney) : 0;
        if ($repayment->repaymentType->mandatory_percents && (intval($repayment->paid_money) - intval($minReqMoney)) < 0) {
            Log::error('RepaymentController.create3', ['paid_money' => $repayment->paid_money, 'req_money' => $mDet->money, 'discountedmoney' => $discountedMoney, 'repayment' => $repayment, 'minreqmoney' => $minReqMoney]);
            return redirect()->back()->with('msg', 'Ошибка! Сумма договора не может быть меньше ' . ($minReqMoney / 100) . ' руб.')->with('class', 'alert-danger');
        }
        //отнимаем от необходимых процентов сумму скидки
        $mDet->pc -= $discountedMoney;
        if (is_null(Auth::user())) {
            return redirect()->back()->with('msg', 'Ошибка аутентификации')->with('class', 'alert-danger');
        }
        if ($req->has('user_id') && $req->has('subdivision_id')) {
            $user_id = $req->user_id;
            $subdiv_id = $req->subdivision_id;
        } else {
            $user_id = Auth::user()->id;
            $subdiv_id = Auth::user()->subdivision_id;
        }
        $repayment->user_id = $user_id;
        $repayment->subdivision_id = $subdiv_id;
        $rep1cParams = [
            'created_at' => with(new Carbon($req->create_date))->format('YmdHis'),
            'Number' => '',
            'passport_series' => $loan->claim->passport->series,
            'passport_number' => $loan->claim->passport->number,
            'money' => $mDet->od,
            'loan_id_1c' => $loan->id_1c,
            'subdivision_id_1c' => $repayment->subdivision->name_id,
            'user_id_1c' => $repayment->user->id_1c,
            'od' => 0,
            'pc' => 0,
            'exp_pc' => 0,
            'fine' => 0,
            'comment' => $repayment->comment,
            'time' => 0,
            'discount' => $repayment->discount,
            'loantype_id_1c' => ''
        ];
        if ($repayment->repaymentType->isDopnik()) {
            $rep1cParams['loantype_id_1c'] = 'ARM000016';
        }
        $rep1cParams['fine'] = $mDet->fine;

        $payOrder = $repayment->repaymentType->getPaymentsOrder();
        $mTemp = $repayment->paid_money;

        foreach ($payOrder as $item) {
            $repayment->{'was_' . $item} = $mDet->{$item};
            if ($mTemp <= 0) {
                continue;
            }
            $orderM = round(($mTemp - $mDet->{$item} < 0) ? $mTemp : $mDet->{$item});
//                if ($item == 'pc' && $discountedMoney > 0) {
//                    $orderM -= $discountedMoney;
//                }
            if ($orderM > 0) {
                $order = new Order();
                $order->fill([
                    'loan_id' => $loan->id,
                    'money' => $orderM, 'type' => OrderType::getPKOid(),
                    'passport_id' => $loan->claim->passport_id,
                    'user_id' => $user_id, 'subdivision_id' => $subdiv_id,
                    'purpose' => with(Order::getPurposeTypes())[$item]
                ]);
                $orders[] = $order;
                $repayment->{$item} = $orderM;
                if ($item == 'fine') {
                    $repayment->fine = $mDet->fine - $orderM;
                }
                if ($repayment->repaymentType->isDopnik()) {
                    $rep1cParams[$item] = $order->money;
                    if ($item == 'od') {
                        $rep1cParams['money'] -= $order->money;
                    }
                }
            }
            $mTemp -= $orderM;
        }
        $rep1cParams['time'] = $repayment->time;
        DB::beginTransaction();
        try {
            /**
             * ОТПРАВЛЯЕМ ВСЕ ДОГОВОРА\ОРДЕРЫ\ГРАФИК ПЛАТЕЖЕЙ В 1С
             */
            //если какой то из этих параметров по какой то причине стал нулем то сделать его равным 0
            foreach (['pc', 'exp_pc', 'od', 'fine', 'tax'] as $i) {
                if (is_null($i) || $i == 'null') {
                    $i = 0;
                }
            }
            if ($repayment->repaymentType->isDopnik()) {
                //отправляем ДОПНИК в 1С
                if ($rep1cParams['time'] > config('options.max_dopnik_time')) {
                    $rep1cParams['time'] = config('options.max_dopnik_time');
                    $repayment->time = config('options.max_dopnik_time');
                }
                $res1c = MySoap::createRepayment($rep1cParams);
                if ($res1c['res'] == 0) {
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка связи с 1с. ');
                } else {
                    $repayment->id_1c = $res1c['value'];
                }
//                $this->addSaving($repayment,$orders);
            }
            //сохраняем договор в базу
            $repayment->fine = $mDet->fine;
            if (Repayment::where('id_1c', $repayment->id_1c)->where('repayment_type_id', $repayment->repayment_type_id)->count() > 0) {
                return redirect()->back()->with('msg_err', 'Задвоение договора. Обратитесь в поддержку.');
            }
            if (!$repayment->save()) {
                \PC::debug($repayment, 'repayment');
                DB::rollback();
                return redirect()->back()->with('msg_err', 'Ошибка на сохранении договора! Договор не был сохранён.');
            }
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'repayments', $repayment);

            //сохраняем все ордеры в базу попутно отправляя их в 1С
            foreach ($orders as $order) {
                $order->repayment_id = $repayment->id;
                if (!$order->saveThrough1c($repayment->created_at->subSecond()->format('Y-m-d H:i:s'))) {
                    \PC::debug($order, 'order');
                    DB::rollback();
                    return redirect()->back()->with('msg_err', 'Ошибка на сохранении ордера! Договор не был сохранён.');
                }
                Spylog::logModelAction(Spylog::ACTION_CREATE, 'orders', $order);
            }

            DB::commit();
            return redirect()->back()->with('msg_suc', 'Договор успешно сохранён.');
        } catch (Exception $exc) {
            DB::rollback();
            return redirect()->back()->with('msg_err', 'Ошибка! Договор не был сохранён.');
        }
    }

    /**
     * Правим суз и добавляем к нему график (в случае если график заполнен)
     * @param Request $req
     * @return type
     */
    public function addSuzSchedule(Request $req) {
        if (!$req->has('suzschedule') || !$req->has('loan_id')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $rep = Repayment::leftJoin('repayment_types', 'repayment_types.id', '=', 'repayments.repayment_type_id')
                ->where('loan_id', $req->get('loan_id'))
                ->whereIn('repayment_types.text_id', [config('options.rtype_suz1'), config('options.rtype_suz2')])
                ->orderBy('repayments.created_at', 'desc')
                ->first();

        if (is_null($rep)) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NULL);
        }
        $repData = $rep->getData();
        $reqMoney = $rep->req_money;
        $contracts1c = Synchronizer::getContractsFrom1c(null, null, $rep->loan->id_1c);
        if (!is_null($contracts1c) && array_key_exists('stockData', $contracts1c)) {
            $mData = $contracts1c['stockData']['importantMoneyData'];
            $reqMoney = ($mData['od'] + $mData['exp_pc'] + $mData['pc'] + $mData['fine'] + $mData['tax']) / 100;
        }
        $xml = ['type' => 'AddSuzSchedule', 'repayment_id_1c' => $rep->id_1c, 'pays' => []];
        if ($req->has('change_suz')) {
            $xml['od'] = StrUtils::rubToKop($req->get('od', 0));
            $xml['pc'] = StrUtils::rubToKop($req->get('pc', 0));
            $xml['exp_pc'] = StrUtils::rubToKop($req->get('exp_pc', 0));
            $xml['fine'] = StrUtils::rubToKop($req->get('fine', 0));
            $xml['tax'] = StrUtils::rubToKop($req->get('tax', 0));
            $xml['stock_type'] = config('options.suz_arhiv_ub');
            $xml['stock_created_at'] = Carbon::now()->format('YmdHis');

            $reqMoney = 0;
            foreach (['od', 'pc', 'exp_pc', 'fine', 'tax'] as $p) {
                $reqMoney += $xml[$p];
            }
            $xml['money'] = $reqMoney;
        }

        $totalMoney = 0;
        $data = json_decode($req->suzschedule);
        $scheduleRowsNum = count($data);

        if ($scheduleRowsNum > 0) {
            foreach ($data as $p) {
                if ($scheduleRowsNum == 1 && ($p->total == '' || $p->total == 0)) {
                    break;
                }
                $xml['pays'][] = [
                    'date' => with(new Carbon($p->date))->format('Ymd'),
                    'total' => $p->total
                ];
                $totalMoney += StrUtils::rubToKop($p->total);
            }
            if ($totalMoney != $reqMoney && count($xml['pays']) > 0) {
                return redirect()->back()->with('msg_err', 'Общая сумма по графику (' . StrUtils::kopToRub($totalMoney) . ' руб.) не соответствует необходимой - ' . StrUtils::kopToRub($reqMoney) . ' руб.');
            }
        }

        $res1c = MySoap::sendExchangeArm(MySoap::createXML($xml));
        if (!isset($res1c->result) || !$res1c->result) {
            return redirect()->back()->with('msg_err', StrLib::ERR_1C . ((isset($res1c->msg_err)) ? $res1c->msg_err : ''));
        }
        return redirect()->back()->with('msg_suc', StrLib::SUC);
    }

    public function calculateDopCommissionMoney(Request $req) {
        if (!$req->has('loan_id') || !$req->has('time')) {
            return ['result' => 0, 'msg' => StrLib::ERR_NO_PARAMS];
        }
        $loan = Loan::find($req->get('loan_id'));
        if (is_null($loan)) {
            return['result' => 0, 'msg' => StrLib::ERR_NULL];
        }
        return array_merge(['result' => 1], json_decode(json_encode($loan->calculateDopCommissionMoney($req->get('time'), $req->get('od'))), true));
    }

}
