<?php

namespace App\Http\Controllers;


use App\DebtorEvent;
use App\Http\Requests\StartCourtTaskRequest;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Debtor;
use App\Passport;
use App\DebtorNotice;
use App\User;
use App\NoticeNumbers;
use App\Loan;
use App\StrUtils;
use App\CourtOrder;
use App\CourtOrderTasks;
use App\Services\PdfService;
use App\Utils\PdfUtil;
use App\Export\Excel\CourtExport;
use App\Export\Excel\NoticeExport;

class DebtorsNoticesController extends Controller {

    public function __construct(PdfService $pdfService) {
        $this->middleware('auth');
        $this->pdfService = $pdfService;
    }

    public function index(Request $request) {
        $user = auth()->user();

        if ($user->hasRole('debtors_remote')) {
            $struct_subdivision = '000000000006';
        } else if ($user->hasRole('debtors_personal')) {
            $struct_subdivision = '000000000007';
        } else {
            $struct_subdivision = false;
        }

        $taskInProgress = false;

        if ($struct_subdivision) {
            $noticesTask = DebtorNotice::where('in_progress', 1)->where('struct_subdivision', $struct_subdivision)->first();
            $taskInProgress = (!is_null($noticesTask)) ? true : false;
        }

        $tasks = DebtorNotice::where('struct_subdivision', $struct_subdivision)
                ->orderBy('created_at', 'desc')
                ->get();

        return view('debtors.notices.index', [
            'user' => $user,
            'taskInProgress' => $taskInProgress,
            'tasks' => $tasks
        ]);
    }

    public function startTask(Request $request) {
        $input = $request->input();
        $user = auth()->user();

        if ($user->hasRole('debtors_remote')) {
            $str_podr = '000000000006';
        } else if ($user->hasRole('debtors_personal')) {
            $str_podr = '000000000007';
        } else {
            $str_podr = false;
        }

        if (!$str_podr) {
            return redirect()->back();
        }

        $noticesTask = new DebtorNotice();
        $noticesTask->struct_subdivision = $str_podr;
        $noticesTask->in_progress = 1;
        $noticesTask->completed = 0;
        $noticesTask->save();

        $fixationDateFrom = !empty($input['fixation_date_from']) ?
            Carbon::parse($input['fixation_date_from'])->startOfDay() : null;
        $fixationDateTo = !empty($input['fixation_date_to']) ?
            Carbon::parse($input['fixation_date_to'])->endOfDay() : null;

        $respUsers = User::whereIn('id',$input['responsible_users_ids'])->get()->pluck('id_1c')->toArray();

        $bases = null;
        if (!empty($input['debt_base_ids'])) {
            foreach ($input['debt_base_ids'] as $debt_base_id) {
                $bases[] = config('debtors.debt_bases')[$debt_base_id];
            }
        }

        $debtors = Debtor::where('is_debtor', 1)
            ->byFixation($fixationDateFrom, $fixationDateTo)
            ->whereIn('responsible_user_id_1c', $respUsers)
            ->whereIn('debt_group_id', $input['debt_group_ids']);

        if(!is_null($bases)) {
            $debtors->whereIn('base', $bases);
        }
        $debtors = $debtors->get();

        $massDir = storage_path() . '/app/public/postPdfTasks/' . $noticesTask->id . '/';

        if (!is_dir($massDir)) {
            mkdir(storage_path() . '/app/public/postPdfTasks/' . $noticesTask->id, 0777);
        }

        $this->createPdf($debtors, $input['address_type'], $noticesTask->id, $str_podr);

        $noticesTask->in_progress = 0;
        $noticesTask->completed = 1;
        $noticesTask->save();

        return redirect()->back();
    }

    public function createPdf($debtors, $address_type, $task_id, $str_podr) {
        $user = auth()->user();
        $i = 1;
        $rows = [];

        foreach ($debtors as $debtor) {

            $notice = NoticeNumbers::where('debtor_id_1c', $debtor->debtor_id_1c)
                    ->where('str_podr', $str_podr)
                    ->first();

            if (!is_null($notice)) {
                continue;
            }

            $arParams = [];

            $debtorData = Debtor::select(DB::raw('*, passports.id as d_passport_id, '
                                    . 'loans.id_1c as d_loan_id_1c, loans.created_at as d_loan_created_at, loans.time as d_loan_time, '
                                    . 'loans.tranche_number as d_loan_tranche_number, loans.first_loan_id_1c as d_loan_first_loan_id_1c, loans.first_loan_date as d_loan_first_loan_date, loans.in_cash as d_in_cash,'
                                    . 'loantypes.name as d_loan_name, claims.created_at as d_claim_created_at, '
                                    . 'users.login as spec_fio, users.phone as spec_phone, debtors.fine as d_fine, '
                                    . 'debtors.pc as d_pc, debtors.exp_pc as d_exp_pc, passports.birth_date as birth_date, '
                                    . 'loans.money as money, customers.id_1c as d_customer_id_1c, about_clients.customer_id as a_customer_id, '
                                    . 'claims.id as r_claim_id, loans.id as r_loan_id'))
                    ->leftJoin('loans', 'loans.id_1c', '=', 'debtors.debtors.loan_id_1c')
                    ->leftJoin('customers', 'customers.id_1c', '=', 'debtors.debtors.customer_id_1c')
                    ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                    ->leftJoin('passports', function($join) {
                        $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                        $join->on('passports.number', '=', 'debtors.debtors.passport_number');
                    })
                    ->leftJoin('loantypes', 'loantypes.id', '=', 'debtors.loans.loantype_id')
                    ->leftJoin('about_clients', 'about_clients.id', '=', 'debtors.claims.about_client_id')
                    ->leftJoin('debtors.users', 'debtors.users.id_1c', '=', 'debtors.responsible_user_id_1c')
                    ->where('debtors.id', $debtor->id);

            $debtorTmp = Debtor::find($debtor->id);

            $objDebtorData = $debtorData->first();

            $date = $date_personal = date('d.m.Y', time());

            $isKkzLoanEnd = false;
            if (str_contains($objDebtorData->loan_id_1c, 'ККЗ')) {
                $date_cc = $date;

                $json_string_cc = file_get_contents('http://192.168.35.54:8020/api/v1/loans/' . $objDebtorData->loan_id_1c . '/schedule/' . $date_cc);
                $arDataCc = json_decode($json_string_cc, true);

                $arParams['loan_end_at_cc'] = date('d.m.Y', strtotime($arDataCc['data']['loan_end_at']));
                $arParams['overdue_mop_cc'] = ($arDataCc['data']['overdue_debt'] + $arDataCc['data']['overdue_percent'] + $arDataCc['data']['fine']) / 100;
                $arParams['overdue_debt_cc'] = $arDataCc['data']['overdue_debt'] / 100;
                $arParams['overdue_percent_cc'] = $arDataCc['data']['overdue_percent'] / 100;
                $arParams['overdue_fine_cc'] = $arDataCc['data']['fine'] / 100;

                $arParams['total_debt_cc'] = $arDataCc['data']['total_debt'] / 100;
                $arParams['debt_cc'] = ($arDataCc['data']['debt'] + $arDataCc['data']['mop_debt'] + $arDataCc['data']['overdue_debt']) / 100;
                $arParams['percent_cc'] = ($arDataCc['data']['percent'] + $arDataCc['data']['mop_percent'] + $arDataCc['data']['overdue_percent']) / 100;

                $arParams['current_percent_cc'] = ($arDataCc['data']['percent'] + $arDataCc['data']['mop_percent']) / 100;
                $arParams['current_debt_cc'] = ($arDataCc['data']['debt'] + $arDataCc['data']['mop_debt']) / 100;

                if (time() > strtotime($arDataCc['data']['loan_end_at'])) {
                    $isKkzLoanEnd = true;
                }
            }

            if ($user->hasRole('debtors_remote')) {
                if ($debtor->str_podr == '000000000006') {
                    if ($debtor->is_bigmoney == 1) {
                        $doc_id = 146;
                    } else if ($debtor->qty_delays < 61 && str_contains($debtor->loan_id_1c, 'ККЗ')) {
                        $doc_id = 147;
                    } else {
                        $doc_id = 140;
                    }
                } else {
                    continue;
                }
            } else if ($user->hasRole('debtors_personal')) {
                if ($debtor->str_podr == '000000000007') {
                    if ($debtor->is_bigmoney == 1) {
                        $doc_id = 145;
                    } else if (str_contains($debtor->loan_id_1c, 'ККЗ') && !$isKkzLoanEnd) {
                        $doc_id = 148;
                    } else if (str_contains($debtor->loan_id_1c, 'ККЗ') && $isKkzLoanEnd) {
                        $doc_id = 149;
                    } else {
                        $doc_id = 144;
                    }
                } else {
                    continue;
                }
            } else {
                die();
            }

            if (!is_null($objDebtorData)) {

                $doc = \App\ContractForm::where('id', (int) $doc_id)->first();

                $html = $doc->template;

                $passport = json_decode(json_encode(Passport::where('series', $objDebtorData->passport_series)->where('number', $objDebtorData->passport_number)->first()), true);

                $loan_percent = $objDebtorData->special_percent;
                if (is_null($loan_percent)) {
                    $loan_percent = $objDebtorData->percent;
                }

                $return_date = date('d.m.Y', strtotime("+" . $objDebtorData->d_loan_time . " days", strtotime($objDebtorData->d_loan_created_at)));
                $arSpecName = explode(' ', $objDebtorData->spec_fio);

                $spec_surname = '';
                if (isset($arSpecName[0])) {
                    $spec_surname = $arSpecName[0];
                }

                $spec_io = '';
                if (isset($arSpecName[1])) {
                    $spec_io = $arSpecName[1];
                    if (isset($arSpecName[2])) {
                        $spec_io .= ' ' . $arSpecName[2];
                    }
                }

                $arBirthDate = explode('-', $objDebtorData->birth_date);
                if (!isset($arBirthDate[2]) || !isset($arBirthDate[1]) || !isset($arBirthDate[0])) {
                    $debtor_birth_date = 'n/a';
                } else {
                    $debtor_birth_date = $arBirthDate[2] . '.' . $arBirthDate[1] . '.' . $arBirthDate[0];
                }

                $loan = Loan::where('id_1c', $objDebtorData->loan_id_1c)->first();

                if ($date != '0') {

                    $debtData = $loan->getDebtFrom1cWithoutRepayment(date('Y-m-d', strtotime($date_personal)));

                    $debt = [];
                    $arFrom1CFields = ['od', 'pc', 'exp_pc', 'fine', 'all_pc', 'money', 'pays'];

                    foreach ($arFrom1CFields as $field) {
                        if (isset($debtData->$field) && !is_null($debtData->$field) && mb_strlen($debtData->$field)) {
                            $debt[$field] = $debtData->$field;
                        } else {
                            $debt[$field] = 0;
                        }
                    }
                } else {
                    $debt['od'] = $objDebtorData->od;
                    $debt['pc'] = $objDebtorData->d_pc;
                    $debt['exp_pc'] = $objDebtorData->d_exp_pc;
                    $debt['fine'] = $objDebtorData->d_fine;
                    $debt['money'] = $objDebtorData->sum_indebt;
                    $debt['all_pc'] = $objDebtorData->d_pc + $objDebtorData->d_exp_pc;
                }

                if (isset($debt['pays']) && $debt['pays'] !== 0) {
                    $fullpay_sum = 0;
                    $fullpay_od_sum = 0;
                    $fullpay_pc_sum = 0;
                    $fullpay_exp_pc_sum = 0;
                    $fullpay_fine_sum = 0;

                    $open_debt = false;

                    $arrayPays = json_decode(json_encode($debt['pays']), true);

                    foreach ($arrayPays as $pay) {
                        if ($pay['expired'] == 1 && $pay['closed'] == 1) {
                            if (is_int($pay['exp_pc']) || is_string($pay['exp_pc'])) {
                                $fullpay_exp_pc_sum += $pay['exp_pc'];
                            }

                            if (is_int($pay['fine']) || is_string($pay['fine'])) {
                                $fullpay_fine_sum += $pay['fine'];
                            }

                            $fullpay_sum = $fullpay_sum + $fullpay_exp_pc_sum + $fullpay_fine_sum;
                        }

                        if ($pay['expired'] == 1 && $pay['closed'] == 0) {
                            $open_debt = true;

                            if (is_int($pay['od']) || is_string($pay['od'])) {
                                $fullpay_sum += $pay['od'];
                                $fullpay_od_sum += $pay['od'];
                            }

                            if (is_int($pay['pc']) || is_string($pay['pc'])) {
                                $fullpay_sum += $pay['pc'];
                                $fullpay_pc_sum += $pay['pc'];
                            }

                            if (is_int($pay['exp_pc']) || is_string($pay['exp_pc'])) {
                                $fullpay_sum += $pay['exp_pc'];
                                $fullpay_exp_pc_sum += $pay['exp_pc'];
                            }

                            if (is_int($pay['fine']) || is_string($pay['fine'])) {
                                $fullpay_sum += $pay['fine'];
                                $fullpay_fine_sum += $pay['fine'];
                            }
                        }

                        if ($pay['expired'] == 0 && $pay['closed'] == 1) {
                            continue;
                        }

                        if ($pay['expired'] == 0 && $pay['closed'] == 0) {
                            if ($open_debt) {
                                $open_debt = false;

                                $fullpay_pc_sum += $pay['pc'];
                                $fullpay_sum = $fullpay_sum + $pay['pc'];
                            }

                            $fullpay_od_sum += $pay['od'];
                            $fullpay_sum = $fullpay_sum + $pay['od'];
                        }
                    }
                }

                $od = number_format($debt['od'] / 100, 2, ',', ' ');
                $pc = number_format($debt['pc'] / 100, 2, ',', ' ');
                $exp_pc = number_format($debt['exp_pc'] / 100, 2, ',', ' ');
                $fine = number_format($debt['fine'] / 100, 2, ',', ' ');
                $sum_indebt = number_format($debt['money'] / 100, 2, ',', ' ');
                $sum_pc = number_format(($debt['exp_pc'] + $debt['pc']) / 100, 2, ',', ' ');

                $arOd = explode(',', $od);
                $arPc = explode(',', $pc);
                $arExp_pc = explode(',', $exp_pc);
                $arFine = explode(',', $fine);
                $arSumIndebt = explode(',', $sum_indebt);

                $fact_pc = number_format($debt['all_pc'] / 100, 2, ',', ' ');
                $arFactPc = explode(',', $fact_pc);

                $ur_address = $debtorTmp->passport->full_address;
                $fact_address = $debtorTmp->passport->fact_full_address;
                if (!$address_type) {
                    $print_address = $ur_address;
                } else {
                    $print_address = $fact_address;
                }

                $objDebtorData->this_loan_created_at = $objDebtorData->d_loan_created_at;

                if (mb_strlen($date_personal)) {
                    $ar_req_date = explode(' ', StrUtils::dateToStr($date_personal));
                }

                $req_day = (isset($ar_req_date)) ? $ar_req_date[0] : '';
                $req_month = (isset($ar_req_date)) ? $ar_req_date[1] : '';
                $req_year = (isset($ar_req_date)) ? $ar_req_date[2] : '';

                $arVidTruda = [
                    'Официальное',
                    'Неофициальное',
                    'Пенсионер работающий',
                    'Пенсионер неработающий',
                    'Льготная категория',
                    'Домохозяйка',
                    'Студент',
                ];

                $arParams += [
                    'print_date' => date('d.m.Y', time()),
                    'debtor_fio' => $objDebtorData->fio,
                    'debtor_birth_date' => $debtor_birth_date,
                    'p_series' => $objDebtorData->series,
                    'p_number' => $objDebtorData->number,
                    'issued_date' => StrUtils::dateToStr($objDebtorData->issued_date),
                    'issued' => $objDebtorData->issued,
                    'subdivision_code' => $objDebtorData->subdivision_code,
                    'address_reg_date' => StrUtils::dateToStr($objDebtorData->address_reg_date),
                    'ur_address' => $ur_address,
                    'fact_address' => $fact_address,
                    'loan_id_1c' => $objDebtorData->d_loan_id_1c,
                    //'loan_created_at' => StrUtils::dateToStr($objDebtorData->d_loan_created_at),
                    'loan_created_at' => StrUtils::dateToStr($objDebtorData->this_loan_created_at),
                    'money' => $od,
                    'time' => $objDebtorData->d_loan_time,
                    'loan_percent' => $loan_percent,
                    'loan_name' => $objDebtorData->d_loan_name,
                    'claim_created_at' => date('d.m.Y', strtotime($objDebtorData->d_claim_created_at)),
                    'return_date' => $return_date,
                    'sum_indebt' => $sum_indebt,
                    'return_sum' => number_format(($objDebtorData->money + $objDebtorData->money * ($loan_percent / 100) * $objDebtorData->d_loan_time), 2, '.', ' '),
                    'organizacia' => $objDebtorData->organizacia,
                    'adresorganiz' => $objDebtorData->adresorganiz,
                    'dolznost' => $objDebtorData->dolznost,
                    'vidtruda' => (isset($arVidTruda[$objDebtorData->vidtruda])) ? $arVidTruda[$objDebtorData->vidtruda] : 'Не определено',
                    'telephoneorganiz' => $objDebtorData->telephoneorganiz,
                    'fiorukovoditel' => $objDebtorData->fiorukovoditel,
                    'dohod' => number_format($objDebtorData->dohod, 0, '.', ' '),
                    'dopdohod' => number_format($objDebtorData->dopdohod, 0, '.', ' '),
                    'dohod_husband' => (!is_null($objDebtorData->dohod_husband)) ? number_format($objDebtorData->dohod_husband, 0, '.', ' ') : 0,
                    'pension' => (!is_null($objDebtorData->pension)) ? number_format($objDebtorData->pension, 0, '.', ' ') : 0,
                    'live_condition' => \App\LiveCondition::getLiveConditionById($objDebtorData->zhusl),
                    'birth_city' => $passport['birth_city'],
                    'deti' => $objDebtorData->deti,
                    'fiosuprugi' => $objDebtorData->fiosuprugi,
                    'fioizmena' => $objDebtorData->fioizmena,
                    'telephonehome' => $objDebtorData->telephonehome,
                    'telephone' => $objDebtorData->telephone,
                    'anothertelephone' => $objDebtorData->anothertelephone,
                    'telephonerodstv' => $objDebtorData->telephonerodstv,
                    'stepenrodstv' => \App\Stepenrodstv::getStepenById($objDebtorData->stepenrodstv),
                    'credit' => $objDebtorData->credit,
                    'od' => $od,
                    'pc' => $pc,
                    'exp_pc' => $exp_pc,
                    'fine' => $fine,
                    'sum_pc' => $sum_pc,
                    'qty_delays' => $objDebtorData->qty_delays,
                    'comment' => $objDebtorData->comment,
                    'overdue_start' => date('d.m.Y', strtotime("+1 day", strtotime($return_date))),
                    'spec_surname' => $spec_surname,
                    'spec_io' => $spec_io,
                    'spec_fio' => $objDebtorData->spec_fio,
                    'spec_phone' => $objDebtorData->spec_phone,
                    'spec_doc' => $objDebtorData->doc,
                    'od_rub' => $arOd[0],
                    'od_kop' => isset($arOd[1]) ? $arOd[1] : '00',
                    'pc_rub' => $arPc[0],
                    'pc_kop' => isset($arPc[1]) ? $arPc[1] : '00',
                    'exp_pc_rub' => $arExp_pc[0],
                    'exp_pc_kop' => isset($arExp_pc[1]) ? $arExp_pc[1] : '00',
                    'fine_rub' => $arFine[0],
                    'fine_kop' => isset($arFine[1]) ? $arFine[1] : '00',
                    'sum_indebt_rub' => $arSumIndebt[0],
                    'sum_indebt_kop' => isset($arSumIndebt[1]) ? $arSumIndebt[1] : '00',
                    'pc_fact_rub' => $arFactPc[0],
                    'pc_fact_kop' => isset($arFactPc[1]) ? $arFactPc[1] : '00',
                    'date_personal' => $date_personal,
                    'date_suspended' => date('d.m.Y', strtotime("+10 day", strtotime($date_personal))),
                    'print_address' => $print_address,
                    'tranche_data' => '',
                    'req_day' => $req_day,
                    'req_month' => $req_month,
                    'req_year' => $req_year,
                    'fact_pc' => $fact_pc,
                    'fullpay_sum' => isset($fullpay_sum) ? $fullpay_sum : '',
                    'fullpay_od_sum' => isset($fullpay_od_sum) ? $fullpay_od_sum : '',
                    'fullpay_percents_sum' => isset($fullpay_pc_sum) ? $fullpay_pc_sum + $fullpay_exp_pc_sum : '',
                    'fullpay_fine_sum' => isset($fullpay_fine_sum) ? $fullpay_fine_sum : ''
                ];

                $arParams['tranche_data'] = '';
                $arParams['dop_string'] = '';

                $eventUser = User::where('id_1c', $debtorTmp->responsible_user_id_1c)->first();

                $arParams['spec_fio'] = $eventUser->login;
                $arParams['spec_phone'] = $eventUser->phone;
                $arParams['spec_doc'] = $eventUser->doc;

                if (mb_strlen($arParams['spec_phone']) < 6) {
                    $arParams['spec_phone'] = '88003014344';
                }

                if (isset($repayment_in_cash) && $repayment_in_cash) {
                    $arParams['dop_string'] = 'Дополнительное соглашение от ' . date('d.m.Y', strtotime($repl_repayment->created_at)) . ', ';
                }

                $arParams['dop_string'] = '';
                $arParams['tranche_data'] = '';

                if ($debtorTmp->is_bigmoney == 1) {

                    $postData = [
                        'loan_id_1c' => $debtorTmp->loan_id_1c,
                        'customer_id_1c' => $debtorTmp->customer_id_1c,
                        'date' => date('Y-m-d', time())
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'http://192.168.35.102/ajax/loans/get/debt');
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    curl_close($ch);

                    $arResult = json_decode($result, true);

                    if (isset($arResult['pays'])) {
                        $days_overdue = 0;
                        foreach ($arResult['pays'] as $payment) {
                            if ($payment['expired'] == 1 && $payment['closed'] == 0) {
                                $arBigMoneyExpDates[] = date('d.m.Y', strtotime($payment['date'])) . ' г.';
                                $days_overdue += $payment['days_overdue'];
                            }
                        }

                        $arParams['expBigMoneyDates'] = implode(',', $arBigMoneyExpDates);
                        $arParams['days_overdue'] = $days_overdue;
                    }
                }

                if (mb_strlen($html) > 200) {
                    $pic = '';
                    $arParams['pic'] = $pic;
                    foreach ($arParams as $param => $value) {
                        $html = str_replace("{{" . $param . "}}", $value, $html);
                    }
                    return PdfUtil::getPdf($html);
                }
                $cUser = auth()->user();

                if ($doc_id == 140 || $doc_id == 141 || $doc_id == 144 || $doc_id == 145 || $doc_id == 146 || $doc_id == 147 || $doc_id == 148 || $doc_id == 149) {
                    $userHeadChief = User::find(951);
                    $arParams['headchief_doc'] = $userHeadChief->doc;

                    $currentUser = auth()->user();
                    $number_postfix = '';
                    if ($eventUser->hasRole('debtors_remote')) {
                        $number_postfix = 'УВ';
                    }
                    if ($eventUser->hasRole('debtors_personal')) {
                        $number_postfix = 'ЛВ';
                    }

                    if ($cUser->id != 69) {
                        $notice_number = NoticeNumbers::addRecord($doc_id, $debtorTmp->id, $address_type, $eventUser);
                        if (!$notice_number) {
                            die('Не удалось сформировать исходящий номер. Обратитесь к программистам.');
                        }
                    } else {
                        $notice_number = new \stdClass();
                        $notice_number->new_notice = false;
                        $notice_number->id = '123456';
                    }

                    if ($doc_id == 144 || $doc_id == 145 || $doc_id == 148 || $doc_id == 149) {
                        if ($eventUser->id == 916) {
                            $arParams['req_spec_position'] = 'Ведущий специалист';
                            $arParams['spec_fio'] = 'Свиридов Павел Владимирович';
                        } else if ($eventUser->hasRole('debtors_chief') && $eventUser->hasRole('debtors_personal')) {
                            $arParams['req_spec_position'] = 'Старший специалист';
                        } else {
                            $arParams['req_spec_position'] = 'Специалист';
                        }
                    }

                    $address_type_txt = ($address_type) ? 'проживания' : 'регистрации';

                    if ($notice_number->new_notice && $currentUser->id != 69 && !is_null($eventUser)) {
                        $doctype = ($doc_id == 144 || $doc_id == 145 || $doc_id == 148 || $doc_id == 149) ? 'требование' : 'уведомление';

                        $pdfEvent = new DebtorEvent();
                        $pdfEvent->customer_id_1c = $debtorTmp->customer_id_1c;
                        $pdfEvent->loan_id_1c = $debtorTmp->loan_id_1c;
                        $pdfEvent->event_type_id = ($number_postfix == 'УВ') ? 13 : 20;
                        $pdfEvent->debt_group_id = $debtorTmp->debt_group_id;
                        $pdfEvent->overdue_reason_id = 0;
                        $pdfEvent->event_result_id = ($number_postfix == 'УВ') ? 9 : 27;
                        $pdfEvent->report = 'Отправлено ' . $doctype . ' по договору ' . $loan->id_1c . ' от ' . date('d.m.Y', strtotime($objDebtorData->d_loan_created_at)) . ' г. Исх. № ' . $notice_number->id . '/' . $number_postfix . ' от ' . date('d.m.Y', strtotime($notice_number->created_at)) . ' по адресу ' . $address_type_txt . ': ' . $print_address;
                        $pdfEvent->debtor_id = $debtorTmp->id;
                        $pdfEvent->user_id = $eventUser->id;
                        $pdfEvent->last_user_id = $eventUser->id;
                        $pdfEvent->completed = 1;
                        $pdfEvent->debtor_id_1c = $debtorTmp->debtor_id_1c;
                        $pdfEvent->user_id_1c = $eventUser->id_1c;
                        $pdfEvent->refresh_date = date('Y-m-d H:i:s', time());
                        $pdfEvent->save();
                    }

                    $postfix_number_doc = '';

                    if ($doc_id == 144 && $number_postfix == 'УВ') {
                        $postfix_number_doc = '/УВ';
                    }

                    $arParams['notice_number'] = $notice_number->id . $postfix_number_doc;
                    $arParams['date_sent'] = $arParams['print_date'];
                }

                $arMassTask = [
                    'filename' => $notice_number->id . '_' . $i,
                    'task_id' => $task_id
                ];

                $i++;

                \App\Utils\FileToPdfUtil::replaceKeys($doc->tplFileName, $arParams, 'debtors', $arMassTask);

                $rows[] = [
                    'name' => $arMassTask['filename'] . '.pdf',
                    'address' => $arParams['print_address'],
                    'fio' => $arParams['debtor_fio'],
                    'notice_id' => $notice_number->id,
                    'address_company' => '650000, г. Кемерово, пр. Советский, 2/6',
                ];
                sleep(5);
            }
        }

        $path = storage_path() . '/app/public/postPdfTasks/' . $task_id;
        config()->set('filesystems.disks.local.root',$path);

        Excel::store(new NoticeExport($rows), $task_id . '.xls', 'local_notice');

        $arDir = scandir($path);
        $zip = new \ZipArchive();
        if ($zip->open($path . '/' . $task_id . '.zip', \ZipArchive::CREATE) === true) {
            foreach ($arDir as $k => $pdfFile) {
                if ($k == 0 || $k == 1) {
                    continue;
                }
                if (str_contains($pdfFile, '.xls')) {
                    continue;
                }
                $zip->addFile($path . '/' . $pdfFile, $pdfFile);
            }
            $zip->close();
        }
    }

    public function getFile($type, $task_id)
    {
        $path = storage_path('app/public/postPdfTasks/') . $task_id;

        if ($type == 'zip') {
            $path .= '/' . $task_id . '.zip';
            $ext = 'attachment; filename="' . $task_id . '.zip"';
        } else if ($type == 'xls') {
            $path .= '/' . $task_id . '.xls';
            $ext = 'attachment; filename="' . $task_id . '.xls"';
        }

        $file = File::get($path);
        $filetype = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $filetype);
        $response->header("Content-Disposition", $ext);

        return $response;
    }

    public function courtNotices(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('debtors_personal')) {
            $struct_subdivision = '000000000007';
        } else {
            $struct_subdivision = false;
        }

        $taskInProgress = false;

        if ($struct_subdivision) {
            $courtTask = CourtOrderTasks::where('in_progress', 1)->where('struct_subdivision', $struct_subdivision)->first();
            $taskInProgress = (!is_null($courtTask)) ? true : false;
        }

        $tasks = CourtOrderTasks::where('struct_subdivision', $struct_subdivision)
                ->orderBy('created_at', 'desc')
                ->get();

        return view('debtors.notices.courtNotices', [
            'user' => $user,
            'taskInProgress' => $taskInProgress,
            'tasks' => $tasks
        ]);
    }

    public function startCourtTask(StartCourtTaskRequest $request)
    {
        $input = $request->input();

        $user = auth()->user();
        if ($user->hasRole('debtors_personal')) {
            $str_podr = '000000000007';
        } else {
            $str_podr = false;
        }

        if (!$str_podr) {
            return redirect()->back()->with('msg_err','Только для специалистов личного взыскания');
        }

        $fixationDateFrom = !empty($input['fixation_date_from']) ?
            Carbon::parse($input['fixation_date_from'])->startOfDay(): null;
        $fixationDateTo = !empty($input['fixation_date_to']) ?
            Carbon::parse($input['fixation_date_to'])->endOfDay() : null;

        $qtyDelaysFrom = !empty($input['qty_delays_from']) ? $input['qty_delays_from'] : null;
        $qtyDelaysTo = !empty($input['qty_delays_to']) ?
            $input['qty_delays_to'] : null;

        if (is_null($fixationDateFrom) && is_null($fixationDateTo) && is_null($qtyDelaysFrom) && is_null($qtyDelaysTo)) {
            return redirect()->back()->with('msg_err', 'Отсутсвуют параметры для поиска');
        }

        $respUsers = [];
        foreach ($input['responsible_users_ids'] as $uid) {
            $respUser = User::find($uid);
            $respUsers[] = $respUser->id_1c;
        }

        $debtors = Debtor::where('is_debtor', 1)
            ->whereIn('responsible_user_id_1c', $respUsers)
            ->whereIn('debt_group_id', $input['debt_group_ids'])
            ->byQty($qtyDelaysFrom,$qtyDelaysTo)
            ->byFixation($fixationDateFrom,$fixationDateTo)
            ->get();

        $courtTask = new CourtOrderTasks();
        $courtTask->struct_subdivision = $str_podr;
        $courtTask->in_progress = 1;
        $courtTask->completed = 0;
        $courtTask->save();

        $massDir = storage_path() . '/app/public/courtPdfTasks/' . $courtTask->id . '/';

        if (!is_dir($massDir)) {
            mkdir(storage_path() . '/app/public/courtPdfTasks/' . $courtTask->id, 0777);
        }

        $this->createCourtPdf($debtors, $courtTask->id, $str_podr);

        $courtTask->in_progress = 0;
        $courtTask->completed = 1;
        $courtTask->save();

        return redirect()->back();
    }

    public function createCourtPdf($debtors, $task_id, $str_podr)
    {
        $path = storage_path() . '/app/public/courtPdfTasks/' . $task_id;

        $rows = [];
        foreach ($debtors as $debtor) {
            $courtOrder = CourtOrder::where('debtor_id', $debtor->id)->first();

            if (!is_null($courtOrder)) {
                continue;
            }

            $html = $this->pdfService->getCourtOrder($debtor);
            PdfUtil::savePdfFromPrintServer($html, $path . '/' . $debtor->id . '.pdf');

            $rows[] =  [
                'name' => $debtor->id . '.pdf',
                'address' => $debtor->passport->full_address,
                'fio' =>$debtor->passport->fio,
                'debtor_id' =>$debtor->id,
                'address_company' =>'650000, г. Кемерово, пр. Советский, 2/6',
            ];
            sleep(5);
        }
        config()->set('filesystems.disks.local.root',$path);
        Excel::store(new CourtExport($rows),$task_id . '.xls','local_courts');

        $arDir = scandir($path);

        $zip = new \ZipArchive();
        if ($zip->open($path . '/' . $task_id . '.zip', \ZipArchive::CREATE) === TRUE) {
            foreach ($arDir as $k => $pdfFile) {
                if ($k == 0 || $k == 1) {
                    continue;
                }

                if (str_contains($pdfFile, '.xls')) {
                    continue;
                }

                $zip->addFile($path . '/' . $pdfFile, $pdfFile);
            }

            $zip->close();
        }
    }

    public function getCourtFile($type, $task_id)
    {
        $path = storage_path('app/public/courtPdfTasks/') . $task_id;

        if ($type == 'zip') {
            $path .= '/' . $task_id . '.zip';
            $ext = 'attachment; filename="' . $task_id . '.zip"';
        } else if ($type == 'xls') {
            $path .= '/' . $task_id . '.xls';
            $ext = 'attachment; filename="' . $task_id . '.xls"';
        }

        $file = File::get($path);
        $filetype = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $filetype);
        $response->header("Content-Disposition", $ext);

        return $response;
    }
}
