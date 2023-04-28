<?php

namespace App;

use App\Spylog\Spylog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;
use App\Http\Controllers\ClaimController;
use Session;
use Auth;

/**
 * Синхронизирует данные с 1С
 *
 */
class Synchronizer {

    static function getContractsFrom1c($series = null, $number = null, $loan_id_1c = null) {
        if (!is_null($series) && !is_null($number)) {
            $result = MySoap::getLoanRepayments($series, $number);
        } else if (!is_null($loan_id_1c)) {
            $result = MySoap::getLoanRepaymentsByNumber($loan_id_1c);
        } else {
            return null;
        }
        if (!(bool) $result['res']) {
            return null;
        }
        $result = Synchronizer::addStocksParams($result, $result);
        return $result;
    }

    /**
     * добавляет акционные параметры к результату как отдельный массив так и в массив с ключом stockData
     * @param array $result
     * @param array $contracts1c
     * @return array
     */
    static function addStocksParams($result, $contracts1c) {
        $stockName = '';
        $stockType = '';
        $importantMoneyData = null;
        $akcsuzst46 = false;
        foreach (['akcsuzst46' => 'СузСт46', 'ngbezdolgov' => 'НовыйГодБезДолгов'] as $stock => $snv) {
            if (array_key_exists($stock, $contracts1c)) {
                $result[$stock] = $contracts1c[$stock];
                $result[$stock]['total'] = 0;
                foreach ($contracts1c[$stock] as $k => $v) {
                    $result[$stock][$k] = $v * 100;
                    $result[$stock]['total'] += $result[$stock][$k];
                }

                $stockName = $stock;
                $stockType = $snv;

                if (
                        $stockName != '' &&
                        is_array($contracts1c) &&
                        array_key_exists('repayments', $contracts1c) &&
                        array_key_exists('suz', $contracts1c['repayments']) &&
                        array_key_exists('stock_type', $contracts1c['repayments']['suz']) &&
                        $contracts1c['repayments']['suz']['stock_type'] == '' &&
                        array_key_exists($stockName, $contracts1c)
                ) {
                    $importantMoneyData = $result[$stock];
                    if ($stockName == 'akcsuzst46') {
                        $akcsuzst46 = true;
                    }
                }
                $result['stockData'] = [
                    'importantMoneyData' => $importantMoneyData,
                    'stockName' => $stockName,
                    'stockType' => $stockType,
                    'akcsuzst46' => $akcsuzst46
                ];
            }
        }
        return $result;
    }

    static function updateLoanRepayments($series = null, $number = null, $loan_id_1c = null, $customer_id_1c = null) {
        $result = [];
        if (strlen($series) > 4) {
            return null;
        }
        DB::beginTransaction();
        if (!is_null($series) && !is_null($number)) {
            $contracts1c = MySoap::getLoanRepayments($series, $number);
        } else if (!is_null($loan_id_1c)) {
            if (!is_null($customer_id_1c)) {
                $contracts1c = MySoap::getLoanRepaymentsByNumber2($loan_id_1c, $customer_id_1c);
            } else {
                $contracts1c = MySoap::getLoanRepaymentsByNumber($loan_id_1c);
            }
        } else {
            return null;
        }
        if (!(bool) $contracts1c['res']) {
//            Session::flash('error_1c', json_encode(['name' => 'Synchronizer.updateLoanRepayments', 'params' => ['series'=>$series,'number'=>$number,'loan_id_1c'=>$loan_id_1c,'customer_id_1c'=>$customer_id_1c], 'response' => $contracts1c]));
//            session('error_1c', json_encode(['name' => 'Synchronizer.updateLoanRepayments', 'params' => ['series'=>$series,'number'=>$number,'loan_id_1c'=>$loan_id_1c,'customer_id_1c'=>$customer_id_1c], 'response' => $contracts1c]));
            return null;
        }
        $result['contracts1c'] = $contracts1c;

        if (!is_null($loan_id_1c) && array_key_exists('loan', $contracts1c)) {
            $loan = Loan::where('id_1c', $contracts1c['loan']['loan_id_1c'])->first();
            if (!is_null($loan) && !is_null($loan->claim)) {
                $claim = $loan->claim;
            }
        } else {
            /**
             * если старый клиент на которого не приходит ни заявка ни кредитник
             */
            if (!array_key_exists('claim', $contracts1c)) {
                $passport = Passport::where('series', $series)->where('number', $number)->first();
                if (is_null($passport)) {
                    $customerData = Synchronizer::addCustomer($series, $number, FALSE);
                    if (!is_null($customerData)) {
                        $customer = $customerData['customer'];
                        $passport = $customerData['passport'];
                        $about_client = $customerData['about_client'];
                    } else {
                        DB::rollback();
                        return null;
                    }
                    $emptyDocs = Synchronizer::addEmptyClaimLoan($passport, $customer, $about_client);
                    if (!is_null($emptyDocs)) {
                        $claim = $emptyDocs['claim'];
                        $loan = $emptyDocs['loan'];
                    } else {
                        DB::rollback();
                        return null;
                    }
                } else {
                    $customer = $passport->customer;
                    $about_client = about_client::where('customer_id', $customer->id)->first();
                    if (!is_null($about_client)) {
                        $about_client = $about_client->replicate();
                    } else {
                        $about_client = new about_client();
                        $about_client->customer_id = $customer->id;
                    }
                    if (!$about_client->save()) {
                        DB::rollback();
                        return null;
                    }
                    $claim = Claim::where('customer_id', $customer->id)->first();
                    if (is_null($claim)) {
                        $emptyDocs = Synchronizer::addEmptyClaimLoan($passport, $customer, $about_client);
                        if (!is_null($emptyDocs)) {
                            $claim = $emptyDocs['claim'];
                            $loan = $emptyDocs['loan'];
                        } else {
                            DB::rollback();
                            return null;
                        }
                    } else {
                        $loan = Loan::where('claim_id', $claim->id)->first();
                    }
                }
            } else {
                $claim = Claim::where('id_1c', $contracts1c['claim']['Number'])->first();
            }
        }
        /**
         * создаем новую заявку с пришедшими данными, если такой заявке нет в базе
         */
        if (!isset($claim) || is_null($claim)) {
            if (is_null($series) || is_null($number)) {
                DB::rollback();
                return null;
            }
            $claim = Synchronizer::updateClaim($contracts1c, $series, $number);
            if (is_null($claim)) {
                DB::rollback();
                return null;
            }
        } else {
            $passport = $claim->passport;
            $about_client = $claim->about_client;
            $customer = $passport->customer;
            if (array_key_exists('claim', $contracts1c)) {
                if (array_key_exists('claim_status', $contracts1c['claim'])) {
                    if (!is_numeric($contracts1c['claim']['claim_status'])) {
                        $claim->status = Claim::STATUS_ONCHECK;
                    } else {
                        $claim->status = $contracts1c['claim']['claim_status'];
                    }
                }
                if (array_key_exists('uki', $contracts1c['claim'])) {
                    $claim->uki = $contracts1c['claim']['uki'];
                }
                if (array_key_exists('money', $contracts1c['claim'])) {
                    $claim->summa = $contracts1c['claim']['money'];
                }
                if (array_key_exists('percent', $contracts1c['claim'])) {
                    $claim->special_percent = $contracts1c['claim']['percent'];
                }
            }
            $claim->updated_at = Carbon::now()->format('Y-m-d H:i:s');
            $claim->save();
        }
        $result['claim'] = $claim;
        if (array_key_exists("loan", $contracts1c)) {
            $loan = Synchronizer::updateLoan($contracts1c, $claim, (isset($passport)) ? $passport : null);
            if (is_null($loan)) {
                DB::rollback();
                return null;
            }
            $result['loan'] = $loan;
        }

        $repsToSave = [];
        if (array_key_exists('loan', $contracts1c) && array_key_exists('repayments', $contracts1c)) {
            $repsToSave = Synchronizer::updateRepayments($contracts1c['repayments'], $loan, $contracts1c);
            if (is_null($repsToSave)) {
                DB::rollback();
                return null;
            }
            /**
             * проходимся по всем договорам и удаляем те которые не пришли из 1с, но есть в базе
             */
            Synchronizer::removeUnreceivedRepayments($contracts1c, $loan);
            $result['repayments'] = $repsToSave;
        } else {
            if (isset($loan) && !is_null($loan)) {
                $repsToSave = Synchronizer::updateRepayments($contracts1c, $loan, $contracts1c);
                if (is_null($repsToSave)) {
                    DB::rollback();
                    return null;
                }
                $result['repayments'] = $repsToSave;
            }
        }
        DB::commit();
        if (!is_null($loan_id_1c)) {
            $repsNum = count($repsToSave);
            if (!isset($loan) || is_null($loan)) {
                $loan = Loan::where('id_1c', $loan_id_1c)->first();
            }
//            Synchronizer::updateOrders($loan->created_at, $claim->passport->series, $claim->passport->number, null, $loan, Carbon::now());
            if ($repsNum > 0) {
                $lastrep = $repsToSave[$repsNum - 1];
                Synchronizer::updateOrders($lastrep->created_at, $claim->passport->series, $claim->passport->number, null, $loan);
            } else {
                Synchronizer::updateOrders($loan->created_at, $claim->passport->series, $claim->passport->number, null, $loan);
            }
        }
        if (array_key_exists('debtor', $contracts1c)) {
            $result['debtor'] = $contracts1c['debtor'];
        }
        $result['stock_params'] = Synchronizer::addStocksParams($result, $contracts1c);
        return $result;
    }

    static function addEmptyClaimLoan($passport, $customer, $about_client) {
        $emptySubdiv = Subdivision::where('name_id', '000')->first();
        if (is_null($emptySubdiv)) {
            DB::rollback();
            return null;
        }
        $claim = new Claim();
        $claim->user_id = Spylog::USERS_ID_1C;
        $claim->subdivision_id = $emptySubdiv->id;
        $claim->customer_id = $customer->id;
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about_client->id;
        $claim->created_at = '2014-01-01 00:00:00';
        $claim->comment = 'Пустая заявка, созданная автоматически для корректного отображение имеющихся документов';
        if (!$claim->save()) {
            Log::error('Ошибка на сохранении заявки(Synchronizer.updateLoanRepayments)', ['claim' => $claim]);
            return null;
        }
        $loan = new Loan();
        $loantype = LoanType::where('id_1c', '000000003')->first();
        if (!is_null($loantype)) {
            $loan->loantype_id = $loantype->id;
        }
        $loan->enrolled = 1;
        $loan->in_cash = 1;
        $loan->money = 1000;
        $loan->time = 15;
        $loan->user_id = Spylog::USERS_ID_1C;
        $loan->claim_id = $claim->id;
        $loan->subdivision_id = $emptySubdiv->id;
        $loan->created_at = '2014-01-01 00:00:00';
        if (!$loan->save()) {
            Log::error('Ошибка на сохранении кредитника(Synchronizer.updateLoanRepayments)', ['loan' => $loan]);
            return null;
        }
        return ['loan' => $loan, 'claim' => $claim];
    }

    /**
     * обновляет данные заявки из 1с, включая about_client, passport,customer, claim
     * @param \App\Claim $claim
     * @return boolean
     */
    static function updateClaimFrom1c($claim) {
        $data = MySoap::passport(['series' => $claim->passport->series, 'number' => $claim->passport->number, 'claim_id' => $claim->id_1c]);
        if (!array_key_exists('result', $data) || !$data['result']) {
            return false;
        }
        return $claim->updateByData($data);
    }

    static function updateClaim($contracts1c, $series, $number) {
        $passport = Passport::where('series', $series)->where('number', $number)->first();
        if (!is_null($passport)) {
            $customer = $passport->customer;
            $about_client = about_client::where('customer_id', $customer->id)->orderBy('created_at', 'desc')->first();
        } else {
            $customerData = Synchronizer::addCustomer($series, $number);
            if (!is_null($customerData)) {
                $customer = $customerData['customer'];
                $passport = $customerData['passport'];
                $about_client = $customerData['about_client'];
            } else {
                DB::rollback();
                return null;
            }
        }
        if (is_null($about_client)) {
            $about_client = new about_client();
            $about_client->customer_id = $customer->id;
            $about_client->save();
        }

        $claim = Claim::where('id_1c', $contracts1c['claim']['Number'])->withTrashed()->first();
        if (is_null($claim)) {
            $claim = new Claim();
        }
        if (!is_null($claim->deleted_at)) {
            $claim->restore();
        }
        $claim->fill($contracts1c['claim']);
        $claimSubdivision = Subdivision::where('name_id', $contracts1c['claim']['subdivision_id_1c'])->first();
        if (is_null($claimSubdivision)) {
            $claimSubdivision = new Subdivision();
            $claimSubdivision->name_id = $contracts1c['claim']['subdivision_id_1c'];
            $claimSubdivision->save();
        }
        $claimUser = User::where('id_1c', 'like', StrUtils::stripWhitespaces($contracts1c['claim']['user_id_1c']) . '%')->first();
        $claim->summa = $contracts1c['claim']['money'];
        $claim->srok = $contracts1c['claim']['time'];
        $claim->passport_id = $passport->id;
        $claim->about_client_id = $about_client->id;
        $claim->customer_id = $customer->id;
        $claim->date = date("Y-m-d H:i:s");
        $claim->user_id = (is_null($claimUser)) ? Spylog::USERS_ID_1C : $claimUser->id;
        $claim->subdivision_id = $claimSubdivision->id;
        $claim->id_1c = $contracts1c['claim']['Number'];
        $claim->uki = $contracts1c['claim']['uki'];
        if (array_key_exists('claim_status', $contracts1c['claim'])) {
            if (!is_numeric($contracts1c['claim']['claim_status'])) {
                $claim->status = Claim::STATUS_ONCHECK;
            } else {
                $claim->status = $contracts1c['claim']['claim_status'];
            }
        } else {
            $claim->status = Claim::STATUS_ONCHECK;
//            $claim->status = Claim::STATUS_ACCEPTED;
        }
        if (array_key_exists('percent', $contracts1c['claim'])) {
            $claim->special_percent = $contracts1c['claim']['percent'];
        }
        $claim->created_at = with(new Carbon($contracts1c['claim']['created_at']))->format('Y-m-d H:i:s');
        if (isset($contracts1c['claim']['promocode_number'])) {
            $promocode = Promocode::where('number', $contracts1c['claim']['promocode_number'])->first();
            if (is_null($promocode)) {
                $promocode = new Promocode();
                $promocode->number = $contracts1c['claim']['promocode_number'];
                $promocode->save();
            }
            $claim->promocode_id = $promocode->id;
        }
        if (!$claim->save()) {
            Log::error('Ошибка на сохранении заявки(Synchronizer.updateClaim)', $claim->toArray());
            return null;
        }
        if (!is_null(Auth::user()) && Auth::user()->isAdmin()) {
            $claim->updateFrom1c();
        }

        Spylog::logModelAction(Spylog::ACTION_CREATE, 'claims', $claim);
        return $claim;
    }

    static function updateLoan($contracts1c, $claim, $passport = null) {
        if (array_key_exists('last_payday', $contracts1c)) {
            $lastPayday = new Carbon($contracts1c['last_payday']);
        } else {
            $lastPayday = null;
        }
        $loan = Loan::where('id_1c', $contracts1c['loan']['loan_id_1c'])->first();

        if (is_null($loan)) {
            if (Loan::where('claim_id', $claim->id)->count() > 0) {
                DB::rollback();
                Log::error('Ошибка! уже существует займ на эту заявку(Synchronizer.updateLoan)', ['claim_id' => $claim->id]);
                return null;
            }
            $loan = new Loan();
        }
        $contracts1c['loan']['created_at'] = with(new Carbon($contracts1c['loan']['created_at']))->format('Y-m-d H:i:s');
        $loan->fill($contracts1c['loan']);
        //ВРЕМЕННЫЙ КОСТЫЛЬ: если приходит код другого короткосрочного вида займа, то ставить тот который есть в базе
        $loantype_id_1c = preg_replace('/\s+/', '', $contracts1c['loan']['loantype_id_1c']);
        if ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
            if ($loantype_id_1c == '0000029' || $loantype_id_1c == '0000021' || $loantype_id_1c == '0000028') {
                $loantype = LoanType::where('id_1c', $contracts1c['loan']['loantype_id_1c'])
                        ->where('start_date', '>=', config('options.new_rules_day'))
                        ->where('status', Loan::STATUS_OPENED)
                        ->first();
            } else if ($contracts1c['loan']['loantype_id_1c'] == '0000022') {
                $contracts1c['loan']['loantype_id_1c'] = 'ARM000010';
            } else if ($contracts1c['loan']['loantype_id_1c'] == '000000032') {
                $contracts1c['loan']['loantype_id_1c'] = $contracts1c['loan']['loantype_id_1c'];
            } else if (strpos($contracts1c['loan']['loantype_id_1c'], 'ARM') === FALSE) {
                $contracts1c['loan']['loantype_id_1c'] = 'ARM000004';
            }
        } else if (in_array($loantype_id_1c, ['000000001', '000000002', '000000013'])) {
            $contracts1c['loan']['loantype_id_1c'] = '000000003';
        }
        if (!isset($loantype) || is_null($loantype)) {
            $loantype = LoanType::where('id_1c', $contracts1c['loan']['loantype_id_1c'])->first();
        }
        $loanSubdivision = Subdivision::where('name_id', $contracts1c['loan']['subdivision_id_1c'])->first();
        $loanUser = User::where('id_1c', 'like', StrUtils::stripWhitespaces($contracts1c['loan']['user_id_1c']) . '%')->first();
        $loan->id_1c = $contracts1c['loan']['loan_id_1c'];
        $loan->subdivision_id = (is_null($loanSubdivision)) ? NULL : $loanSubdivision->id;
        $loan->user_id = (is_null($loanUser)) ? Spylog::USERS_ID_1C : $loanUser->id;
        $loan->enrolled = (array_key_exists('enrolled', $contracts1c['loan'])) ? $contracts1c['loan']['enrolled'] : 1;
        $loan->uki = (array_key_exists('uki', $contracts1c['loan'])) ? $contracts1c['loan']['uki'] : 0;
        //если на эту заявку есть договор без номера, то удалить этот договор, а к заявке прикрепить новый договор
        if (is_null($loan->id)) {
            $otherLoanOnThisClaim = Loan::where('claim_id', $claim->id)->first();
        } else {
            $otherLoanOnThisClaim = Loan::where('id', '<>', $loan->id)->where('claim_id', $claim->id)->first();
        }
//        if (!is_null($otherLoanOnThisClaim) && ($otherLoanOnThisClaim->id_1c == '' || is_null($otherLoanOnThisClaim->id_1c))) {
        if (!is_null($otherLoanOnThisClaim)) {
            $otherLoanOnThisClaim->delete();
        }
        $loan->claim_id = $claim->id;
        $loan->money = $contracts1c['loan']['money'];
        $loan->special_percent = (array_key_exists('special_percent', $contracts1c['loan'])) ? str_replace(',', '.', $contracts1c['loan']['special_percent']) : null;
        if (!array_key_exists('card_number', $contracts1c['loan'])) {
            $loan->card_id = null;
            $loan->in_cash = 1;
        } else {
            $card = Card::where('card_number', $contracts1c['loan']['card_number'])->first();
            if (is_null($card)) {
                $card = new Card();
                $card->card_number = $contracts1c['loan']['card_number'];
                $card->customer_id = $claim->customer_id;
                $card->status = Card::STATUS_ACTIVE;
                $card->save();
            }
            $loan->card_id = $card->id;
            $loan->in_cash = 0;
        }
        if (isset($contracts1c['loan']['promocode_number'])) {
            $promocode = Promocode::where('number', $contracts1c['loan']['promocode_number'])->first();
            if (is_null($promocode)) {
                $promocode = new Promocode();
                $promocode->number = $contracts1c['loan']['promocode_number'];
                $promocode->save();
            }
            $loan->promocode_id = $promocode->id;
        }

        if (is_null($loantype)) {
            //если вид займа не найден, то ставить неопределенный тип (он должен быть в базе обязательно)
            $loantype = LoanType::where('name', 'UNDEFINED_LOAN')->first();
            if (is_null($loantype)) {
                Log::error('Ошибка вид займа не найден(Synchronizer.updateLoan)', $loan->toArray());
                return null;
            }
        }

        $loan->loantype_id = $loantype->id;
        if (is_null($loan->id)) {
            $rt_id = with(RepaymentType::where('text_id', config('options.rtype_closing'))->first())->id;
            $loan->closed = (Repayment::where('loan_id', $loan->id)->where('repayment_type_id', $rt_id)->count() > 0) ? 1 : 0;
        }

        //вписывает дату последнего платежа в кредитник
        if (!is_null($lastPayday)) {
            $loan->last_payday = $lastPayday->format('Y-m-d H:i:s');
        } else {
            $loan->last_payday = '0000-00-00 00:00:00';
        }
        if (is_null($loan->subdivision_id)) {
            $loan->subdivision_id = '113';
        }
        $loan->closed = 0;
        if (array_key_exists('tranche_number', $contracts1c['loan']) && $contracts1c['loan']['tranche_number'] != '') {
            $loan->tranche_number = $contracts1c['loan']['tranche_number'];
        }
        if (array_key_exists('first_loan_id_1c', $contracts1c['loan']) && $contracts1c['loan']['first_loan_id_1c'] != '') {
            $loan->first_loan_id_1c = $contracts1c['loan']['first_loan_id_1c'];
        }
        if (array_key_exists('first_loan_date', $contracts1c['loan']) && $contracts1c['loan']['first_loan_date'] != '') {
            $loan->first_loan_date = Carbon::createFromFormat('d.m.Y H:i:s', $contracts1c['loan']['first_loan_date'])->format('Y-m-d H:i:s');
        }
        //добавляем данные по списанному должнику
        if (array_key_exists('spisan', $contracts1c)) {
            $loanData['spisan'] = $contracts1c['spisan'];
            $loan->data = json_encode($loanData);
        }
        if (!$loan->save()) {
            Log::error('Ошибка на сохранении кредитника(Synchronizer.updateLoan)', $loan->toArray());
            return null;
        }
        return $loan;
    }

    /**
     * удаляем документы, которые не пришли из 1с. Допники не удаляем для сохранения 
     * истории, так как с 1с приходит только 2 последних допника
     * @param type $contracts1c весь массив документов из 1с
     * @param \App\Loan $loan
     */
    static function removeUnreceivedRepayments($contracts1c, $loan) {
        $reps = $loan->repayments;
        foreach ($reps as $rep) {
            if ($rep->repaymentType->isSuzStock()) {
                return;
            }
        }
        foreach ($reps as $rep) {
            $received = false;
            $duplicate = false;
            foreach ($contracts1c['repayments'] as $rep1c) {
                if ($rep1c['Number'] == $rep->id_1c) {
                    $received = true;
                    foreach ($reps as $rep1) {
                        //удаляет дубликаты договоров без ордеров
                        if ($rep->id_1c == $rep1->id_1c && $rep->id != $rep1->id && Order::where('repayment_id', $rep->id)->count() == 0) {
                            $duplicate = true;
                        }
                    }
                }
            }
            if (!$received && !$rep->repaymentType->isDopnik() && !$rep->repaymentType->isDopCommission() || $duplicate) {
                if ($rep->repaymentType->isClosing() && !$duplicate) {
                    $loan->update(['closed' => 0]);
                }
                if ($rep->delete()) {
                    Spylog::logModelAction(Spylog::ACTION_DELETE, Spylog::TABLE_REPAYMENTS, $rep->toArray());
                }
            }
        }
    }

    /**
     * Загружаем доп документы: допники, мировые, заявления
     * @param type $repayments массив с данными для документов, пришедший из 1с
     * @param \App\Loan $loan кредитный договор
     * @return массив объектов \App\Repayment
     */
    static function updateRepayments($repayments, $loan, $contracts1c = null) {
        $repsToSave = [];
        $newRulesDay = new Carbon(config('options.new_rules_day'));
        $permNewRulesDay = new Carbon(config('options.perm_new_rules_day'));
        $newPeaceDay = new Carbon(config('options.perm_new_rules_day'));
        $cardNalDopDay = new Carbon(config('options.card_nal_dop_day'));
        foreach ($repayments as $rep1cKey => $rep1c) {
            if (!is_array($rep1c)) {
                continue;
            }
            $repType = $rep1cKey;
            $repTypeUnderscorePos = strpos($repType, '_');
            if ($repTypeUnderscorePos !== FALSE) {
                $repType = substr($repType, 0, $repTypeUnderscorePos);
            }
            $rep = Repayment::where('id_1c', $rep1c["Number"])->first();
            if (!isset($rep) || is_null($rep)) {
                $rep = new Repayment();
            } else {
                $oldRep = $rep->toArray();
            }
            $repDate = new Carbon($rep1c['created_at']);
            $rep1c['created_at'] = with($repDate)->format('Y-m-d H:i:s');
            if ($rep1cKey == "suz") {
                $repType = ($loan->created_at->lt(new Carbon('2014-07-01'
                                . ''))) ? "suz1" : "suz2";
                $rep->time = config('options.suz_days');
            }
            $rep->repayment_type_id = with(RepaymentType::where('text_id', ($repType == 'dopnik_p') ? 'dopnik' : $repType)->select('id')->first())->id;
            if (($repType == 'dopnik' || $repType == 'dopnik_p') && $repDate->gte($newRulesDay)) {
                if ($repDate->gte(new Carbon(config('options.perm_new_rules_day')))) {
                    $prevRep = Repayment::where('loan_id', $loan->id)->where('id_1c', '<>', $rep1c["Number"])->orderBy('created_at', 'desc')->first();
                    $otherReps = Repayment::where('loan_id', $loan->id)->where('id_1c', '<>', $rep1c["Number"])->get();
                    $hasOverdue = false;
                    if ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
                        foreach ($otherReps as $otherRep) {
                            if ($otherRep->getOverdueDays(true) > 0) {
                                $hasOverdue = true;
                            }
                        }
                    }
                    /**
                     * Здесь меняем тип допника в зависимости от условий
                     * хозяйке на заметку: в Loan.getRequiredMoneyDetails() тип договора меняется на просроченный в зависимости от пришедшего процента
                     */
                    if (!is_null($prevRep)) {
                        if (($repDate->gt(with(new Carbon($prevRep->created_at))->addDays($prevRep->time + 1)) || $hasOverdue) &&
                                $repDate->gt(new Carbon('2016-06-07')) &&
                                $loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
                            if ($loan->created_at->gte(new Carbon(config('options.new_rules_day_010117')))) {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik7'))->select('id')->first())->id;
                            } else {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik5'))->select('id')->first())->id;
                            }
                        } else {
                            if ($repDate->gte($cardNalDopDay)) {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik6'))->select('id')->first())->id;
                            } else {
                                $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik4'))->select('id')->first())->id;
                            }
                        }
                    } else if ($repDate->gt(with(new Carbon($loan->created_at))->addDays($loan->time + 1)) &&
                            $repDate->gt(new Carbon('2016-06-07')) &&
                            $loan->created_at->gte(new Carbon(config('options.new_rules_day'))) || $hasOverdue) {
                        if ($loan->created_at->gte(new Carbon(config('options.new_rules_day_010117')))) {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik7'))->select('id')->first())->id;
                        } else {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik5'))->select('id')->first())->id;
                        }
                    } else {
                        if ($repDate->gte($cardNalDopDay)) {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik6'))->select('id')->first())->id;
                        } else {
                            $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik4'))->select('id')->first())->id;
                        }
                    }
                } else if ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik2'))->select('id')->first())->id;
                } else {
                    $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik3'))->select('id')->first())->id;
                }
            }
            if ($repType == 'peace') {
                if (array_key_exists('peace_type', $rep1c)) {
                    $peace_type = (int) $rep1c['peace_type'];
                    if ($peace_type == 0) {
                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace'))->select('id')->first())->id;
                    } else if ($peace_type == 1) {
                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace2'))->select('id')->first())->id;
                    } else if ($peace_type == 2) {
                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace3'))->select('id')->first())->id;
                    } else if ($peace_type == 3) {
                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_peace4'))->select('id')->first())->id;
                    }
                }
                //добавляем данные для печати, которые приходят для новых мировых
                $repData = [];
                foreach (['create_pc', 'create_exp_pc', 'create_fine', 'create_od'] as $item) {
                    if (array_key_exists($item, $rep1c)) {
                        $repData[$item] = number_format(floatval($rep1c[$item]), 2, '.', '');
                        if (!array_key_exists('create_req_money', $repData)) {
                            $repData['create_req_money'] = 0;
                        }
                        $repData['create_req_money'] += floatval($rep1c[$item]);
                    }
                }
                if (array_key_exists('create_pays', $rep1c)) {
//                    $repData['create_pays'] = $rep1c['create_pays'];
                }
                if (count($repData) > 0) {
                    if (array_key_exists('create_req_money', $repData)) {
                        $repData['create_req_money'] = number_format(floatval($repData['create_req_money']), 2, '.', '');
                    }
                    $rep->data = json_encode($repData);
                }
            }
            if (array_key_exists('user_id_1c', $rep1c)) {
                $repUser = User::where('id_1c', $rep1c['user_id_1c'])->first();
                if (!is_null($repUser)) {
                    $rep->user_id = $repUser->id;
                } else {
                    $rep->user_id = (!is_null($loan->user)) ? $loan->user_id : Spylog::USERS_ID_1C;
                }
            } else {
                if (is_null($rep->user_id)) {
                    $rep->user_id = (!is_null($loan->user)) ? $loan->user_id : Spylog::USERS_ID_1C;
                }
            }
            if (array_key_exists('subdivision_id_1c', $rep1c)) {
                $repSubdivision = Subdivision::where('name_id', $rep1c['subdivision_id_1c'])->select('id')->first();
            } else {
                $repSubdivision = NULL;
            }
            $rep->subdivision_id = (!is_null($repSubdivision)) ? $repSubdivision->id : NULL;
            $rep->id_1c = $rep1c['Number'];
            foreach (['od', 'pc', 'exp_pc', 'fine', 'tax', 'money'] as $item) {
                $rep1c[$item] = (isset($rep1c[$item])) ? round((float) str_replace(',', '.', $rep1c[$item]) * 100) : 0;
            }
            foreach (['was_od', 'was_pc', 'was_exp_pc', 'was_fine', 'was_tax'] as $item) {
                if (isset($rep1c[$item])) {
                    $rep1c[$item] = round((float) str_replace(',', '.', $rep1c[$item]) * 100);
                }
            }
            $rep->req_money = $rep1c["od"] + $rep1c["pc"] + $rep1c["exp_pc"] + $rep1c["fine"] + $rep1c["tax"];
            $rep->fill($rep1c);
            //в заявление приходит дата окончания заявления и от нее считаем срок
            if ($rep->repaymentType->isClaim()) {
                $reptime = with(new Carbon($rep1c['created_at']))->setTime(0, 0, 0)->diffInDays(with(new Carbon($rep1c['time']))->setTime(0, 0, 0));
                if (array_key_exists('claim_type', $rep1c)) {
                    if ($rep1c['claim_type'] == 1) {
                        $rt = RepaymentType::where('text_id', config('options.rtype_claim2'))->select('id')->first();
                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_claim2'))->select('id')->first())->id;
                    } else if ($rep1c['claim_type'] == 2) {
                        //допник с комиссией
                        $rt = RepaymentType::where('text_id', config('options.rtype_claim3'))->select('id')->first();
                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_claim3'))->select('id')->first())->id;
                    }
                }
//                else {
//                    if ($loan->created_at->gte(new Carbon(config('options.new_rules_day')))) {
//                        $rep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_claim2'))->select('id')->first())->id;
//                    }
//                }
//                $reptime = with(new Carbon($rep1c['created_at']))->setTime(0, 0, 0)->diffInDays(with(new Carbon($rep1c['time']))->setTime(0, 0, 0));
                //если заявление вдруг меньше стандартного срока то значит сегодняшний день не посчитался
//                if ($reptime < $rep->repaymentType->default_time) {
//                if (!$rep->repaymentType->isDopCommission()) {
                $rep->time = $reptime;
//                }
//                } else{
//                    $rep->time = $reptime;
//                }
//                if($rep->time>$rep->repaymentType->default_time){
//                    $rep->time = $rep->repaymentType->default_time;
//                }
//                    $rep->time = $rep->repaymentType->time;
            }
            //если допник то сумма основного долга = сумме задолженности
            if ($rep->repaymentType->isDopnik()) {
                $rep->od = $rep1c['money'];
                $rep->req_money += $rep->od;
            }
            if ($rep->repaymentType->isClosing()) {
                $rep->time = 0;
                $loan->update(['closed' => 1]);
            }
            if ($rep->repaymentType->isSUZ()) {
                if (array_key_exists('resp_fio', $rep1c) && array_key_exists('resp_phone', $rep1c)) {
//                    АкцияСУЗ (Ульрих Ю.А) - 8-903-046-63-58
//                    Акция НовыйГодБезДолгов (Мазуркина Н.Е.) - 8-960-910-56-69
                    $rep->comment = $rep1c['resp_fio'] . ' ' . $rep1c['resp_phone'];
                    if (!is_null($contracts1c)) {
                        if (array_key_exists('ngbezdolgov', $contracts1c)) {
                            $rep->comment = 'Акция НовыйГодБезДолгов (Мазуркина Н.Е.) - 8-960-910-56-69';
                        } else if (array_key_exists('akcsuzst46', $contracts1c)) {
                            $rep->comment = 'АкцияСУЗ (Ульрих Ю.А) - 8-903-046-63-58';
                        }
                    }
                }
                $repData = [
                    'stock_type' => (array_key_exists('stock_type', $rep1c)) ? $rep1c['stock_type'] : '',
                    'stock_created_at' => (array_key_exists('stock_created_at', $rep1c)) ? $rep1c['stock_created_at'] : '',
                ];
                if (array_key_exists('suzpays', $rep1c)) {
                    $repData['pays'] = $rep1c['suzpays'];
                }
                foreach (['print_od', 'print_pc', 'print_exp_pc', 'print_fine', 'print_tax'] as $p) {
                    if (array_key_exists($p, $rep1c)) {
                        $repData[$p] = $rep1c[$p] * 100;
                    } else {
                        $repData[$p] = 0;
                    }
                }
                if (count($repData) > 0) {
                    $rep->data = json_encode($repData);
                }
                if (array_key_exists('totalsum', $rep1c)) {
                    $rep->req_money = StrUtils::rubToKop($rep1c['totalsum']);
                }
            }
            $rep->loan_id = $loan->id;
            //срок мирового считаем от последего платежа
            if (isset($rep1c["pays"])) {
                $pays1c = $rep1c["pays"];
                usort($pays1c, function($a, $b) {
                    return strtotime($a['end_date']) - strtotime($b['end_date']);
                });
                $paysNum = count($pays1c);
                if ($paysNum > 0) {
                    $rep->time = with(new Carbon($pays1c[0]["end_date"]))->subMonth()->diffInDays(new Carbon($pays1c[$paysNum - 1]["end_date"]));
                }
            }
            if ($rep->repaymentType->isPeace()) {
                if ($rep->exp_pc < 0) {
                    $rep->exp_pc = 0;
                }
                if ($rep->fine < 0) {
                    $rep->fine = 0;
                }
            }

            if (!$rep->save()) {
                Log::error('Ошибка на сохранении договора(Synchronizer.updateRepayments)', $rep->toArray());
                return null;
            }
            if (isset($oldRep) && array_key_exists('id', $oldRep) && $rep->id == $oldRep['id']) {
                Spylog::logModelChange(Spylog::TABLE_REPAYMENTS, $oldRep, $rep->toArray());
            } else {
                Spylog::logModelAction(Spylog::ACTION_CREATE, 'repayments', $rep->toArray());
            }
            $repsToSave[] = $rep;
            if (isset($pays1c) && $rep->repaymentType->isPeace()) {
                PeacePay::where('repayment_id', $rep->id)->delete();
                $paysNum = count($rep1c["pays"]);

                $pays = [];
                foreach ($pays1c as $pay1c) {
                    $pay = new PeacePay();
                    $pay->repayment_id = $rep->id;
                    $pay->created_at = $rep->created_at;
                    $pay->money = round((float) str_replace(',', '.', $pay1c['total']) * 100);
                    $pay->total = round((float) str_replace(',', '.', $pay1c['total']) * 100);
                    $pay->fine = round((float) str_replace(',', '.', $pay1c['fine']) * 100);
                    if (array_key_exists('exp_pc', $pay1c)) {
                        $pay->exp_pc = round((float) str_replace(',', '.', $pay1c['exp_pc']) * 100);
                    }
                    $pay->end_date = with(new Carbon($pay1c['end_date']))->format('Y-m-d H:i:s');
                    $pay->closed = ($pay1c['closed'] == 'Да') ? 1 : 0;
                    if ($pay1c["total"] == 0) {
                        $pay->closed = 1;
                    }
                    $pays[] = $pay;
                }
                $rep_exp_pc = $rep->exp_pc;
                foreach ($pays as $pay) {
                    if (!$pay->save()) {
                        Log::error('Ошибка на сохранении платежа по мировому(Synchronizer.updateRepayments)', $pay->toArray());
                        return null;
                    }
                }
            }
        }
        return $repsToSave;
    }

    static function addCustomer($series, $number, $failOnEmpty = true) {
        $customer1c = MySoap::passport(['series' => $series, 'number' => $number, 'old_series' => '', 'old_number' => '']);
        if ((!(bool) $customer1c['res'] || is_null($customer1c) || $customer1c == '') && $failOnEmpty) {
            return null;
        }
        $customer = new Customer();
        $customer->fill($customer1c);
        $customer->creator_id = Spylog::USERS_ID_1C;
        $customer->id_1c = (array_key_exists('customer_id_1c', $customer1c)) ? $customer1c['customer_id_1c'] : '';
        if (!$customer->save()) {
            Log::error('ошибка на сохранении клиента(Synchronizer.addCustomer)', ['customer' => $customer]);
            return null;
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, 'customers', $customer);
        $passport = new Passport();
        $passport->fill($customer1c);
        $passport->series = $series;
        $passport->number = $number;
        $passport->customer_id = $customer->id;
        if (!$passport->save()) {
            Log::error('Ошибка на сохранении паспорта(Synchronizer.addCustomer)', $passport->toArray());
            return null;
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, 'passports', $passport);

        $about_client = new about_client();
        if (isset($customer1c) && is_array($customer1c)) {
            foreach (['adsources' => 'adsource', 'live_conditions' => 'zhusl', 'education_levels' => 'obrasovanie', 'marital_types' => 'marital_type_id'] as $table => $field) {
                if (!array_key_exists($field, $customer1c) || !is_int($customer1c[$field]) || is_null(DB::table($table)->where('id', $customer1c['field'])->first())) {
                    $customer1c[$field] = NULL;
                }
            }
            $about_client->fill($customer1c);
            foreach (ClaimController::$claimCheckboxes as $cb) {
                if (array_key_exists($cb, $customer1c)) {
                    $about_client->setAttribute($cb, ($customer1c[$cb] == 'Да' || $customer1c[$cb] == '1') ? 1 : 0);
                }
            }
            if (array_key_exists('sex', $customer1c)) {
                $about_client->sex = ($customer1c['sex'] == 'Мужской') ? 1 : 0;
            }
        }
        $about_client->vidtruda = NULL;
        $about_client->goal = NULL;
        $about_client->avto = NULL;
        $about_client->customer_id = $customer->id;
        if (!$about_client->save()) {
            Log::error('Ошибка на сохранении данных о клиенте(Synchronizer.addCustomer)', $about_client->toArray());
            return null;
        }
        Spylog::logModelAction(Spylog::ACTION_CREATE, 'about_clients', $about_client);
        return ['customer' => $customer, 'passport' => $passport, 'about_client' => $about_client];
    }

    /**
     * получить ордеры из 1с и записать в базу
     * @param string $date - дата создания ордеров
     * @param string $passport_series серия паспорта
     * @param string $passport_number номер паспорта
     * @param string $subdivision_id_1c код подразделения
     * @param \App\Loan $loan кредитник
     * @return \App\Order
     */
    public static function updateOrders($date, $passport_series = null, $passport_number = null, $subdivision_id_1c = null, $loan = null, $end_date = null) {
        if (!is_null($passport_series) && !is_null($passport_number)) {
            $passport = Passport::where('series', $passport_series)->where('number', $passport_number)->first();
            if (!is_null($end_date)) {
                $res1c = MySoap::sendXML(MySoap::createXML([
                                    'created_at' => with(new Carbon($date))->format('Ymd'),
                                    'type' => '3',
                                    'passport_series' => $passport_series,
                                    'passport_number' => $passport_number,
                                    'loan_id_1c' => $loan->id_1c,
                                    'search_type' => '4',
                                    'customer_id_1c' => $passport->customer->id_1c,
                                    'start_date' => with(new Carbon($date))->format('Ymd'),
                                    'end_date' => with(new Carbon($end_date))->format('Ymd')
                                ]), false);
            } else {
                if (is_null($loan)) {
                    $res1c = MySoap::sendXML(MySoap::createXML(['created_at' => with(new Carbon($date))->format('Ymd'), 'type' => '3', 'passport_series' => $passport_series, 'passport_number' => $passport_number, 'search_type' => '2']), false);
                } else {
                    $res1c = MySoap::sendXML(MySoap::createXML(['created_at' => with(new Carbon($date))->format('Ymd'), 'type' => '3', 'passport_series' => $passport_series, 'passport_number' => $passport_number, 'loan_id_1c' => $loan->id_1c, 'search_type' => '1']), false);
                }
            }
        } else if (!is_null($subdivision_id_1c)) {
            $searchedSubdiv = Subdivision::where('name_id', $subdivision_id_1c)->first();
            $res1c = MySoap::sendXML(MySoap::createXML(['created_at' => with(new Carbon($date))->format('Ymd'), 'type' => '3', 'subdivision_id_1c' => $subdivision_id_1c, 'search_type' => '3']), false);
        } else {
            return null;
        }
        if (is_null($res1c) || !$res1c->result || !isset($res1c->orders)) {
            return null;
        }
        $rko_id = OrderType::getRKOid();
        $pko_id = OrderType::getPKOid();
        $orderTypes = OrderType::all();
        DB::beginTransaction();
        $orders = [];
        foreach ($res1c->orders->children() as $o) {
            $orderDate = new Carbon((string) $o->date);
            $order = Order::where('number', '=', ((string) $o->number))
//                    ->where('type', '=', (string) $o->order_type)
//                    ->where('type','=',(string) $o->type)
                    ->whereBetween('created_at', [
                        $orderDate->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                        $orderDate->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                    ])
                    ->first();
            if (is_null($order)) {
                $order = new Order();
                $order->number = (string) $o->number;
            }
            $order->created_at = with(new Carbon((string) $o->date))->format('Y-m-d H:i:s');
            $reason = (string) $o->reason;
            $account = (string) $o->accounting_records;
            $item = (string) $o->item;
            $customer_id_1c = (string) $o->customer_id_1c;
            $order->reason = $reason;
            if ((string) $o->type == MySoap::ITEM_RKO) {
                if (strstr($reason, 'Самоинкассация') !== FALSE && $account == '57.01') {
                    if (strstr($item, 'СБЕРБАНК') !== FALSE) {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'SBERINCASS');
                    } else
                    if (strstr($item, 'УРАЛСИБ') !== FALSE) {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'URALINCASS');
                    } else {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'INCASS');
                    }
                } else
                if (strstr($item, 'Канцтовары') !== FALSE && $account == '71.01') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'CANC');
                } else
                if ((string)$o->order_type==OrderType::INTERNET && $account == '71.01') {
                    $order->type = OrderType::getIdByTextId(OrderType::INTERNET);
                } else
                if (strstr($reason, 'Коммиссия') !== FALSE && $account == '91.02') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'COMIS');
                } else
                if (strstr($reason, 'Почтовые расходы') !== FALSE) {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'POCHTA');
                } else
                if ($account == '70') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'SALARY');
                } else
                if ($account == '71.01') {
                    if (strstr($item, 'Внутреннее перемещение') !== FALSE) {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'RASHOD');
                } else {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'PODOTCHET');
                    }
                } else
                if ($account == '94') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, OrderType::DEFICITRKO);
                } else {
                    $order->type = $rko_id;
                }
            } else if ((string) $o->type == MySoap::ITEM_PKO) {
                $order->type = $pko_id;
                if ((strstr($reason, 'Возврат процентов') !== FALSE || strstr($reason, 'Оплата процентов и основного долга') !== FALSE) && $account == '62.01') {
                    $order->purpose = Order::P_PC;
                } else
//                if ((strstr($reason, 'Возврат процентов') !== FALSE || strstr($reason, 'Оплата процентов и основного долга') !== FALSE || strstr($item, 'Просроченные проценты') !== FALSE) && $account == '62.04') {
                if ($account == '62.04') {
                    $order->purpose = Order::P_EXPPC;
                } else
                if ((strstr($reason, 'основного долга') !== FALSE || strstr($reason, 'Оплата процентов и основного долга') !== FALSE) && $account == '58.03') {
                    $order->purpose = Order::P_OD;
                } else
                if ($account == '76.02') {
                    $order->purpose = Order::P_FINE;
                } else
                if ($account == '76.09') {
                    if (strstr($reason, 'НДС с комиссии') !== FALSE) {
                        $order->purpose = Order::P_COMMISSION_NDS;
                    } else if (strstr($reason, 'НДС') !== FALSE) {
                        $order->purpose = Order::P_UKI_NDS;
                    } else if (strstr($reason, 'Комиссия за продление') !== FALSE) {
                        $order->purpose = Order::P_COMMISSION;
                    } else if (strstr($reason, 'Комиссия') !== FALSE) {
                        $order->purpose = Order::P_UKI;
                    }
                } else
                if ($account == '71.01') {
                    if (strstr($reason, 'Возврат подотчетных сумм') !== FALSE) {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'VOZVRAT');
                    } else {
                        $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'VPKO');
                    }
                } else
                if ($account == '50.01') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'VPKO');
                } else
                if ($account == '94') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'DEFICIT');
                } else
                if ($account == '91.01') {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'OVERAGE');
                } else if ($account == '76.10') {
                    $order->purpose = Order::P_TAX;
                } else if($account == '76.13'){
                    $order->type = OrderType::getIdByTextId(OrderType::OPKO);
                }
                if (strstr($reason, 'Возврат недостачи') !== FALSE) {
                    $order->type = Synchronizer::getOrderTypeByTextID($orderTypes, 'DEFICIT');
                }
            }
            if (isset($o->order_type) && in_array((string) $o->order_type, [3, 20, 21, 22, 23, 24, 25, 26])) {
                $order->type = (string) $o->order_type;
            }
            $ops = StrUtils::removeNonDigits((string) $o->passport_series);
            $opn = StrUtils::removeNonDigits((string) $o->passport_number);
            if (!is_null($ops) && $ops != '') {
                $passport = Passport::where('series', StrUtils::removeNonDigits((string) $o->passport_series))->where('number', (string) $o->passport_number)->first();
                if (is_null($passport)) {
                    $customer = Http\Controllers\CustomersController::getCustomerFrom1c((string) $o->passport_series, (string) $o->passport_number);
                    if (!is_null($customer)) {
                        $passport = $customer['passport'];
                    }
                }
            }
            if (isset($passport) && !is_null($passport)) {
                if (!is_null($ops) && $ops != '' && $ops != $passport->series && $opn != $passport->number) {
                    $passport = Passport::where('series', $ops)->where('number', $opn)->first();
                }
                if (!is_null($passport)) {
                    $order->passport_id = $passport->id;
                }
            }
            if (is_null($order->passport_id)) {
                $customer = Customer::where('id_1c', $customer_id_1c)->first();
                if (!is_null($customer)) {
                    $lastPassport = $customer->getLastPassport();
                    if (!is_null($lastPassport)) {
                        $order->passport_id = $lastPassport->id;
                    }
                }
            }
            if (is_null($loan)) {
                $loan = Loan::where('id_1c', (string) $o->loan_id_1c)->first();
            }
            if (!is_null($loan)) {
                //проверяем сходится ли тот паспорт который мы подставили в ордер с паспортом который на кредитнике
                if (!is_null($order->passport_id) && !is_null($loan->claim_id) && $order->passport_id == $loan->claim->passport_id) {
                    $order->loan_id = $loan->id;
                } else {
                    if (!is_null($order->loan_id) && !in_array($order->purpose, [Order::P_UKI_NDS, Order::P_UKI])) {
                        $order->loan_id = null;
                    }
                }
            }
            $docbase = (string) $o->doc_base;
            if (!is_null($docbase) && $docbase != '') {
                if (is_null($loan) || (!is_null($loan) && $docbase != $loan->id_1c)) {
                    $rep = Repayment::where('id_1c', $docbase)->select('id')->first();
                    if (!is_null($rep)) {
                        $order->repayment_id = $rep->id;
                    }
                }
            }
            if (is_null($order->repayment_id) && ($order->purpose == Order::P_UKI_NDS || $order->purpose == Order::P_UKI)) {
                if (!is_null($order->loan_id)) {
                    $closing = Repayment::where('loan_id', $order->loan_id)->orderBy('created_at', 'desc')->first();
                    if (!is_null($closing) && $closing->repaymentType->isClosing()) {
                        $order->repayment_id = $closing->id;
                    }
                }
            }
            if (!is_null($order->repayment) && $order->repayment->id_1c == '') {
                $order->repayment_id = null;
            }
            if (isset($searchedSubdiv) && !is_null($searchedSubdiv)) {
                $subdiv = $searchedSubdiv;
            } else {
                $subdiv = Subdivision::where('name_id', (string) $o->subdivision_id_1c)->select('id')->first();
            }
            if (!is_null($subdiv)) {
                $order->subdivision_id = $subdiv->id;
            }
            $user = User::where('id_1c', (string) $o->user_id_1c)->select('id')->first();
            if (!is_null($user)) {
                $order->user_id = $user->id;
            } else {
                $user = User::where('login', (string) $o->user_id_1c)->select('id')->first();
                if (!is_null($user)) {
                    $order->user_id = $user->id;
                } else {
                    $user = User::createEmptyUser((string) $o->user_id_1c, (!is_null($subdiv)) ? $subdiv->id : null);
                    if (!is_null($user)) {
                        $order->user_id = $user->id;
                    }
                }
            }
            $order->money = ((float) $o->sum) * 100;
            $order->fio = (string) $o->fio;
            $order->passport_data = (string) $o->passport;

            if (!$order->save()) {
                DB::rollback();
                Log::error('Synchronizer.updateOrders failonordersave', ['order' => $order]);
                return null;
            }
            $orders[] = $order;
        }
        DB::commit();
        return $orders;
    }

    static function getOrderTypeByTextID($orderTypes, $text_id) {
        foreach ($orderTypes as $ot) {
            if ($ot->text_id == $text_id) {
                return $ot->id;
            }
        }
        return null;
    }

}
