<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller,
    App\ContractForm,
    Illuminate\Http\Request,
    mikehaertl\wkhtmlto\Pdf,
    App\Claim,
    App\Customer,
    App\Passport,
    App\Loan,
    App\about_client,
    Illuminate\Support\Facades\Input,
    DateTime,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    Auth,
    Carbon\Carbon,
    App\StrUtils,
    App\LoanType,
    App\Repayment,
    App\RepaymentType,
    App\PeacePay,
    App\Utils\Petrovich,
    App\Utils\FileToPdfUtil,
    Log,
    App\NpfContract,
    App\Utils\StrLib,
    Illuminate\Support\Facades\Config,
    App\Order;

class ContractEditorController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        $contracts = ContractForm::orderBy('updated_at', 'desc')->get();
        return view('contracteditor.index', ['contracts' => $contracts]);
    }

    public function openEditor($contract_id = null) {
        $contract = (!is_null($contract_id) && $contract_id != "") ? (ContractForm::find($contract_id)) : (new ContractForm());
        Spylog::log(Spylog::ACTION_OPEN, 'contract_forms', $contract_id);
        return view('contracteditor.edit', ['contract' => $contract]);
    }

    public function update() {
        $contract = ContractForm::findOrNew(Input::get('id'));
        if (!is_null($contract->id)) {
//            Spylog::logModelChange('contract_forms', $contract, Input::all());
            $contract->fill(Input::all());
        } else {
            $contract->fill(Input::all());
            Spylog::logModelAction(Spylog::ACTION_CREATE, 'contract_forms', $contract);
        }
        if ($contract->save()) {
            return redirect()->back()->with('msg_suc', StrLib::$SUC_SAVED);
        } else {
            return redirect()->back()->with('msg_err', StrLib::$ERR);
        }
    }

    public function delete($contract_id) {
        if (ContractForm::where('id', (int) $contract_id)->delete()) {
            Spylog::log(Spylog::ACTION_DELETE, 'contract_forms', $contract_id);
            return redirect()->back()->with('msg_suc', StrLib::$SUC);
        } else {
            return redirect()->back()->with('msg_err', StrLib::$ERR_CANT_DELETE);
        }
    }

    public function createPdf($contract_id, $claim_id = null, $repayment_id = null) {
        $contract = ContractForm::where('id', (int) $contract_id)->first();
        if (is_null($contract)) {
            abort(404);
        }
        if ($contract->text_id == config('options.orderRKO')) {
            $loan = Loan::where('claim_id', $claim_id)->first();
            if (is_null($loan) || is_null($loan->order_id)) {
                abort(404);
            }
        }
        if (!is_null($contract->tplFileName) && mb_strlen($contract->tplFileName) > 0) {
            if (file_exists(FileToPdfUtil::getPathToTpl() . $contract->tplFileName)) {
                return $this->createPdfFromFile($contract, $claim_id, $repayment_id);
            }
        }

        $opts = [];
        if (!is_null($claim_id)) {
            //подбираем версию документа
            $claim = Claim::find($claim_id);
            $loan = Loan::where('claim_id', $claim_id)->first();
            $repayment = null;
            if (!is_null($repayment_id)) {
                $repayment = Repayment::find($repayment_id);
            }
            if (!is_null($repayment)) {
                if ($repayment->repaymentType->isDopnik() || $repayment->repaymentType->isDopCommission()) {
                    $contract = $contract->getLastVersion($loan->created_at);
                } else {
                    $contract = $contract->getLastVersion($repayment->created_at);
                }
            } else if (!is_null($loan) && $contract->text_id == config('options.loan_contract')) {
                $contract = $contract->getLastVersion($loan->created_at);
            } else if (!is_null($claim)) {
                $contract = $contract->getLastVersion($claim->created_at);
            }
            $html = $contract->template;
            $html = $this->processHtml($contract, $claim_id, $repayment_id, $claim, $loan, $repayment);
        } else {
            $html = $contract->template;
        }
        $html = ContractEditorController::replaceConfigVars($html);
        if ($contract->text_id == 'npf') {
            $opts['margin-top'] = "0.25cm";
            $opts['margin-right'] = "0.1cm";
            $opts['margin-bottom'] = "0.25cm";
            $opts['margin-left'] = "0.1cm";
        }
        if ($contract->text_id == 'loan') {
            $opts['margin-top'] = "0.5cm";
            $opts['margin-right'] = "0.5cm";
            $opts['margin-bottom'] = "0.5cm";
            $opts['margin-left'] = "0.5cm";
        }
        if ($contract->text_id == config('options.grid')) {
            $opts['orientation'] = 'landscape';
        }
        if (is_null($claim_id)) {
            $html = str_replace('{{passports.fio}}', '___________________________________________________________________________________', $html);
            $html = str_replace('{{passports.issued}}', '________________________________________________________________________________', $html);
            $html = str_replace('{{full_address}}', '_____________________________________________________________________________________', $html);
            $dayPercent = with(\App\LoanRate::getByDate('2017-07-01'))->pc;
            $html = str_replace('{{yearPercent365AfterExp}}', number_format($dayPercent*365, 3, ',', ''), $html);
            $html = str_replace('{{num2str(yearPercent365AfterExp)}}', StrUtils::percentsToStr(number_format($dayPercent*365, 3, '.', '')), $html);
            $html = str_replace('{{loantypes.percent}}', $dayPercent, $html);
            $html = str_replace('{{num2str(loantypes.pc_after_exp)}}', StrUtils::percentsToStr($dayPercent,true), $html);
            $html = str_replace('{{loantypes.pc_after_exp}}', $dayPercent, $html);
            $html = ContractEditorController::clearTags($html, '___________');
        } else {
            $html = ContractEditorController::clearTags($html);
        }
        return \App\Utils\PdfUtil::getPdf($html, $opts);
    }

    public function createPdfNpf($contract_id, $npf_id) {
        return redirect('home');
        $contract = ContractForm::find((int) $contract_id);
        $npf = NpfContract::find((int) $npf_id);
        if (is_null($contract) || is_null($npf)) {
            return redirect()->back()->with('msg_err', 'no contract');
        }
        $html = $contract->template;
        $html = ContractEditorController::processParams([
                    'npf_contracts' => $npf->toArray(),
                    'customers' => $npf->passport->customer->toArray(),
                    'passports' => $npf->passport->toArray()
                        ], $html);
        $opts['margin-top'] = "0.25cm";
        $opts['margin-right'] = "0.1cm";
        $opts['margin-bottom'] = "0.25cm";
        $opts['margin-left'] = "0.1cm";
        return \App\Utils\PdfUtil::getPdf($html, $opts);
    }

    // формирование PDF из ODS-шаблона или ODT-шаблона
    public function createPdfFromFile($contract, $claim_id = null, $repayment_id = null) {
        $arCurrentUser = Auth::user()->toArray();

        if ($contract->text_id == config('options.orderRKO')) {
            $loan = Loan::where('claim_id', $claim_id)->first();
            if (is_null($loan) || is_null($loan->order_id)) {
                abort(404);
            }
        }
        $claim = Claim::find($claim_id);
        $loan = Loan::where('claim_id', $claim_id)->first();
        //echo '<pre>'; print_r($claim); echo '</pre>'; die();
//        echo '<pre>'; print_r(about_client::find($claim['about_client_id'])->toArray()); echo '</pre>'; die();
//        echo '<pre>'; print_r(Passport::find($claim['passport_id'])->toArray()); echo '</pre>';
//        echo '<pre>'; print_r(Customer::find($claim['customer_id'])->toArray()); echo '</pre>';

        $client = about_client::find($claim['about_client_id'])->toArray();
        $passport = Passport::find($claim['passport_id'])->toArray();
        $customer = Customer::find($claim['customer_id'])->toArray();

        if (is_null($loan)) {
            $username = $arCurrentUser['name'];
        } else {
            $username = $claim->user->name;
            if (is_null($username)) {
                
                $username = $arCurrentUser['name'];
            }
        }

        $data = [
            'about_clients' => $client,
            'passports' => $passport,
            'customers' => $customer,
            'claims' => $claim->toArray(),
            'loans' => $loan,
            'users' => ['name' => $username]
        ];


        return \App\Utils\FileToPdfUtil::replaceKeys($contract->tplFileName, $data);
    }

    static public function replaceConfigVars($html) {
        $vars = Config::get('vars');
        foreach ($vars as $k => $v) {
            $html = str_replace('{{config.' . $k . '}}', $v, $html);
        }
        return $html;
    }

    public function processHtml($contract, $claim_id, $repayment_id = null, $claim = null, $loan = null, $repayment = null) {
        $html = $contract->template;
        $objects = [];
        $objects['claims'] = (is_null($claim)) ? Claim::find($claim_id)->toArray() : $claim->toArray();
        $objects['customers'] = Customer::find($objects['claims']['customer_id'])->toArray();
        $objects['about_clients'] = about_client::find($objects['claims']['about_client_id'])->toArray();
        if ($objects['about_clients']['pensioner']) {
            $html = str_replace('{{client_type}}', 'пенсионером', $html);
        } else if ($objects['about_clients']['postclient']) {
            $html = str_replace('{{client_type}}', 'постоянным клиентом', $html);
        }
        $objects['passports'] = Passport::find($objects['claims']['passport_id'])->toArray();
        if(!empty($objects['passports']['address_city1'])){
            $objects['passports']['address_city'] = $objects['passports']['address_city'].' '.$objects['passports']['address_city1'];
        }
        if(!empty($objects['passports']['fact_address_city1'])){
            $objects['passports']['fact_address_city'] = $objects['passports']['fact_address_city'].' '.$objects['passports']['fact_address_city1'];
        }
        if (is_null($loan)) {
            $loan = Loan::where('claim_id', $claim_id)->first();
        }
        if (!is_null($loan)) {
            if (!is_null($repayment_id)) {
                $rep = (is_null($repayment)) ? Repayment::find($repayment_id) : $repayment;
                if (!is_null($rep) && !$rep->created_at->isToday()) {
                    $mDet = $loan->getDebtFrom1c($loan, $rep->created_at->format('Y-m-d'), $rep);
                } else {
                    $mDet = $loan->getRequiredMoneyDetails();
                }
            } else {
                $mDet = $loan->getRequiredMoneyDetails();
            }
            $loanrate = $loan->getLoanRate();
            $objects['mDet'] = ['od' => $mDet->od / 100, 'pc' => $mDet->pc / 100, 'exp_pc' => $mDet->exp_pc / 100, 'money' => $mDet->money / 100];
            $objects['loans'] = $loan->toArray();
            $objects['loans']['end_date'] = with(new Carbon($loan->created_at))->addDays($loan->time);

            $objects['loans']['print_percent'] = $loanrate->pc;
            $objects['loans']['print_year_percent'] = number_format($objects['loans']['print_percent'] * (365 + date('L')), 3, ',', '');
            $objects['loans']['print_percent365'] = number_format($objects['loans']['print_percent'] * 365, 3, ',', '');
            $objects['loans']['print_percent366'] = number_format($objects['loans']['print_percent'] * 366, 3, ',', '');
            $objects['loans']['tranche_number'] = (is_null($loan->tranche_number)) ? 1 : $loan->tranche_number;

            if ($loan->closed) {
                $closing = Repayment::where('loan_id', $loan->id)->where('repayment_type_id', with(RepaymentType::where('text_id', config('options.rtype_closing'))->first())->id)->first();
                if (!is_null($closing)) {
                    $objects['loans']['close_date'] = $closing->created_at->format('d.m.Y');
                }
            }

            $objects['subdivisions'] = \App\Subdivision::find($objects['loans']['subdivision_id'])->toArray();
            $objects['users'] = $loan->user->toArray();

            if ($objects['loans']['time'] >= 24 && $loan->loantype->id_1c == 'ARM000025') {
                //для договора под 0%, если срок займа больше 24 дней, то с 24 дня по 30 или по последний день договора вывести строчки о прекращении начисления процентов
                $daysToZeroPc = ($objects['loans']['time'] > 30) ? 30 : $objects['loans']['time'];
                $html = str_replace(
                        '{{zero_percent_1}}', '<strong>' . config('vars.company_name') . ' на период с {{addDays(loans.created_at,24)}} по {{addDays(loans.created_at,' . $daysToZeroPc . ')}} отменяет начисление процентов на сумму займа.</strong>', $html
                );
                $html = str_replace(
                        '{{zero_percent_2}}', 'В период с {{addDays(loans.created_at,24)}} по {{addDays(loans.created_at,' . $daysToZeroPc . ')}} проценты за пользование суммой займа не начисляются.', $html
                );
            }

            if (array_key_exists('order_id', $objects['loans']) && is_int($objects['loans']['order_id'])) {
                $objects['orders'] = Order::find($objects['loans']['order_id'])->toArray();
                if (array_key_exists('type', $objects['orders']) && !is_null($objects['orders']['type'])) {
                    $objects['order_types'] = \App\OrderType::find($objects['orders']['type'])->toArray();
                }
                $objects['orders']['reason'] = 'На основании договора №' . $loan->id_1c . ' от ' . (with(new Carbon($loan->created_at))->format('d.m.Y')) . ' г.';

                if (isset($objects['passports']) && !is_null($objects['passports'])) {
                    $objects['orders']['fio'] = $objects['passports']['fio'];
                    $objects['orders']['passport_data'] = 'Паспорт гражданина Российской Федерации, серия: '
                            . $objects['passports']['series'] . ', № ' . $objects['passports']['number'] . ', выдан: '
                            . with(new Carbon($objects['passports']['issued_date']))->format('d.m.Y') . ' года, ' . $objects['passports']['issued'] . ', № подр. '
                            . $objects['passports']['subdivision_code'];
                }
            }
            $objects['loantypes'] = (!is_null($loan->loantype)) ? $loan->loantype->toArray() : [];
            if (array_key_exists('percent', $objects['loantypes'])) {
                $objects['loantypes']['percent'] = with($loan->getPercents())['pc'];
                $objects['loantypes']['pc_after_exp'] = $loanrate->pc;
                $objects['loans']['end_od_and_pc'] = $loan->money + $loan->money * ($objects['loantypes']['percent'] / 100) * $loan->time;
            }

            if (!is_null($loan->card_id) && $loan->card_id > 0) {
                $objects['cards'] = $loan->card->toArray();
            }
            if (isset($rep) && !is_null($rep)) {
                $repPercent = $rep->repaymentType->percent;
                $newrules010117 = new Carbon(config('options.new_rules_day_010117'));
                $newrules010717 = new Carbon(config('options.new_rules_day_010717'));
                $newrules290316 = new Carbon(config('options.new_rules_day'));
                if ($rep->repaymentType->isDopnik() && $rep->created_at->gte($newrules010117) && $loan->created_at->gte($newrules290316)) {
//                    $repPercent = 2.17;
                }
                if ($rep->repaymentType->isDopnik() || $rep->repaymentType->isClaim()) {
                    $html = $this->replaceMultiplierText($html, $loan, $rep);
                }
                $objects['repayments'] = $rep->toArray();
                if ($rep->isArhivUbitki()) {
                    $objects['users'] = Auth::user()->toArray();
                    $objects['subdivisions'] = Auth::user()->subdivision->toArray();
                } else {
                    $objects['users'] = $rep->user->toArray();
                    $subdivision = \App\Subdivision::find($objects['repayments']['subdivision_id']);
                    if (!is_null($subdivision)) {
                        $objects['subdivisions'] = $subdivision->toArray();
                    } else {
                        $objects['subdivisions'] = Auth::user()->subdivision->toArray();
                    }
                }
                foreach (['od', 'pc', 'req_money', 'exp_pc', 'fine', 'was_pc', 'was_exp_pc', 'was_fine', 'was_od', 'tax', 'was_tax'] as $m) {
                    $objects['repayments'][$m] = StrUtils::kopToRub($objects['repayments'][$m]);
                }
                if (in_array($rep->repaymentType->text_id, [config('options.rtype_dopnik5'), config('options.rtype_dopnik7')])) {
                    $objects['repayments']['was_od'] = $objects['repayments']['od'];
                    $objects['repayments']['was_fine'] = $objects['repayments']['fine'];
                }
                $objects['repayments']['was_req_money'] = $objects['repayments']['was_od'] + $objects['repayments']['was_pc'] + $objects['repayments']['was_exp_pc'] + $objects['repayments']['was_fine'] + $objects['repayments']['was_tax'];
                $objects['repayments']['end_date'] = $rep->getEndDate();
                if ($rep->repaymentType->isClaim()) {
//                    $objects['repayments']['commission_money'] = number_format(round($objects['mDet']['od'] * 0.3), 2, '.', '');
                    $objects['repayments']['end_date'] = $rep->getEndDate();
                    if ($rep->repaymentType->isDopCommission()) {
                        $objects['repayments']['pc_and_exp_pc'] = StrUtils::kopToRub($rep->pc + $rep->exp_pc);
                    }
                }
                $objects['repayments']['months'] = $rep->created_at->diffInMonths($objects['repayments']['end_date']);
                $objects['repayment_types'] = $rep->repaymentType->toArray();
                $objects['repayment_types']['year_percent'] = number_format($repPercent * (365 + date('L')), 3, ',', '');
                $objects['repayment_types']['percent365'] = number_format($repPercent * 365, 3, ',', '');
                $objects['repayment_types']['percent366'] = number_format($repPercent * 366, 3, ',', '');
                $prevRep = Repayment::where('created_at', '<', $rep->created_at)->where('loan_id', $loan->id)->orderBy('created_at', 'desc')->first();

                if ($rep->repaymentType->isDopCommission()) {
                    $html = $this->replaceDopCommissionEndPoints($html, $loan, $rep, $prevRep);
                }

                $objects['prev_contract'] = [];
                if (!is_null($prevRep)) {
                    if ($prevRep->repaymentType->isSUZ()) {
                        $prevRep = Repayment::where('created_at', '<', $prevRep->created_at)
                                ->where('loan_id', $loan->id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                    }
                    if (!is_null($prevRep) && $prevRep->repaymentType->isClaim() && !$prevRep->isActive() && !$prevRep->repaymentType->isDopCommission()) {
                        $prevRep = Repayment::where('created_at', '<', $prevRep->created_at)
                                ->where('loan_id', $loan->id)
                                ->orderBy('created_at', 'desc')
                                ->first();
                    }
                    if (!is_null($prevRep)) {
                        $prevContractDate = new Carbon($prevRep->created_at);
                        $prevContractTime = $prevRep->time;
                        $prevRepExpPercent = $prevRep->repaymentType->exp_percent;
                        $prevContractEndDate = with(new Carbon($prevRep->created_at))->addDays($prevContractTime)->setTime(0, 0, 0);
                    }
                }
                if (is_null($prevRep)) {
                    $prevContractDate = new Carbon($loan->created_at);
                    $prevContractTime = $loan->time;
                    $prevRepExpPercent = with($loan->getPercents())['exp_pc'];
                    $prevContractEndDate = with(new Carbon($loan->created_at))->addDays($prevContractTime)->setTime(0, 0, 0);
                }
                $objects['prev_contract']['exp_start_date'] = with(new Carbon($prevContractEndDate))->addDay();
                //затычка для просроченных допников, чтобы дата начала просрочки не была больше даты создания документа
                if ($objects['prev_contract']['exp_start_date']->gt($rep->created_at)) {
                    $objects['prev_contract']['exp_start_date'] = $rep->created_at;
                }
                $objects['prev_contract']['exp_end_date'] = with(new Carbon($objects['prev_contract']['exp_start_date']))->addDays($prevContractTime);
                $objects['prev_contract']['start_date'] = $prevContractDate;
                $objects['prev_contract']['end_date'] = $prevContractEndDate;
                if ($objects['prev_contract']['end_date']->gt($rep->created_at)) {
                    $objects['prev_contract']['end_date'] = $rep->created_at;
                }
                $objects['prev_contract']['time'] = $prevContractTime;
                $diffInDays = $prevContractDate->diffInDays($rep->created_at);
//                    $repTime = $rep->time-(with(new Carbon($rep->created_at))->setTime(0,0,0)->lte($prevContractEndDate))?(with(new Carbon($rep->created_at))->setTime(0,0,0)->diffInDays($prevContractEndDate)):0;
                $repTime = $rep->time;
                $objects['repayments']['end_date2'] = with(new Carbon($rep->created_at))->setTime(0, 0, 0)->addDays($repTime);
                if ($rep->created_at->gt($prevContractEndDate)) {
                    $objects['repayments']['start_date'] = $rep->created_at;
                    if ($rep->repaymentType->text_id == config('options.rtype_dopnik3')) {
                        $objects['repayments']['start_date']->addDay();
                        $objects['repayments']['end_date2']->addDay();
                    }
                } else {
//                    $objects['repayments']['start_date'] = with(new Carbon($prevContractDate))->addDays($prevContractTime + 1)->setTime(0, 0, 0);
                    $objects['repayments']['start_date'] = $rep->created_at->copy()->addDay();
                    if ($rep->repaymentType->text_id == config('options.rtype_dopnik3')) {
                        $objects['repayments']['end_date2'] = with(new Carbon($rep->created_at))->setTime(0, 0, 0)->addDays($repTime + 1)->setTime(0, 0, 0);
                    } else {
                        $objects['repayments']['end_date2'] = with(new Carbon($rep->created_at))->setTime(0, 0, 0)->addDays($repTime)->setTime(0, 0, 0);
                    }
                }
                $diffInDays2 = $prevContractDate->diffInDays($objects['repayments']['start_date']);
                $objects['repayments']['was_exp_pc_days'] = ($diffInDays > $prevContractTime) ? ($diffInDays - $prevContractTime) : 0;
                $objects['repayments']['was_pc_days'] = ($diffInDays >= $prevContractTime) ? $prevContractTime : ($prevContractTime - $diffInDays);
                $objects['repayments']['prev_exp_percent'] = $prevRepExpPercent;

                if ($rep->repaymentType->isDopnik()) {
//                        $dopDays = $objects['repayments']['time'] + (($diffInDays > $prevContractTime) ? ($prevContractTime) : (-$diffInDays));
                    $dopDays = $objects['repayments']['start_date']->diffInDays($objects['repayments']['end_date2']);
                    if (with(new Carbon($prevContractDate))->addDays($prevContractTime)->setTime(0, 0, 0)->eq($prevContractEndDate)) {
                        $dopDays = $objects['repayments']['time'];
                    }
                    if ($dopDays > 30) {
                        $dopDays = 30;
                    }

                    $objects['repayments']['end_pc'] = StrUtils::kopToRub(($mDet->od) * ($repPercent / 100) * $dopDays);
                    $objects['repayments']['end_od_and_pc'] = StrUtils::kopToRub(($mDet->od) + $objects['repayments']['end_pc'] * 100);
                    $objects['repayments']['pc_and_exp_pc'] = StrUtils::kopToRub($rep->was_pc + $rep->was_exp_pc);
                    $objects['repayments']['end_pc_after_exp'] = StrUtils::kopToRub(($mDet->od) * ($loanrate->pc / 100) * $dopDays);
//                        $objects['repayments']['end_pc_after_exp'] = $dopDays;
                    $objects['repayments']['end_od_and_pc_after_exp'] = StrUtils::kopToRub(($mDet->od) + $objects['repayments']['end_pc_after_exp'] * 100);
                    $objects['loantypes']['pc_after_exp'] = $loanrate->pc;
                    $objects['repayments']['od_and_pc'] = ($mDet->od / 100) * ($loanrate->pc / 100) * $dopDays;
                    $moneyAndPercentsAfterExpWithDopDays = ($mDet->od / 100) + $objects['repayments']['od_and_pc'];
                    $moneyPercentsAfterExpWithDopDays = $objects['repayments']['od_and_pc'];
                    $html = str_replace('{{moneyAndPercentsAfterExpWithDopDays}}', number_format($moneyAndPercentsAfterExpWithDopDays, 2, ".", ""), $html);
                    $html = str_replace('{{num2str(moneyAndPercentsAfterExpWithDopDays)}}', StrUtils::num2str($moneyAndPercentsAfterExpWithDopDays, true), $html);
                    $html = str_replace('{{moneyPercentsAfterExpWithDopDays}}', number_format($moneyPercentsAfterExpWithDopDays, 2, ".", ""), $html);
                    $html = str_replace('{{num2str(moneyPercentsAfterExpWithDopDays)}}', StrUtils::num2str($moneyPercentsAfterExpWithDopDays, true), $html);
                    $objects['loans']['time_with_dop_days'] = with(new Carbon($loan->created_at))->diffInDays($objects['repayments']['end_date2']) + 1;
//                        $objects['loans']['time_with_dop_days'] = $objects['repayments']['start_date']
//                                        ->diffInDays($objects['repayments']['end_date2']) + (($rep->repaymentType->text_id == config('options.rtype_dopnik3')) ? 1 : 0);
//                                        ->diffInDays($objects['repayments']['end_date2']) + 1;
                    $objects['repayments']['print_time'] = $objects['loans']['time_with_dop_days'];
                    if ($rep->repaymentType->text_id == config('options.rtype_dopnik3')) {
                        $objects['repayments']['end_date2']->subDay();
                    }
                    $objects['repayments']['year_percent'] = number_format($repPercent * (365 + date("L")), 3, ".", "");
                    $objects['repayment_types']['year_percent365'] = number_format($repPercent * 365, 3, ".", "");
                    $objects['repayment_types']['year_percent366'] = number_format($repPercent * 366, 3, ".", "");
                }
                if ($rep->repaymentType->isDopCommission() || $rep->repaymentType->isClosing()) {
                    if (is_null($prevRep)) {
                        $dopComCalc = new \App\Utils\DopCommissionCalculation($loan, $rep->time, $mDet->od);
                    } else {
                        if ($rep->created_at->isToday()) {
                            $dopComCalc = new \App\Utils\DopCommissionCalculation($loan, $rep->time, $rep->od, $prevRep);
                        } else {
                            $dopComCalc = new \App\Utils\DopCommissionCalculation($loan, $rep->time, $rep->od, $prevRep, $rep->created_at);
                        }
                    }
                    if ($rep->created_at->lt(new Carbon('2017-01-30'))) {
                        $objects['repayments']['commission_money'] = StrUtils::kopToRub(round($mDet->od * 0.3));
                    } else {
                        $objects['repayments']['commission_money'] = StrUtils::kopToRub($dopComCalc->money_to_pay);
                    }
                    $objects['repayments']['dop_commission_cashback'] = StrUtils::kopToRub($dopComCalc->money_to_return);
                    $dopComOrders = $rep->getDopCommissionOrders();
                    $objects['repayments']['order_number_commission'] = (is_null($dopComOrders['commission'])) ? '' : $dopComOrders['commission']->number;
                    $objects['repayments']['order_number_nds'] = (is_null($dopComOrders['nds'])) ? '' : $dopComOrders['nds']->number;
                }
                if ($rep->repaymentType->isPeace()) {
                    $objects['repayments']['time'] = PeacePay::where('repayment_id', $rep->id)->count();
                    $html = str_replace('{{peacepays_table}}', $this->generatePeacePaysTable($objects['repayments']['id'], $mDet), $html);
                    if ($rep->repaymentType->text_id == config('options.rtype_peace4')) {
                        $epp = PeacePay::where('repayment_id', $rep->id)->orderBy('end_date', 'desc')->first();
                        if (!is_null($epp)) {
                            $objects['repayments']['end_date'] = $epp->end_date;
                        }
                    }
                    if (!is_null($rep->data)) {
                        $repData = $rep->getData(true, true);
                        if (is_array($repData) && count($repData) > 0) {
                            $objects['repayments'] = array_merge($objects['repayments'], $repData);
                        }
                    }
                }
                if ($rep->repaymentType->isSuzStock()) {
                    $html = $this->updateSuzStock($rep, Repayment::where('id', '<>', $rep->id)->where('loan_id', $rep->loan_id)->orderBy('created_at', 'desc')->first(), $html);
                }
                if ($rep->repaymentType->isSUZ()) {
                    $html = $this->updateSuz($rep, $html);
                    if (!is_null($rep->getData())) {
                        $repData = $rep->getData();
                        $objects['repayments']['print_req_money'] = 0;
                        foreach ($repData as $rdk => $rdv) {
                            if (in_array($rdk, ['print_od', 'print_pc', 'print_exp_pc', 'print_fine', 'print_money', 'print_tax'])) {
                                $objects['repayments'][$rdk] = StrUtils::kopToRub($rdv);
                                $objects['repayments']['print_req_money'] += $objects['repayments'][$rdk];
                            } else {
                                $objects['repayments'][$rdk] = $rdv;
                            }
                        }
                        //временная заглушка, пока не откуда брать дату заключения соглашения о реструктуризации
                        if (isset($repData->stock_created_at) && $repData->stock_created_at != '') {
                            $objects['repayments']['created_at'] = with(new Carbon($repData->stock_created_at))->format('Y-m-d');
                        } else {
                            $objects['repayments']['created_at'] = Carbon::now()->format('Y-m-d');
                        }
                    }
                }

                foreach (['od', 'pc', 'exp_pc', 'fine', 'money', 'tax'] as $m) {
                    $objects['mDet'][$m] = StrUtils::kopToRub($mDet->{$m});
                }
                foreach (['exp_days', 'pc_days', 'all_days'] as $d) {
                    $objects['mDet'][$d] = $mDet->{$d};
                }
                $objects['repayments']['end_date_money'] = StrUtils::kopToRub($rep->req_money + round(($rep->od + $rep->exp_pc + $rep->pc) * ($repPercent / 100)) * $repTime);
            }
        } else {
            $objects['loans'] = null;
        }
        if (!array_key_exists('users', $objects)) {
            $objects['users'] = Auth::user()->toArray();
        }
        if (!array_key_exists('subdivisions', $objects)) {
            $objects['subdivisions'] = \App\Subdivision::find($objects['users']['subdivision_id'])->toArray();
        }
        //если распечатываем уведомление, то всегда подставлять процент из кредитника или спецпроцент
        if (strstr($contract->text_id, 'pc_notification') !== FALSE && array_key_exists('loantypes', $objects) && isset($loan) && !is_null($loan)) {
            $objects['loantypes']['percent'] = (!is_null($loan->special_percent) && $loan->special_percent > 0) ? $loan->special_percent : $loan->loantype->percent;
        }
        //проходим по всем полям всех объектов и заменяем метки типа имя_таблицы.имя_столбца на соответствующие значения
        $html = ContractEditorController::processParams($objects, $html);

        //хак: проверяем на повторное заполнение анкеты, если повторно, то меняем надпись "Дата заполнения анкеты" на "Дата обновления анкеты"
        $date_type = 'заполнения';
        if (count(Claim::where('customer_id', $objects['customers']['id'])->get()) > 1) {
            $date_type = 'обновления';
        }
        $html = str_replace('{{date_type}}', $date_type, $html);
        $html = str_replace('{{full_address}}', $this->getFullAddress($objects['passports']), $html);
        $html = str_replace('{{full_fact_address}}', $this->getFullAddress($objects['passports'], true), $html);

        if (!is_null($objects['loans'])) {
            $money = (int) $objects['loans']['money'];
            $loanPC = $loan->getPercents();
            Log::info('loanpc', $loanPC);
            $percents = $objects['loantypes']['percent'];
            //если распечатываем уведомление, то всегда подставлять процент из кредитника или спецпроцент
            if (strstr($contract->text_id, 'pc_notification') !== FALSE) {
                $percents = (!is_null($loan->special_percent) && $loan->special_percent > 0) ? $loan->special_percent : $loan->loantype->percent;
            }
            //если в виде займа стоит галочка "Базовый", то процент расчитывается 
            //здесь, если не стоит то процент берется из вида займа
            if ($objects['loantypes']['basic'] == 1) {
                $brate = config('options.basic_rate');
                foreach ($brate as $r) {
                    if ($money >= $r['min'] && $money < $r['max']) {
                        $percents = $r['percent'];
                    }
                }
            }
            $time = (int) $objects['loans']['time'];
            $moneyPercents = $money * ($percents / 100) * $time;
            //поправка для займа под 0% (насчитываем проценты только до 23го дня
            if (($loan->loanType->id_1c == 'ARM000014' || $loan->loanType->id_1c == 'ARM000025') && $loan->time > 22) {
                $moneyPercents = $money * ($percents / 100) * 23;
            }
            $objects['loantypes']['pc_after_exp'] = $loanrate->pc;
            $moneyPercentsAfterExp = $money * ($objects['loantypes']['pc_after_exp'] / 100) * $time;

            $yearPercent = $percents * (365 + date("L"));
            $yearPercent365 = $percents * 365;
            $yearPercent366 = $percents * 366;
            $yearPercentAfterExp = $objects['loantypes']['pc_after_exp'] * (365 + date("L"));
            $yearPercent365AfterExp = $objects['loantypes']['pc_after_exp'] * 365;
            $yearPercent366AfterExp = $objects['loantypes']['pc_after_exp'] * 366;

            $html = str_replace('{{moneyAndPercents}}', $money + $moneyPercents, $html);
            $html = str_replace('{{num2str(moneyAndPercents)}}', StrUtils::num2str($money + $moneyPercents), $html);
            $html = str_replace('{{moneyPercents}}', $moneyPercents, $html);
            $html = str_replace('{{num2str(moneyPercents)}}', StrUtils::num2str($moneyPercents), $html);

            $html = str_replace('{{yearPercent}}', (number_format($yearPercent, 3, ",", "")), $html);
            $html = str_replace('{{num2str(yearPercent)}}', StrUtils::percentsToStr(number_format($yearPercent, 3)), $html);
            $html = str_replace('{{yearPercent366}}', number_format($yearPercent366, 3, ",", ""), $html);
            $html = str_replace('{{num2str(yearPercent366)}}', StrUtils::percentsToStr(number_format($yearPercent366, 3)), $html);
            $html = str_replace('{{yearPercent365}}', number_format($yearPercent365, 3, ",", ""), $html);
            $html = str_replace('{{num2str(yearPercent365)}}', StrUtils::percentsToStr(number_format($yearPercent365, 3)), $html);

            $html = str_replace('{{moneyAndPercentsAfterExp}}', $money + $moneyPercentsAfterExp, $html);
            $html = str_replace('{{num2str(moneyAndPercentsAfterExp)}}', StrUtils::num2str($money + $moneyPercentsAfterExp), $html);
            $html = str_replace('{{moneyPercentsAfterExp}}', $moneyPercentsAfterExp, $html);
            $html = str_replace('{{num2str(moneyPercentsAfterExp)}}', StrUtils::num2str($moneyPercentsAfterExp), $html);

            $html = str_replace('{{yearPercentAfterExp}}', number_format($yearPercentAfterExp, 3, ",", ""), $html);
            $html = str_replace('{{num2str(yearPercentAfterExp)}}', StrUtils::percentsToStr(number_format($yearPercentAfterExp, 3)), $html);
            $html = str_replace('{{yearPercent366AfterExp}}', number_format($yearPercent366AfterExp, 3, ",", ""), $html);
            $html = str_replace('{{num2str(yearPercent366AfterExp)}}', StrUtils::percentsToStr(number_format($yearPercent366AfterExp, 3)), $html);
            $html = str_replace('{{yearPercent365AfterExp}}', number_format($yearPercent365AfterExp, 3, ",", ""), $html);
            $html = str_replace('{{num2str(yearPercent365AfterExp)}}', StrUtils::percentsToStr(number_format($yearPercent365AfterExp, 3)), $html);

            $html = str_replace('{{loanEndDate}}', date('d.m.Y', strtotime($objects['loans']['created_at'] . ' +' . $time . ' day')), $html);
            $html = str_replace('{{addDay(loanEndDate)}}', with(new Carbon($objects['loans']['created_at']))->addDays($time + 1)->format('d.m.Y'), $html);

            $html = str_replace('{{loantypes.percent}}', $percents, $html);
            $html = str_replace('{{num2str(loantypes.percent)}}', StrUtils::percentsToStr($percents), $html);
            $html = str_replace('{{loantypes.name}}', $objects['loantypes']['name'], $html);

            //вычисляем постоянного клиента или пенсионера
            $date1 = new DateTime($objects['passports']['birth_date']);
            $date2 = new DateTime();
            $years = $date1->diff($date2);
            $loansNum = DB::table('loans')
                    ->leftJoin('claims', 'loans.claim_id', '=', 'claims.id')
                    ->where('claims.customer_id', $objects['customers']['id'])
                    ->where('loans.closed', 1)
                    ->count();
            if (($years->y > config('options.man_retirement_age') && $objects['about_clients']['sex']) ||
                    ($years->y > config('options.woman_retirement_age') && !$objects['about_clients']['sex']) ||
                    $loansNum >= config('options.regular_client_loansnum') ||
                    $objects['about_clients']['postclient'] || $objects['about_clients']['pensioner']) {
                $expirationPercent = 'устанавливается в размере ' . (is_null($objects['loantypes']['exp_pc_perm'])) ? config('options.exp_percent_perm') : $objects['loantypes']['exp_pc_perm'];
                $finePercent = (is_null($objects['loantypes']['fine_pc_perm'])) ? config('options.fine_percent_perm') : $objects['loantypes']['fine_pc_perm'];
                /**
                 * убрать такие штуки в отдельную форму договора
                 */
                $paragraph6 = 'возврата';
            } else {
                $expirationPercent = 'увеличивается на ' . (is_null($objects['loantypes']['exp_pc'])) ? config('options.exp_percent') : $objects['loantypes']['exp_pc'];
                $finePercent = (is_null($objects['loantypes']['fine_pc'])) ? config('options.fine_percent') : $objects['loantypes']['fine_pc'];
                /**
                 * убрать такие штуки в отдельную форму договора
                 */
                $paragraph6 = 'выдачи';
            }
            $html = str_replace('{{paragraph6}}', $paragraph6, $html);
            $html = str_replace('{{expirationPercent}}', $expirationPercent, $html);
            $html = str_replace('{{finePercent}}', $finePercent, $html);
            $html = str_replace('{{generateGrid()}}', $this->generateGrid($objects['loans'], $objects['loantypes']), $html);
            $html = str_replace('{{today}}', Carbon::now()->format('d.m.Y'), $html);
            $html = str_replace('{{now}}', Carbon::now()->format('d.m.Y H:i'), $html);
        }
        $html = str_replace('{{' . 'date_type' . '}}', $date_type, $html);

        return $html;
    }

    function getFullAddress($passport, $fact = false) {
        $fields = ['region', 'district', 'city','city1', 'street', 'house', 'building', 'apartment'];
        $pfx = (($fact) ? 'fact_' : '') . 'address_';
        $zip = $passport[(($fact) ? 'fact_' : '') . 'zip'];
        $str = (!is_null($zip) && $zip != '') ? $zip : '';
        foreach ($fields as $f) {
            if (!is_null($passport[$pfx . $f]) && $passport[$pfx . $f] != '') {
                $str .= ($str != '') ? ', ' : '';
                $str .= ($f == 'house' && ctype_digit($passport[$pfx . $f])) ? 'д.' : '';
                $str .= ($f == 'building' && ctype_digit($passport[$pfx . $f])) ? 'стр.' : '';
                $str .= ($f == 'apartment' && ctype_digit($passport[$pfx . $f])) ? 'кв.' : '';
                $str .= ' ' . $passport[$pfx . $f];
            }
        }
        return $str;
    }

    static function getFullAddressString($passport, $fact = false) {
        $ctrl = new ContractEditorController();
        return $ctrl->getFullAddress($passport, $fact);
    }

    /**
     * Генерирует таблицу на печать для акции по сузу
     */
    function updateSuzStock($suzStock, $suz, $html) {
        $isNewSUZ = false;
//        if($suz->created_at->gte(new Carbon('2010-01-01')) && $suz->created_at->lte(new Carbon('2012-11-30'))){
//            $isNewSUZ = '<td>Пеня</td>';
//        } else if($suz->created_at->gte(new Carbon('2012-12-01'))){
//            $table .= '<td>Проценты</td>';
//        }
        if ($suz->created_at->gte(new Carbon('2012-12-01'))) {
            $isNewSUZ = true;
        }

        $pays = [];
        $table = '<table class="suzpays">';
        $table .= '<thead><tr>';
        $table .= '<td>Номер</td><td>Дата платежа</td><td>Сумма платежа</td><td>Сумма ОД</td><td>Срочные проценты</td>';
        if ($isNewSUZ) {
            $table .= '<td>Проценты</td>';
            $table .= '<td>Пеня</td>';
        } else {
            $table .= '<td>Пеня</td>';
        }
        $table .= '<td>Госпошлина</td>';
        $table .= '</tr></thead>';
        $i = 1;
        if (!is_null($suz->data)) {
            $suzdata = json_decode($suz->data);
            if (!is_null($suzdata) && isset($suzdata->pays) && !is_null($suzdata->pays)) {
                $total = [
                    'tax' => $suzStock->tax,
                    'fine' => $suzStock->fine,
                    'exp_pc' => $suzStock->exp_pc,
                    'pc' => $suzStock->pc,
                    'od' => $suzStock->od,
                ];

                foreach ($suzdata->pays as $p) {
                    $pay = [
                        'date' => with(new Carbon($p->date))->format('d.m.Y'),
                        'total' => $p->total,
                        'pc' => 0,
                        'exp_pc' => 0,
                        'od' => 0,
                        'fine' => 0,
                        'tax' => 0
                    ];
                    $money = $p->total * 100;
                    foreach ($total as $k => $v) {
                        if ($money > 0 && $total[$k] > 0) {
                            if ($money <= $total[$k]) {
                                $pay[$k] = $money / 100;
                                $total[$k] -= $money;
                                $money = 0;
                            } else {
                                $pay[$k] = $total[$k] / 100;
                                $money -= $total[$k];
                                $total[$k] = 0;
                            }
                        }
                        if ($money <= 0) {
                            break;
                        }
                    }
                    $pays[] = $pay;
                }
            }
            foreach ($pays as $p) {
                $table .= '<tr>';
                $table .= '<td>' . $i . '</td>';
                $table .= '<td>' . $p['date'] . '</td>';
                $table .= '<td>' . number_format($p['total'], 2, '.', '') . '</td>';
                $table .= '<td>' . number_format($p['od'], 2, '.', '') . '</td>';
                $table .= '<td>' . number_format($p['pc'], 2, '.', '') . '</td>';
                if ($isNewSUZ) {
                    $table .= '<td>' . number_format($p['exp_pc'], 2, '.', '') . '</td>';
                    if(array_key_exists('fine', $p)){
                        $table .= '<td>' . number_format($p['fine'], 2, '.', '') . '</td>';
                    } else {
                        $table .= '<td>0.00</td>';
                    }
                } else {
                    $table .= '<td>' . number_format($p['fine'], 2, '.', '') . '</td>';
                }
                $table .= '<td>' . number_format($p['tax'], 2, '.', '') . '</td>';
                $table .= '</tr>';
                $i++;
            }
        }
        $table.='</table>';
        $tax_label = ' рублей - сумма государственной пошлины.';
        $html = str_replace('{{suz_stock_pay_schedule}}', $table, $html);
        if (count($pays) > 0) {
            $html = str_replace('{{first_suz_pay_date}}', $pays[0]['date'], $html);
        }
        if (!is_null($suzStock->data)) {
            $suzStockData = json_decode($suzStock->data);
            if (isset($suzStockData->stockName) && $suzStockData->stockName == 'akcsuzst46') {
                $html = str_replace('{{suz_stock_was_tax}}', ((!is_null($suzStock->was_tax)) ? StrUtils::kopToRub($suzStock->was_tax) : '0.00') . $tax_label, $html);
                $html = str_replace('{{suz_stock_tax}}', StrUtils::kopToRub($suzStock->tax) . $tax_label, $html);
            }
        }
        return $html;
    }

    public function updateSuz($suz, $html) {
        $isNewSUZ = false;
        if ($suz->created_at->gte(new Carbon('2012-12-01'))) {
            $isNewSUZ = true;
        }

        $pays = [];
        $table = '<table class="suzpays">';
        $table .= '<thead><tr>';
        $table .= '<td>№</td><td>Дата платежа</td><td>Сумма платежа</td><td>Сумма ОД</td><td>Срочные проценты</td>';
        if ($isNewSUZ) {
            $table .= '<td>Проценты</td>';
            $table .= '<td>Пеня</td>';
        } else {
            $table .= '<td>Пеня</td>';
        }
        $table .= '<td>Госпошлина</td>';
        $table .= '</tr></thead>';
        $i = 1;
        if (!is_null($suz->data)) {
            $suzdata = $suz->getData();
            if (!is_null($suzdata) && isset($suzdata->pays) && !is_null($suzdata->pays)) {
                if($suzdata->stock_type==config('options.suz_arhiv_ub')){
                    $total = [
                        'exp_pc' => $suzdata->print_exp_pc,
                        'pc' => $suzdata->print_pc,
                        'od' => $suzdata->print_od,
                        'tax' => $suzdata->print_tax,
                        'fine' => $suzdata->print_fine,
                    ];
                } else {
                    $total = [
                        'tax' => $suzdata->print_tax,
                        'fine' => $suzdata->print_fine,
                        'exp_pc' => $suzdata->print_exp_pc,
                        'pc' => $suzdata->print_pc,
                        'od' => $suzdata->print_od,
                    ];
                }

                foreach ($suzdata->pays as $p) {
                    $pay = [
                        'date' => with(new Carbon($p->date))->format('d.m.Y'),
                        'total' => $p->total,
                        'pc' => 0,
                        'exp_pc' => 0,
                        'od' => 0,
                        'fine' => 0,
                        'tax' => 0
                    ];
                    $money = $p->total * 100;
                    foreach ($total as $k => $v) {
                        if ($money > 0 && $total[$k] > 0) {
                            if ($money <= $total[$k]) {
                                $pay[$k] = $money / 100;
                                $total[$k] -= $money;
                                $money = 0;
                            } else {
                                $pay[$k] = $total[$k] / 100;
                                $money -= $total[$k];
                                $total[$k] = 0;
                            }
                        }
                        if ($money <= 0) {
                            break;
                        }
                    }
                    $pays[] = $pay;
                }
            }
            foreach ($pays as $p) {
                $table .= '<tr>';
                $table .= '<td>' . $i . '</td>';
                $table .= '<td>' . $p['date'] . '</td>';
                $table .= '<td>' . number_format($p['total'], 2, '.', '') . '</td>';
                $table .= '<td>' . number_format($p['od'], 2, '.', '') . '</td>';
                $table .= '<td>' . number_format($p['pc'], 2, '.', '') . '</td>';
                if ($isNewSUZ) {
                    $table .= '<td>' . number_format($p['exp_pc'], 2, '.', '') . '</td>';
                    if(array_key_exists('fine', $p)){
                        $table .= '<td>' . number_format($p['fine'], 2, '.', '') . '</td>';
                    } else {
                        $table .= '<td>0.00</td>';
                    }
                } else {
                    $table .= '<td>' . number_format($p['fine'], 2, '.', '') . '</td>';
                }
                $table .= '<td>' . number_format($p['tax'], 2, '.', '') . '</td>';
                $table .= '</tr>';
                $i++;
            }
        }
        $table.='</table>';
        $tax_label = ' рублей - сумма государственной пошлины.';
        $html = str_replace('{{suz_stock_pay_schedule}}', $table, $html);
        if (count($pays) > 0) {
            $html = str_replace('{{first_suz_pay_date}}', $pays[0]['date'], $html);
        }
        if (!is_null($suzdata)) {
            $html = str_replace('{{suz_stock_was_tax}}', ((!is_null($suz->was_tax)) ? StrUtils::kopToRub($suz->was_tax) : '0.00') . $tax_label, $html);
            $html = str_replace('{{suz_stock_tax}}', StrUtils::kopToRub($suzdata->print_tax) . $tax_label, $html);
        }
        return $html;
    }

    /**
     * Генерирует таблицу на печать платежей по мировому
     * @param type $repaymentID
     * @param type $mDet
     * @return string
     */
    function generatePeacePaysTable($repaymentID, $mDet) {
        $html = '';
        $rep = Repayment::find($repaymentID);
        $pays = \App\PeacePay::where('repayment_id', $repaymentID)->get();
        $i = 1;
//        if(!is_null($rep->data)){
//            $repData = json_decode($rep->data);
//            if(!is_null($repData) && isset($repData->create_pays)){
//                $total = [
//                    'pc' => $repData->create_pc*100 + $repData->create_exp_pc*100,
//                    'od' => $repData->create_od*100,
//                    'fine' => $repData->create_fine*100
//                ];
//                $pays = $repData->create_pays;
//                foreach($pays as $pay){
//                    $pay->total*=100;
//                }
//            }
//        } else {
            $total = [
                'pc' => $mDet->pc + $mDet->exp_pc,
                'od' => $mDet->od,
                'fine' => $mDet->fine
            ];
//        }

        foreach ($pays as $pay) {
//            $pay = $pays[$j];
            $res = [
                'pc' => 0,
                'od' => 0,
                'fine' => 0
            ];
            $money = $pay->total;
            foreach ($total as $k => $v) {
                if ($money > 0 && $total[$k] > 0) {
                    if ($money <= $total[$k]) {
                        $res[$k] = $money;
                        $total[$k] -= $money;
                        $money = 0;
                    } else {
                        $res[$k] = $total[$k];
                        $money -= $total[$k];
                        $total[$k] = 0;
                    }
                }
                if ($money <= 0) {
                    break;
                }
            }
            if ($rep->repaymentType->text_id != config('options.rtype_peace')) {
                $html .= '<tr class="R45">'
                        . '<td class="R60C0"><SPAN STYLE="white-space:nowrap">' . $i . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=2><SPAN STYLE="white-space:nowrap">' . (with(new Carbon($pay->end_date))->format('d.m.Y')) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=2><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($pay->total) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=3><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($res['pc']) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=3><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($res['od']) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=3><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($res['fine']) . '</SPAN></TD>'
                        . '<TD></TD>'
                        . '</tr>';
            } else {
                $html .= '<tr class="R45">'
                        . '<td class="R60C0"><SPAN STYLE="white-space:nowrap">' . $i . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=2><SPAN STYLE="white-space:nowrap">' . (with(new Carbon($pay->end_date))->format('d.m.Y')) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=2><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($pay->total) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=3><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($pay->fine) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=3><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($pay->exp_pc) . '</SPAN></TD>'
                        . '<TD CLASS=R60C0 COLSPAN=3><SPAN STYLE="white-space:nowrap">' . StrUtils::kopToRub($pay->money) . '</SPAN></TD>'
                        . '<TD></TD>'
                        . '</tr>';
            }
            $i++;
        }
        return $html;
    }

    function generateGrid($loan, $loantype) {
        $html = '<table>';
        $loandb = Loan::find($loan['id']);
        $pc = with($loandb->getLoanRate())->pc;

        $moneyDefNum = (int) config('options.grid_def_money') / 1000;
        $moneyNum = ((int) $loan['money'] / 1000 < $moneyDefNum) ? $moneyDefNum : (int) $loan['money'] / 1000;
        $yearPercent = $pc * 365;
        $html .= '<thead><tr rowspan="2"><th style="font-size:8pt">Срок возврата ' . $loantype['name'] . ' (дни)</th><th>Полная стоимость займа*</th><th colspan="' . ($moneyNum) . '">Сумма займа (рубли)</th></tr>';
        $html .= '<tr><th></th><th></th>';
        for ($m = 1; $m <= $moneyNum; $m++) {
            $html .= '<th>' . ($m * 1000) . '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';

        $time = ($loan['time'] < config('options.grid_def_time')) ? config('options.grid_def_time') : $loan['time'];
        for ($t = 1; $t <= $time; $t++) {
            $html .= '<tr><td>' . $t . '</td><td>' . number_format($yearPercent,3,',','') . '%</td>';
            for ($m = 1; $m <= $moneyNum; $m++) {
                $html .= '<td>' . ((($pc * ($m * 1000)) / 100) * $t + ($m * 1000)) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    static function processParams($objects, $html) {
        foreach ($objects as $objName => $obj) {
            if (!is_array($obj)) {
                continue;
            }
            foreach ($obj as $col => $v) {
                if (is_array($v)) {
                    continue;
                }
                //хак: форматируем дату в случае столбца с датой
                $dates_cols = [
                    'date', 'birth_date', 'issued_date', 'address_reg_date',
                    'created_at', 'updated_at', 'end_date', 'payday', 'end_date2',
                    'start_date', 'exp_start_date', 'exp_end_date', 'first_loan_date',
                    'close_date'
                ];
                if (strstr($html, '{{char(' . $objName . '.' . $col) !== FALSE) {
                    $vv = (string) $v;
                    if (in_array($col, $dates_cols)) {
                        $vv = with(new Carbon($vv))->format('dmY');
                    }
                    if ($col == 'snils') {
                        $vv = StrUtils::removeNonDigits($vv);
                    }
                    $vLen = strlen($vv);
                    for ($i = 0; $i < $vLen; $i++) {
                        if (strstr($html, '{{char(' . $objName . '.' . $col . ',' . $i . ')}}') !== FALSE) {
                            $html = str_replace('{{char(' . $objName . '.' . $col . ',' . $i . ')}}', substr((string) $vv, $i, 1), $html);
                        }
                    }
                }
                if (in_array($col, $dates_cols)) {
                    if (!is_null($v)) {
                        while (strstr($html, '{{addDays(' . $objName . '.' . $col . ',') !== FALSE) {
                            $days = ContractEditorController::get_string_between($html, '{{addDays(' . $objName . '.' . $col . ',', ')}}');
                            $html = str_replace('{{addDays(' . $objName . '.' . $col . ',' . $days . ')}}', with(new Carbon($v))->addDays($days)->format('d.m.Y'), $html);
                        }
                        while (strstr($html, '{{addMonths(' . $objName . '.' . $col . ',') !== FALSE) {
                            $months = ContractEditorController::get_string_between($html, '{{addMonths(' . $objName . '.' . $col . ',', ')}}');
                            $html = str_replace('{{addMonths(' . $objName . '.' . $col . ',' . $months . ')}}', with(new Carbon($v))->addMonths($months)->format('d.m.Y'), $html);
                        }
                    }
                    $v = with(new Carbon($v))->format('d.m.Y');
                }
                //хак: пока не настроится нормальная модель связки полей
                if ($col == 'zhusl') {
                    $liveCond = \App\LiveCondition::find($v);
                    $v = (!is_null($liveCond)) ? $liveCond->name : '';
                }
                if ($col == 'adsource') {
                    $adsource = \App\AdSource::find($v);
                    $v = (!is_null($adsource)) ? $adsource->name : '';
                }
                if ($col == 'vidtruda') {
                    $v = ((bool) $v) ? 'Официально' : 'Не официально';
                }
                if ($col == 'goal') {
                    $goal = \App\LoanGoal::find($v);
                    $v = (!is_null($goal)) ? $goal->name : '';
                }
                if ($col == 'avto') {
                    $avto = ['нет', 'отечественный', 'иномарка'];
                    $v = (array_key_exists($v, $avto)) ? $avto[$v] : '';
                }
                if ($objName == 'orders' && $col == 'money') {
                    $v = ((int) $v) / 100;
                    if (floor($v) == $v) {
                        $v .= '.00';
                    }
                }
                if ($col == 'sex') {
                    $v = ($v == '0') ? 'Женский' : 'Мужской';
                }
                if (in_array($col, ['name', 'fio'])) {
                    $fio = explode(' ', $v);
                    //убираем все пустые элементы, получившиеся если были лишние пробелы
                    foreach ($fio as $itemK => $itemV) {
                        if ($itemV == '') {
                            unset($fio[$itemK]);
                        }
                    }
                    //обновляем индексы в массиве
                    $fio = array_values($fio);
                    $fioLength = count($fio);
                    if ($fioLength > 2) {
                        if (array_key_exists('about_clients', $objects) && array_key_exists('sex', $objects['about_clients'])) {
                            $gender = ($objects['about_clients']['sex'] == '1') ? Petrovich::GENDER_MALE : Petrovich::GENDER_FEMALE;
                        } else {
                            $gender = Petrovich::GENDER_ANDROGYNOUS;
                        }
                        for ($case = Petrovich::CASE_NOMENATIVE; $case <= Petrovich::CASE_PREPOSITIONAL; $case++) {
                            $p = new Petrovich($gender);
                            $gender = $p->detectGender($fio[2]);
                            if (count($fio) > 3) {
                                $casedFIO = $p->lastname($fio[0], $case) . ' ' . $fio[3] . ' ' . $p->firstname($fio[1], $case) . ' ' . $p->middlename($fio[2], $case);
                            } else {
                                $casedFIO = $p->lastname($fio[0], $case) . ' ' . $p->firstname($fio[1], $case) . ' ' . $p->middlename($fio[2], $case);
                            }
                            $html = str_replace('{{case(' . $objName . '.' . $col . ',' . $case . ')}}', $casedFIO, $html);
                        }
                        $html = str_replace('{{' . $objName . '.first_name}}', $fio[1], $html);
                        $middlename = '';
                        if ($fioLength > 3) {
                            for ($i = 2; $i < $fioLength; $i++) {
                                $middlename.=$fio[$i];
                            }
                        } else {
                            $middlename = $fio[2];
                        }
                        $html = str_replace('{{' . $objName . '.middle_name}}', $middlename, $html);
                        $html = str_replace('{{' . $objName . '.last_name}}', $fio[0], $html);
                    } else if ($fioLength == 2) {
                        $html = str_replace('{{' . $objName . '.first_name}}', $fio[1], $html);
                        $html = str_replace('{{' . $objName . '.last_name}}', $fio[0], $html);
                    }
                }
                if ($col == 'telephone') {
                    $html = str_replace('{{' . $objName . '.' . $col . '}}', '+' . (string) $v, $html);
                }

                if (strstr($html, '{{celled(' . $objName . '.' . $col . ')}}') !== FALSE) {
                    $arrayItem = str_split($v);
                    $celledItem = '';
                    foreach ($arrayItem as $character) {
                        $celledItem .= '<span class="cell">' . $character . '</span>';
                    }
                    $html = str_replace('{{celled(' . $objName . '.' . $col . ')}}', $celledItem, $html);
                }


                $num2str_cols = [
                    'money', 'summa', 'srok', 'time', 'od', 'pc', 'exp_pc', 'fine',
                    'was_req_money', 'req_money', 'prev_exp_percent', 'time_with_dop_days', 'was_od',
                    'end_pc', 'end_od_and_pc', 'print_time', 'pc_and_exp_pc',
                    'end_pc_after_exp', 'end_od_and_pc_after_exp', 'tax', 'was_tax', 'commission_money', 'dop_commission_cashback',
                    'create_pc', 'create_exp_pc', 'create_od', 'create_fine'
                ];
                $money_cols = [
                    'money', 'summa', 'od', 'pc', 'exp_pc', 'fine',
                    'was_req_money', 'req_money', 'prev_exp_percent', 'was_od',
                    'end_pc', 'end_od_and_pc', 'pc_and_exp_pc', 'end_pc_after_exp',
                    'end_od_and_pc_after_exp', 'tax', 'was_tax', 'commission_money', 'dop_commission_cashback',
                    'create_pc', 'create_exp_pc', 'create_od', 'create_fine'
                ];
                $html = str_replace('{{' . $objName . '.' . $col . '}}', (string) $v, $html);

//                $html = str_replace('{{about_clients.sex}}', 'Мужской', $html);

                if (in_array($col, $num2str_cols) && !is_null($v)) {
                    $html = str_replace('{{num2str(' . $objName . '.' . $col . ')}}', StrUtils::num2str($v), $html);
                }
                if (in_array($col, $num2str_cols) && !is_null($v)) {
                    $html = str_replace('{{num2str(' . $objName . '.' . $col . ',true)}}', StrUtils::num2str($v, true), $html);
                }
                $pc2str_cols = [
                    'percent', 'pc_after_exp', 'exp_pc', 'exp_pc_perm', 'fine_pc', 'fine_pc_perm', 'special_pc'
                ];
                if (in_array($col, $pc2str_cols) && !is_null($v)) {
                    $html = str_replace('{{num2str(' . $objName . '.' . $col . ')}}', StrUtils::percentsToStr($v), $html);
                }
                if (in_array($col, $money_cols) && !is_null($v)) {
                    $html = str_replace('{{rubkop(' . $objName . '.' . $col . ')}}', StrUtils::sumToRubAndKop($v), $html);
                }
                if (in_array($col, $pc2str_cols) && !is_null($v)) {
                    $html = str_replace('{{num2str(' . $objName . '.' . $col . ',true)}}', StrUtils::percentsToStr($v), $html);
                }
                if (in_array($col, ['created_at', 'updated_at', 'date', 'birth_date']) && strtotime($v)) {
                    $html = str_replace('{{addYear(' . $objName . '.' . $col . ')}}', with(with(new Carbon($v))->addYear())->format('d.m.Y'), $html);
                }
            }
        }
        return $html;
    }

    static function get_string_between($string, $start, $end) {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    static function clearTags($html, $replace_sign = '') {
        return preg_replace('/\{\{(.*?)\}\}/s', $replace_sign, $html);
    }

    public function openPdfGen(Request $req) {
        return view('contracteditor.pdfgen')->with('html', $req->html);
    }

    public function makePdf(Request $req) {
        return \App\Utils\PdfUtil::getPdf($req->html, json_decode($req->opts, true));
    }

    /**
     * Костыль для замены строк о кратном превышении в допниках
     * @param string $html
     * @param \App\Loan $loan
     * @param \App\Repayment $rep
     * @return string
     */
    public function replaceMultiplierText($html, $loan, $rep) {
        $temp_html = $html;
        $newRulesDay = new Carbon(config('options.new_rules_day'));
        $newRulesDay010117 = new Carbon(config('options.new_rules_day_010117'));
        if ($loan->created_at->gte($newRulesDay)) {
            if ($loan->created_at->gte($newRulesDay010117)) {
                $temp_html = str_replace(
                        '{{multiplier_3_1}}', 'Проценты, начисляемые по Договору потребительского займа 
                        №{{loans.id_1c}} от {{loans.created_at}}г. не могут превышать трехкратный размер суммы займа.
                        После возникновения просрочки по возврату суммы займа и причитающихся процентов, 
                        проценты на не погашенную заемщиком часть суммы основного долга продолжают 
                        начисляться до достижения общей суммы подлежащих уплате процентов размера, 
                        составляющего двукратную сумму непогашенной части займа. Проценты не 
                        начисляются за период времени с момента достижения общей суммы подлежащих 
                        уплате процентов размера, составляющего двукратную сумму непогашенной части 
                        займа, до момента частичного погашения заемщиком суммы займа и (или) уплаты 
                        причитающихся процентов. После возникновения просрочки по возврату суммы займа 
                        и причитающихся процентов начисление неустойки (штрафа, пени) и иные меры 
                        ответственности только на не погашенную заемщиком часть суммы основного долга.', $temp_html
                );
                $temp_html = str_replace('{{multiplier_3_2}}', ', когда сумма начисленных процентов 
                        достигнет трехкратного размера суммы займа. Со следующего дня после даты 
                        достижения вышеуказанного размера начисление процентов прекращается', $temp_html);
            } else {
                $temp_html = str_replace(
                        ['{{multiplier_4_1}}', '{{multiplier_3_1}}'], 'Проценты, начисляемые по Договору потребительского займа №{{loans.id_1c}} от 
                        {{loans.created_at}}г. не могут превышать четырехкратный размер суммы займа.', $temp_html
                );
                $temp_html = str_replace(['{{multiplier_4_2}}', '{{multiplier_3_2}}'], ', когда 
                        сумма начисленных процентов достигнет четырехкратного размера суммы займа. 
                        Со следующего дня после даты достижения вышеуказанного размера начисление 
                        процентов прекращается', $temp_html);
                $temp_html = str_replace('{{multiplier_4_3}}', 'Проценты, начисляемые по Договору потребительского займа №{{loans.id_1c}} от 
                        {{loans.created_at}}г. не могут превышать четырехкратный размер суммы займа, 
                        акцептованной Заемщиком в порядке, предусмотренном Индивидуальными 
                        условиями договором потребительского займа (за  исключением пени).', $temp_html);
            }
        }
        return $temp_html;
    }

    /**
     * замена последних пунктов в допниках с комиссией
     * @param string $html
     * @param \App\Loan $loan
     * @param \App\Repayment $rep
     * @param \App\Repayment $prevRep
     * @return string
     */
    public function replaceDopCommissionEndPoints($html, $loan, $rep, $prevRep = null) {
        $temp_html = $html;
        if (is_null($prevRep)) {
            $is_overdued = ($rep->created_at->gt($loan->getEndDate()));
        } else {
            $is_overdued = ($rep->created_at->gt($prevRep->getEndDate()->addDay()));
        }
        $overduePointText = '4. Настоящее дополнительное соглашение распространяет свое действие на отношения, возникшие с {{prev_contract.exp_start_date}}<br>';
        $preLastPointText = '. В остальных вопросах Стороны руководствуются условиями Договора потребительского займа № {{loans.id_1c}} от {{loans.created_at}}&nbsp;г.<br />';
        $lastPointText = '. Адреса, реквизиты и подписи Сторон:<br>';
        if ($is_overdued) {
            $temp_html = str_replace('{{dop_commission_end_points}}', $overduePointText . '5' . $preLastPointText . '6' . $lastPointText, $temp_html);
        } else {
            $temp_html = str_replace('{{dop_commission_end_points}}', '4' . $preLastPointText . '5' . $lastPointText, $temp_html);
        }
        return $temp_html;
    }

}
