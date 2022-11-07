<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\MaterialsClaim,
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
    App\Loan,
    App\Claim,
    App\NpfContract,
    App\Order,
    Carbon\Carbon;

class PlanController extends BasicController {

    const USER_ID_TO_CHANGE = 435;

    public function __construct() {
        
    }

    public function getTableView(Request $req) {
        $res1c = MySoap::sendXML(MySoap::createXML(['type' => '8', 'user_id_1c' => Auth::user()->id_1c, 'subdivision_id_1c' => Auth::user()->subdivision->name_id]));
        if (!((int) $res1c->result)) {
            $error = (string) $res1c->error;
            if ($error == 'Нет остатков') {
                $error = 'Планы за текущий месяц еще не утверждены';
            }
            return view('reports.plan')->with('data', $this->getEmptyData())->with('msg_err', $error);
        }
        $data = [
            'claims' => $this->getClaimsNum((isset($res1c->PlanClaim) && $res1c->PlanClaim > 0) ? $res1c->PlanClaim : 1),
            'loans' => $this->getLoansNum($res1c->PlanZaim),
//            'cards' => $this->getCardsNum($plan['cards']),
            'npf' => $this->getNpfNum($res1c->PlanPoNpft),
            'uki' => $this->getUkiNum($res1c->PlanPoUki),
        ];
        return view('reports.plan')->with('data', $data);
    }

    function getEmptyData() {
        return [
            'claims' => ['plan' => 0, 'fact' => 0, 'complete' => 0, 'personal' => 0],
            'loans' => ['plan' => 0, 'fact' => 0, 'complete' => 0, 'personal' => 0],
//            'cards' => ['plan' => 0, 'fact' => 0, 'complete' => 0, 'personal' => 0],
            'npf' => ['plan' => 0, 'fact' => 0, 'complete' => 0, 'personal' => 0],
            'uki' => ['plan' => 0, 'fact' => 0, 'complete' => 0, 'personal' => 0],
        ];
    }

    function getMonth() {
        return [
            'start' => Carbon::now()->setDate(Carbon::now()->year, Carbon::now()->month, 1)->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            'end' => Carbon::now()->setDate(Carbon::now()->year, Carbon::now()->month, Carbon::now()->daysInMonth)->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        ];
    }

    function getClaimsNum($plan = 1, $user_id = null, $subdiv_id = null) {
        $m = $this->getMonth();
        $res = ['plan' => $plan, 'fact' => 0, 'complete' => 0, 'personal' => 0];
        $user_id = (is_null($user_id)) ? Auth::user()->id : $user_id;
        $subdiv_id = (is_null($subdiv_id)) ? Auth::user()->subdivision_id : $subdiv_id;

        $res['fact'] = Claim::where('subdivision_id', $subdiv_id)
                ->where('claims.created_at', '>=', $m['start'])
                ->where('claims.created_at', '<=', $m['end'])
                ->count();
        $res['personal'] = Claim::where('subdivision_id', $subdiv_id)
                ->where('user_id', $user_id)
                ->where('claims.created_at', '>=', $m['start'])
                ->where('claims.created_at', '<=', $m['end'])
                ->count();
        $res['complete'] = $res['fact'] / $res['plan'] * 100;
        return $res;
    }

    function getLoansNum($plan, $user_id = null, $subdiv_id = null) {
        $m = $this->getMonth();
        $user_id = (is_null($user_id)) ? Auth::user()->id : $user_id;
        $subdiv_id = (is_null($subdiv_id)) ? Auth::user()->subdivision_id : $subdiv_id;

        $loans = Loan::where('subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->distinct()
                ->select('money', 'loans.id')
                ->get();
        \PC::debug(count($loans), 'loans');
        $loansSum = 0;
        foreach ($loans as $l) {
            $loansSum+=$l->money;
        }
        $myLoans = Loan::where('subdivision_id', $subdiv_id)
                ->where('user_id', $user_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->distinct()
                ->select('money', 'loans.id')
                ->get();

        $ordersSum = Order::where('user_id', $user_id)
                ->where('created_at', '>=', $m['start'])
                ->where('created_at', '<=', $m['end'])
                ->where('type', \App\OrderType::getRKOid())
                ->whereNotNull('loan_id')
                ->sum('money');
        \PC::debug($ordersSum / 100, 'orders sum');
        \PC::debug(count($myLoans), 'myloans');
        $myLoansSum = 0;
        foreach ($myLoans as $loan) {
            $myLoansSum += $loan->money;
        }
        $plan = (float) $plan;
        return [
            'plan' => $plan,
            'fact' => $loansSum,
            'complete' => ($plan > 0) ? (($loansSum) / $plan * 100) : 0,
            'personal' => ($plan > 0) ? $myLoansSum : 0
        ];
    }

    function getCardsNum($plan) {
        $m = $this->getMonth();
        $subdivCards = Loan::where('subdivision_id', Auth::user()->subdivision_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->whereNotNull('loans.card_id')
                ->leftJoin('cards', 'loans.card_id', '=', 'cards.id')
                ->where('cards.created_at', '>=', $m['start'])
                ->where('cards.created_at', '<=', $m['end'])
                ->count();
        $userCards = Loan::where('subdivision_id', Auth::user()->subdivision_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('loans.user_id', Auth::user()->id)
                ->whereNotNull('loans.card_id')
                ->leftJoin('cards', 'loans.card_id', '=', 'cards.id')
                ->where('cards.created_at', '>=', $m['start'])
                ->where('cards.created_at', '<=', $m['end'])
                ->count();
        return [
            'plan' => $plan,
            'fact' => $subdivCards / 100,
            'complete' => ($plan > 0) ? ($subdivCards / $plan * 100) : 0,
            'personal' => ($plan > 0) ? ($userCards / $plan * 100) : 0
        ];
    }

    function getNpfNum($plan, $user_id = null, $subdiv_id = null) {
        $m = $this->getMonth();
        $user_id = (is_null($user_id)) ? Auth::user()->id : $user_id;
        $subdiv_id = (is_null($subdiv_id)) ? Auth::user()->subdivision_id : $subdiv_id;

        $subdivNpfs = NpfContract::where('subdivision_id', $subdiv_id)
                ->where('created_at', '>=', $m['start'])
                ->where('created_at', '<=', $m['end'])
                ->count();
        $userNpfs = NpfContract::where('subdivision_id', $subdiv_id)
                ->where('created_at', '>=', $m['start'])
                ->where('created_at', '<=', $m['end'])
                ->where('user_id', $user_id)
                ->count();
        return [
            'plan' => $plan,
            'fact' => $subdivNpfs,
            'complete' => ($plan > 0) ? ($subdivNpfs / $plan * 100) : 0,
            'personal' => ($plan > 0) ? ($userNpfs / $plan * 100) : 0
        ];
    }

    function getUkiNum($plan, $user_id = null, $subdiv_id = null) {
        $m = $this->getMonth();
        $user_id = (is_null($user_id)) ? Auth::user()->id : $user_id;
        $subdiv_id = (is_null($subdiv_id)) ? Auth::user()->subdivision_id : $subdiv_id;

        $subdivLoans = \App\Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('claims.uki', 1)
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->count();
        $userLoans = \App\Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('claims.uki', 1)
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->where('loans.user_id', $user_id)
                ->count();
        return [
            'plan' => $plan,
            'fact' => $subdivLoans,
            'complete' => ($plan > 0) ? number_format($subdivLoans / $plan * 100, 2, '.', '') : 0,
            'personal' => ($plan > 0) ? number_format($userLoans / $plan * 100, 2, '.', '') : 0
        ];
    }

    public function getLoansList(Request $req) {
        $subdiv_id = ($req->has('subdivision_id')) ? $req->subdivision_id : Auth::user()->subdivision_id;
        $user_id = ($req->has('user_id')) ? $req->user_id : Auth::user()->id;
        $subdivision = \App\Subdivision::find($subdiv_id);
        $user = \App\User::find($user_id);
        $m = $this->getMonth();
        $loans_my = [];
        $total_money = 0;
        $total_money_my = 0;
        $loans = Loan::where('loans.subdivision_id', $req->subdivision_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->leftJoin('users', 'users.id', '=', 'loans.user_id')
                ->distinct()
                ->select('money', 'loans.id as loan_id', 'loans.id_1c as loan_id_1c', 'passports.fio as fio', 'users.name as username')
                ->get();

        foreach ($loans as $l) {
            $total_money += $l->money;
        }

        $loans_my = Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('loans.user_id', $user_id)
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->distinct()
                ->select('money', 'loans.id as loan_id', 'loans.id_1c as loan_id_1c', 'passports.fio as fio')
                ->get();
        foreach ($loans_my as $l) {
            $total_money_my += $l->money;
        }

        $ukis = \App\Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('claims.uki', 1)
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
                ->leftJoin('users', 'users.id', '=', 'loans.user_id')
                ->select('loans.id as loan_id', 'loans.id_1c as loan_id_1c', 'passports.fio as fio', 'users.name as username')
                ->get();
        $total_uki = count($ukis);

        $ukis_my = \App\Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('claims.uki', 1)
                ->where('loans.user_id', $user_id)
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'claims.passport_id', '=', 'passports.id')
                ->select('loans.id as loan_id', 'loans.id_1c as loan_id_1c', 'passports.fio as fio')
                ->get();
        $total_uki_my = count($ukis_my);

        $subdivCards = Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->whereNotNull('loans.card_id')
                ->leftJoin('cards', 'loans.card_id', '=', 'cards.id')
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->where('cards.created_at', '>=', $m['start'])
                ->where('cards.created_at', '<=', $m['end'])
                ->select('money', 'loans.id as loan_id', 'loans.id_1c as loan_id_1c', 'passports.fio as fio')
                ->get();
        $totalCards = count($subdivCards);
        $userCards = Loan::where('loans.subdivision_id', $subdiv_id)
                ->where('loans.created_at', '>=', $m['start'])
                ->where('loans.created_at', '<=', $m['end'])
                ->where('loans.user_id', $user_id)
                ->whereNotNull('loans.card_id')
                ->leftJoin('cards', 'loans.card_id', '=', 'cards.id')
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->where('cards.created_at', '>=', $m['start'])
                ->where('cards.created_at', '<=', $m['end'])
                ->select('money', 'loans.id as loan_id', 'loans.id_1c as loan_id_1c', 'passports.fio as fio')
                ->get();
        $totalCards_my = count($userCards);

        $res1c = MySoap::sendXML(MySoap::createXML(['type' => '8', 'user_id_1c' => $user->id_1c, 'subdivision_id_1c' => $subdivision->name_id]));
        if (!((int) $res1c->result)) {
            $error = (string) $res1c->error;
            if ($error == 'Нет остатков') {
                $error = 'Планы за текущий месяц еще не утверждены';
            }
            return view('reports.plan')->with('data', $this->getEmptyData())->with('msg_err', $error);
        }
        $data = [
            'loans' => $this->getLoansNum($res1c->PlanZaim, $user_id, $subdiv_id),
            'npf' => $this->getNpfNum($res1c->PlanPoNpft, $user_id, $subdiv_id),
            'uki' => $this->getUkiNum($res1c->PlanPoUki, $user_id, $subdiv_id),
            'claims' => $this->getClaimsNum((isset($res1c->PlanClaims))?$res1c->PlanClaims:1, $user_id, $subdiv_id)
        ];
        $docs = $this->matchRegisterWithDB(
                with(new Carbon($m['start']))->format('Ymd'), with(new Carbon($m['end']))->format('Ymd'), $subdivision->name_id, $loans
        );
        foreach ($loans as $loan) {
            $absent = true;
            foreach ($loans_my as $my) {
                if ($my->loan_id_1c == $loan->loan_id_1c) {
                    $absent = false;
                    break;
                }
            }
            $loan->absent = $absent;
        }
        return view('reports.plan_details')
                        ->with('docs', $docs)
                        ->with('loans', $loans)
                        ->with('loans_my', $loans_my)
                        ->with('ukis', $ukis)
                        ->with('ukis_my', $ukis_my)
                        ->with('total_ukis', $total_uki)
                        ->with('total_ukis_my', $total_uki_my)
                        ->with('cards', $subdivCards)
                        ->with('cards_my', $userCards)
                        ->with('total_cards', $totalCards)
                        ->with('total_cards_my', $totalCards_my)
                        ->with('total_money', $total_money)
                        ->with('data', $data)
                        ->with('subdivision', $subdivision)
                        ->with('user', $user)
                        ->with('total_money_my', $total_money_my);
    }

    public function syncLoan($fio, $loan_id_1c, $pass_series = null, $pass_number = null) {
        $res1c = MySoap::getPassportsByFio($fio);
        if (array_key_exists('fio', $res1c)) {
            foreach ($res1c['fio'] as $item) {
                if (is_array($item) && array_key_exists('passport_series', $item) && array_key_exists('passport_number', $item)) {
                    \App\Synchronizer::updateLoanRepayments($item['passport_series'], $item['passport_number'], $loan_id_1c);
                }
            }
        }
    }

    public function matchRegisterWithDB($start, $end, $subdiv_name_id, $loans) {
        // берем реестр договоров из 1с и тем которых нет в базе выставляем флаг отсутствия
        $docs1c = MySoap::getDocsRegister(['date_start' => $start, 'date_finish' => $end, 'subdivision_id_1c' => $subdiv_name_id, 'user_id_1c' => '', 'type' => MySoap::ITEM_LOAN]);
        if ($docs1c != null) {
            $docs = $docs1c['docs'];
            foreach ($docs as &$doc) {
                $noloan = true;
                foreach ($loans as $loan) {
                    if ((string) $doc['number'] == (string) $loan->loan_id_1c) {
                        $noloan = false;
                        break;
                    }
                }
                if ($noloan) {
                    $doc['absent'] = 1;
                    $this->syncLoan($doc['fio'], $doc['number']);
                } else {
                    $doc['absent'] = 0;
                }
            }
        } else {
            $docs = [];
        }
//        проверяем повторно не появились ли договоры в базе
        foreach ($docs as $doc) {
            if ($doc['absent'] == 1 && Loan::where('id_1c', $doc['number'])->count() > 0) {
                $doc['absent'] = 0;
            }
        }
        return $docs;
    }

}
