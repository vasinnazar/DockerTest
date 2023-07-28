<?php

namespace App\Services;

use App\Debtor;
use App\DebtorRecurrentQuery;
use App\DTO\Passport\FactAddressDto;
use App\DTO\Passport\RegAddressDto;
use App\MassRecurrentTask;
use App\MySoap;
use App\Repositories\AboutClientRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DebtorRepository;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class DebtorCardService
{
    private DebtorRepository $debtorRepository;
    private AboutClientRepository $aboutClientRepository;
    private CustomerRepository $customerRepository;
    private $httpClient;

    public function __construct(DebtorRepository $debtorRepository, AboutClientRepository $aboutClientRepository, CustomerRepository $customerRepository)
    {
        $this->debtorRepository = $debtorRepository;
        $this->aboutClientRepository = $aboutClientRepository;
        $this->customerRepository = $customerRepository;
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

    public function getDebtorsByEqualTelephone($telephone, string $customerId1c): Collection
    {
        if (empty(trim($telephone)) || mb_strtolower(trim($telephone)) === 'нет') {
            return collect();
        }
        $customerId = $this->aboutClientRepository
            ->getCustomerIdWithEqualPhone($telephone)
            ->pluck('customer_id')
            ->toArray();
        $customerId1c1 = $this->customerRepository
            ->getCustomerId1cById($customerId)
            ->pluck('id_1c')
            ->diff($customerId1c)
            ->toArray();

        $debtorsEqualAllPhones = $this->debtorRepository
            ->getDebtorsWithEqualTelephone($telephone)->filter(
            fn ($debtorWithEqualPhone)
            => $debtorWithEqualPhone->customer_id_1c !== $customerId1c
        );
        $debtorsEqualPhonesFromAbout = $this->debtorRepository->getDebtorsByCustomerId1c($customerId1c1);
        $debtorsEqualPhonesFromAbout->map(function ($item) use($debtorsEqualAllPhones){
            $debtorsEqualAllPhones->push($item);
        });
        return $debtorsEqualAllPhones;
    }

    public function getDebtorsByEqualAddress(
        object     $addressDto,
        Collection &$debtorsEqualPhonesAndAddress,
        string     $keyAddressReg,
        string     $keyAddressFact
    )
    {
        if (
            !empty($addressDto->address_region) && !empty($addressDto->address_city) &&
            !empty($addressDto->address_street) && !empty($addressDto->address_house)
        ) {
            $equalAddressesFactToRegister = $this->debtorRepository->getDebtorsWithEqualAddressRegister($addressDto);
            $equalAddressesFactToFact = $this->debtorRepository->getDebtorsWithEqualAddressFact($addressDto);
            $debtorsEqualPhonesAndAddress->put($keyAddressReg, $equalAddressesFactToRegister);
            $debtorsEqualPhonesAndAddress->put($keyAddressFact, $equalAddressesFactToFact);
        }
    }

    public function getEqualContactsDebtors(Model $debtor): Collection
    {
        $debtorsEqualPhonesAndAddress = collect();
        if (!$debtor->customer || !$debtor->passport) {
            return collect();
        }
        if (!$debtor->customer->about_clients) {
            return collect();
        }
        $phonesSearch = [
            'equal_telephone' => $debtor->customer->telephone,
            'equal_telephonehome' => $debtor->customer->about_clients->last()->telephonehome,
            'equal_telephoneorganiz' => $debtor->customer->about_clients->last()->telephoneorganiz,
            'equal_telephonerodstv' => $debtor->customer->about_clients->last()->telephonerodstv,
            'equal_anothertelephone' => $debtor->customer->about_clients->last()->anothertelephone,
        ];
        foreach ($phonesSearch as $key => $phone) {
            $debtorsEqualPhonesAndAddress->put(
                $key,
                $this->getDebtorsByEqualTelephone($phone, $debtor->customer->id_1c)
            );
        }

        $this->getDebtorsByEqualAddress(
            FactAddressDto::fromModel($debtor->passport),
            $debtorsEqualPhonesAndAddress,
            'equal_addresses_fact_to_register',
            'equal_addresses_fact_to_fact'
        );
        $this->getDebtorsByEqualAddress(
            RegAddressDto::fromModel($debtor->passport),
            $debtorsEqualPhonesAndAddress,
            'equal_addresses_register_to_register',
            'equal_addresses_register_to_fact'
        );
        return $debtorsEqualPhonesAndAddress;
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
