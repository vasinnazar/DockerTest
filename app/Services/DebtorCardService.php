<?php

namespace App\Services;


use App\Customer;
use App\Debtor;
use App\MySoap;
use App\Passport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebtorCardService
{
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

    public function getEqualContactsDebtors($debtor_id)
    {
        $debtor = Debtor::find($debtor_id);

        if (!$debtor) {
            return collect();
        }

        $equalPhones = Customer::where('telephone', $debtor->customer->telephone)
            ->get()
            ->except($debtor->customer->id)
            ->pluck('id_1c');

        $equalPhonesDebtors = Debtor::whereIn('customer_id_1c', $equalPhones)->get();

        $passport = $debtor->passport->first();

        $equalAddressesRegisterToRegister = Debtor::leftJoin('passports', function ($join) {
            $join->on('passports.series', '=', 'debtors.debtors.passport_series');
            $join->on('passports.number', '=', 'debtors.debtors.passport_number');
        })
            ->where('passports.zip', $passport->zip)
            ->where('passports.address_region', $passport->address_region)
            ->where('passports.address_district', $passport->address_district)
            ->where('passports.address_city', $passport->address_city)
            ->where('passports.address_street', $passport->address_street)
            ->where('passports.address_house', $passport->address_house)
            ->where('passports.address_building', $passport->address_building)
            ->where('passports.address_apartment', $passport->address_apartment)
            ->where('passports.address_city1', $passport->address_city1)
            ->where('passports.id', '<>', $passport->id)
            ->get();

        $equalAddressesRegisterToFact = Debtor::leftJoin('passports', function ($join) {
            $join->on('passports.series', '=', 'debtors.debtors.passport_series');
            $join->on('passports.number', '=', 'debtors.debtors.passport_number');
        })
            ->where('passports.fact_zip', $passport->zip)
            ->where('passports.fact_address_region', $passport->address_region)
            ->where('passports.fact_address_district', $passport->address_district)
            ->where('passports.fact_address_city', $passport->address_city)
            ->where('passports.fact_address_street', $passport->address_street)
            ->where('passports.fact_address_house', $passport->address_house)
            ->where('passports.fact_address_building', $passport->address_building)
            ->where('passports.fact_address_apartment', $passport->address_apartment)
            ->where('passports.fact_address_city1', $passport->address_city1)
            ->where('passports.id', '<>', $passport->id)
            ->get();

        $equalAddressesFactToRegister = Debtor::leftJoin('passports', function ($join) {
            $join->on('passports.series', '=', 'debtors.debtors.passport_series');
            $join->on('passports.number', '=', 'debtors.debtors.passport_number');
        })
            ->where('zip', $passport->zip)
            ->where('address_region', $passport->fact_address_region)
            ->where('address_district', $passport->fact_address_district)
            ->where('address_city', $passport->fact_address_city)
            ->where('address_street', $passport->fact_address_street)
            ->where('address_house', $passport->fact_address_house)
            ->where('address_building', $passport->fact_address_building)
            ->where('address_apartment', $passport->fact_address_apartment)
            ->where('address_city1', $passport->fact_address_city1)
            ->where('passports.id', '<>', $passport->id)
            ->get();

        $equalAddressesFactToFact = Debtor::leftJoin('passports', function ($join) {
            $join->on('passports.series', '=', 'debtors.debtors.passport_series');
            $join->on('passports.number', '=', 'debtors.debtors.passport_number');
        })
            ->where('fact_zip', $passport->fact_zip)
            ->where('fact_address_region', $passport->fact_address_region)
            ->where('fact_address_district', $passport->fact_address_district)
            ->where('fact_address_city', $passport->fact_address_city)
            ->where('fact_address_street', $passport->fact_address_street)
            ->where('fact_address_house', $passport->fact_address_house)
            ->where('fact_address_building', $passport->fact_address_building)
            ->where('fact_address_apartment', $passport->fact_address_apartment)
            ->where('fact_address_city1', $passport->fact_address_city1)
            ->where('passports.id', '<>', $passport->id)
            ->get();

        $collection = collect([
            'equal_phones' => $equalPhonesDebtors,
            'equal_addresses_register_to_register' => $equalAddressesRegisterToRegister,
            'equal_addresses_register_to_fact' => $equalAddressesRegisterToFact,
            'equal_addresses_fact_to_register' => $equalAddressesFactToRegister,
            'equal_addresses_fact_to_fact' => $equalAddressesFactToFact
        ]);

        return $collection;
    }
}
