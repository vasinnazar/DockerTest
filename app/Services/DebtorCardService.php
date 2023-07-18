<?php

namespace App\Services;

use App\Debtor;
use App\DebtorRecurrentQuery;
use App\MassRecurrentTask;
use App\MySoap;
use App\Repositories\DebtorRepository;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class DebtorCardService
{
    private DebtorRepository $debtorRepository;
    private $httpClient;

    public function __construct(DebtorRepository $debtorRepository)
    {
        $this->debtorRepository = $debtorRepository;
        $this->httpClient = new Client(
            [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
            ]
        );
    }

    /**
     * @string  $customerId1c
     * @string  $loanId1c
     * @return  array|void
     */
    public function getMultiSum1c($customerId1c, $loanId1c, $date = null)
    {
        $claims = DB::Table('armf.claims')
            ->select(DB::raw('armf.claims.id, armf.claims.multi_loan'))
            ->leftJoin('armf.loans', 'armf.loans.claim_id', '=', 'armf.claims.id')
            ->leftJoin('armf.customers', 'armf.customers.id', '=', 'armf.claims.customer_id')
            ->where('armf.customers.id_1c', $customerId1c)
            ->where('armf.loans.closed', 0)
            ->groupBy('armf.claims.id')
            ->get();

        if (is_null($claims)) {
            return;
        }

        $arLoanIds = [];

        foreach ($claims as $claim) {

            $tmpLoans = DB::Table('armf.loans')
                ->select(DB::raw('*'))
                ->where('claim_id', $claim->id)
                ->where('loantype_id', '<>', 49)
                ->get();

            if (!is_null($tmpLoans)) {
                foreach ($tmpLoans as $tLoan) {
                    if (strpos($tLoan->data, 'spisan') === false) {
                        $arLoanIds[] = $tLoan->id_1c;
                    }
                }
            }
            $tmpLoansPledge = DB::Table('armf.loans')
                ->select(DB::raw('*'))
                ->where('claim_id', $claim->id)
                ->where('loantype_id', 49)
                ->orderBy('created_at', 'asc')
                ->first();
            if (!is_null($tmpLoansPledge)) {
                $arLoanIds[] = $tmpLoansPledge->id_1c;
            }
        }

        if (!count($arLoanIds)) {
            return;
        }

        $arResult = [];
        $summary = 0;
        $total_pc = 0;

        if (!isset($date) || empty($date)) {
            $date = Carbon::now()->format('YmdHis');
        } else {
            $date = Carbon::createFromFormat('Y-m-d', $date)->format('YmdHis');
        }

        foreach ($arLoanIds as $loan_id_1c) {
            $tmpLoan = DB::Table('armf.loans')->select(DB::raw('*'))->where('id_1c', $loan_id_1c)->first();

            $debtor = Debtor::where('loan_id_1c', $loan_id_1c)->first();

            if (is_null($tmpLoan)) {
                $arResult[$loan_id_1c] = [
                    'has_result' => 0
                ];
                continue;
            }


            $xml = [
                'type' => '11',
                'loan_id_1c' => $loan_id_1c,
                'customer_id_1c' => $customerId1c,
                'repayment_id_1c' => '0',
                'repayment_type' => '0',
                'created_at' => $date
            ];

            $loan_debt = MySoap::sendXML(MySoap::createXML($xml), false, 'IAmMole', config('1c.mole_url'));

            $pc = ((float)$loan_debt->pc) * 100;
            $exp_pc = ((float)$loan_debt->exp_pc) * 100;
            $all_pc = $pc + $exp_pc;
            $fine = ((float)$loan_debt->fine) * 100;
            $fine_left = number_format((float)$fine, 2, '', '');
            $od = ((float)$loan_debt->od) * 100;
            $all_fine = $fine;
            $money = $pc + $exp_pc + $od + $fine;
            $exp_days = (int)$loan_debt->exp_time;

            $summary += $money;
            $total_pc += $all_pc;

            $arResult[$loan_id_1c] = [
                'has_result' => 1,
                'debt' => $money,
                'exp_days' => $exp_days,
                'created_at' => $tmpLoan->created_at,
                'debtor_id' => (is_null($debtor)) ? 0 : $debtor->id,
                'responsible_user_id_1c' => (is_null($debtor)) ? '' : '(' . trim($debtor->responsible_user_id_1c) . ')'
            ];
        }

        $arResult['base_type'] = 'Продажная';
        $arResult['summary'] = $summary;
        $arResult['total_pc'] = $total_pc;
        $arResult['current_loan_id_1c'] = $loanId1c;

        return $arResult;
    }

    public function getDebtorsByEqualTelephone(Model $debtor, $telephone): Collection
    {
        if (empty($telephone) || $telephone === 'нет') {
            return collect();
        }
        return $this->debtorRepository
            ->getDebtorsWithEqualPhone($telephone)
            ->filter(
                fn ($debtorWithEqualTelephone)
                => $debtorWithEqualTelephone->customer_id_1c !== $debtor->customer->id_1c
            );
    }
    public function getEqualContactsDebtors(Model $debtor): Collection
    {
        if (!$debtor->customer->about_clients) {
            return collect();
        }
        $debtorsWithEqualTelephone = $this->getDebtorsByEqualTelephone(
            $debtor,
            $debtor->customer->telephone
        );
        $debtorsWithEqualTelephonehome = $this->getDebtorsByEqualTelephone(
            $debtor,
            $debtor->customer->about_clients->last()->telephonehome
        );
        $debtorsWithEqualTelephoneorganiz = $this->getDebtorsByEqualTelephone(
            $debtor,
            $debtor->customer->about_clients->last()->telephoneorganiz
        );
        $debtorsWithEqualTelephonerodstv = $this->getDebtorsByEqualTelephone(
            $debtor,
            $debtor->customer->about_clients->last()->telephonerodstv
        );
        $debtorsWithEqualAnothertelephone = $this->getDebtorsByEqualTelephone(
            $debtor,
            $debtor->customer->about_clients->last()->anothertelephone
        );

        $equalAddressesRegisterToRegister = Debtor::select('debtors.*')
            ->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->where('passports.zip', $debtor->passport->zip)
            ->where('passports.address_region', $debtor->passport->address_region)
            ->where('passports.address_district', $debtor->passport->address_district)
            ->where('passports.address_city', $debtor->passport->address_city)
            ->where('passports.address_street', $debtor->passport->address_street)
            ->where('passports.address_house', $debtor->passport->address_house)
            ->where('passports.address_building', $debtor->passport->address_building)
            ->where('passports.address_apartment', $debtor->passport->address_apartment)
            ->where('passports.address_city1', $debtor->passport->address_city1)
            ->where('passports.id', '<>', $debtor->passport->id)
            ->get();

        $equalAddressesRegisterToFact = Debtor::select('debtors.*')
            ->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->where('passports.fact_zip', $debtor->passport->zip)
            ->where('passports.fact_address_region', $debtor->passport->address_region)
            ->where('passports.fact_address_district', $debtor->passport->address_district)
            ->where('passports.fact_address_city', $debtor->passport->address_city)
            ->where('passports.fact_address_street', $debtor->passport->address_street)
            ->where('passports.fact_address_house', $debtor->passport->address_house)
            ->where('passports.fact_address_building', $debtor->passport->address_building)
            ->where('passports.fact_address_apartment', $debtor->passport->address_apartment)
            ->where('passports.fact_address_city1', $debtor->passport->address_city1)
            ->where('passports.id', '<>', $debtor->passport->id)
            ->get();

        $equalAddressesFactToRegister = Debtor::select('debtors.*')
            ->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->where('zip', $debtor->passport->zip)
            ->where('address_region', $debtor->passport->fact_address_region)
            ->where('address_district', $debtor->passport->fact_address_district)
            ->where('address_city', $debtor->passport->fact_address_city)
            ->where('address_street', $debtor->passport->fact_address_street)
            ->where('address_house', $debtor->passport->fact_address_house)
            ->where('address_building', $debtor->passport->fact_address_building)
            ->where('address_apartment', $debtor->passport->fact_address_apartment)
            ->where('address_city1', $debtor->passport->fact_address_city1)
            ->where('passports.id', '<>', $debtor->passport->id)
            ->get();

        $equalAddressesFactToFact = Debtor::select('debtors.*')
            ->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.debtors.passport_number');
            })
            ->where('fact_zip', $debtor->passport->fact_zip)
            ->where('fact_address_region', $debtor->passport->fact_address_region)
            ->where('fact_address_district', $debtor->passport->fact_address_district)
            ->where('fact_address_city', $debtor->passport->fact_address_city)
            ->where('fact_address_street', $debtor->passport->fact_address_street)
            ->where('fact_address_house', $debtor->passport->fact_address_house)
            ->where('fact_address_building', $debtor->passport->fact_address_building)
            ->where('fact_address_apartment', $debtor->passport->fact_address_apartment)
            ->where('fact_address_city1', $debtor->passport->fact_address_city1)
            ->where('passports.id', '<>', $debtor->passport->id)
            ->get();

        $collection = collect([
            'equal_telephone' => $debtorsWithEqualTelephone,
            'equal_telephonehome' => $debtorsWithEqualTelephonehome,
            'equal_telephoneorganiz' => $debtorsWithEqualTelephoneorganiz,
            'equal_telephonerodstv' => $debtorsWithEqualTelephonerodstv,
            'equal_anothertelephone' => $debtorsWithEqualAnothertelephone,
            'equal_addresses_register_to_register' => $equalAddressesRegisterToRegister,
            'equal_addresses_register_to_fact' => $equalAddressesRegisterToFact,
            'equal_addresses_fact_to_register' => $equalAddressesFactToRegister,
            'equal_addresses_fact_to_fact' => $equalAddressesFactToFact
        ]);
        return $collection;
    }

    public function checkRecurrentButtonEnabled(Debtor $debtor, $loan_in_cash, $loan_required_money)
    {
        if ($loan_in_cash || !$loan_required_money) {
            return false;
        }

        if (auth()->user()->hasRole('debtors_remote')) {
            $userStrPodr = '000000000006';
        } else {
            if (auth()->user()->hasRole('debtors_personal')) {
                $userStrPodr = '000000000007';
            } else {
                $userStrPodr = null;
            }
        }

        if ($debtor->str_podr != $userStrPodr) {
            return false;
        }

        $sentRecurrentQueryToday = DebtorRecurrentQuery::where('debtor_id', $debtor->id)
            ->whereDate('created_at', '=', Carbon::today())
            ->first();

        if ($sentRecurrentQueryToday) {
            return false;
        }

        $factTimezone = $debtor->passport->fact_timezone;

        if (is_null($factTimezone)) {
            return false;
        }

        if ($factTimezone >= -5 && $factTimezone <= -2) {
            $taskTimezone = 'west';
        } else if ($factTimezone >= -1 && $factTimezone <= 5) {
            $taskTimezone = 'east';
        } else {
            return false;
        }

        $recurrentTask = MassRecurrentTask::whereDate('created_at', '=', Carbon::today())
            ->whereIn('str_podr', [$userStrPodr, $userStrPodr . '-1'])
            ->where('timezone', $taskTimezone)
            ->first();

        if ($recurrentTask) {
            return false;
        }

        return true;
    }
}
