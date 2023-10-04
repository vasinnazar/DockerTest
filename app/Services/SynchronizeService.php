<?php

namespace App\Services;


use App\about_client;
use App\Card;
use App\Claim;
use App\Clients\ArmClient;
use App\Customer;
use App\Debtor;
use App\Exceptions\DebtorException;
use App\Loan;
use App\LoanType;
use App\Passport;
use App\Subdivision;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SynchronizeService
{
    public ArmClient $armClient;

    public function __construct(ArmClient $armClient)
    {
        $this->armClient = $armClient;
    }

    public function synchronizeDebtor(Debtor $debtor): array
    {
        $loanArm = $this->armClient->getLoanById1c($debtor->loan_id_1c)->first();
        if (empty($loanArm)) {
            throw new DebtorException('synchronize_exception', 'Не удалось получить информацию');
        }
        $infoArm = $this->armClient->getCustomerById($loanArm->claim->customer_id);
        if (empty($infoArm)) {
            throw new DebtorException('synchronize_exception', 'Не удалось получить информацию');
        }

        DB::beginTransaction();
        $customer = Customer::where('id_1c', $debtor->customer_id_1c)->first();

        if ($customer->telephone !== $infoArm['customer']->telephone) {
            $customer->telephone = $infoArm['customer']->telephone;
            $customer->save();
        }

        try {
            $aboutClient = $this->updateOrCreateAboutClient($debtor, $infoArm);
            $passport = $this->updateOrCreatePassport($debtor, $infoArm);
            $claim = $this->updateOrCreateClaim($debtor, $loanArm, $passport->id, $aboutClient->id);
            $loan = $this->updateOrCreateLoan($debtor, $loanArm, $claim);
        } catch (\Throwable $exception) {
            Log::error('Critical error update debtors', [
                'customerId1c' => $debtor->customer_id_1c,
                'message' => $exception->getMessage(),
                'line' => $exception->getLine()
            ]);
            DB::rollBack();
            return [
                'customerId' => $loanArm->claim->customer_id,
            ];
        }
        DB::commit();
        return [
            'customerId' => $loanArm->claim->customer_id,
        ];
    }

    private function updateOrCreateAboutClient(Debtor $debtor, $infoCustomerArmSales)
    {
        return about_client::updateOrCreate([
            'customer_id' => $debtor->customer->id
        ], [
            'customer_id' => $debtor->customer->id,
            'sex' => $infoCustomerArmSales['about']->sex,
            'goal' => $infoCustomerArmSales['about']->goal,
            'zhusl' => $infoCustomerArmSales['about']->zhusl,
            'deti' => $infoCustomerArmSales['about']->deti,
            'fiosuprugi' => $infoCustomerArmSales['about']->fiosuprugi,
            'fioizmena' => $infoCustomerArmSales['about']->fioizmena,
            'avto' => $infoCustomerArmSales['about']->avto,
            'telephonehome' => $infoCustomerArmSales['about']->telephonehome,
            'organizacia' => $infoCustomerArmSales['about']->organizacia,
            'innorganizacia' => $infoCustomerArmSales['about']->innorganizacia,
            'dolznost' => $infoCustomerArmSales['about']->dolznost,
            'vidtruda' => $infoCustomerArmSales['about']->vidtruda,
            'fiorukovoditel' => $infoCustomerArmSales['about']->fiorukovoditel,
            'adresorganiz' => $infoCustomerArmSales['about']->adresorganiz,
            'telephoneorganiz' => $infoCustomerArmSales['about']->telephoneorganiz,
            'credit' => $infoCustomerArmSales['about']->credit,
            'dohod' => $infoCustomerArmSales['about']->dohod,
            'dopdohod' => $infoCustomerArmSales['about']->dopdohod,
            'stazlet' => $infoCustomerArmSales['about']->stazlet,
            'pensionnoeudost' => $infoCustomerArmSales['about']->pensionnoeudost,
            'telephonerodstv' => $infoCustomerArmSales['about']->telephonerodstv,
            'obrasovanie' => $infoCustomerArmSales['about']->obrasovanie,
            'pensioner' => $infoCustomerArmSales['about']->pensioner,
            'postclient' => $infoCustomerArmSales['about']->postclient,
            'armia' => $infoCustomerArmSales['about']->armia,
            'poruchitelstvo' => $infoCustomerArmSales['about']->poruchitelstvo,
            'zarplatcard' => $infoCustomerArmSales['about']->zarplatcard,
            'alco' => $infoCustomerArmSales['about']->alco,
            'drugs' => $infoCustomerArmSales['about']->drugs,
            'stupid' => $infoCustomerArmSales['about']->stupid,
            'badspeak' => $infoCustomerArmSales['about']->badspeak,
            'pressure' => $infoCustomerArmSales['about']->pressure,
            'dirty' => $infoCustomerArmSales['about']->dirty,
            'smell' => $infoCustomerArmSales['about']->smell,
            'badbehaviour' => $infoCustomerArmSales['about']->badbehaviour,
            'soldier' => $infoCustomerArmSales['about']->soldier,
            'other' => $infoCustomerArmSales['about']->other,
            'watch' => $infoCustomerArmSales['about']->watch,
            'stepenrodstv' => $infoCustomerArmSales['about']->stepenrodstv,
            'anothertelephone' => $infoCustomerArmSales['about']->anothertelephone,
            'marital_type_id' => $infoCustomerArmSales['about']->marital_type_id,
            'recomend_phone_1' => $infoCustomerArmSales['about']->recomend_phone_1,
            'recomend_phone_2' => $infoCustomerArmSales['about']->recomend_phone_2,
            'recomend_phone_3' => $infoCustomerArmSales['about']->recomend_phone_3,
            'recomend_fio_1' => $infoCustomerArmSales['about']->recomend_fio_1,
            'recomend_fio_2' => $infoCustomerArmSales['about']->recomend_fio_2,
            'recomend_fio_3' => $infoCustomerArmSales['about']->recomend_fio_3,
            'other_mfo' => $infoCustomerArmSales['about']->other_mfo,
            'other_mfo_why' => $infoCustomerArmSales['about']->other_mfo_why,
            'email' => $infoCustomerArmSales['about']->email,
            'dohod_husband' => $infoCustomerArmSales['about']->dohod_husband,
            'pension' => $infoCustomerArmSales['about']->pension,
        ]);
    }

    private function updateOrCreatePassport(Debtor $debtor, $infoCustomerArmSales)
    {
        $debtor->passport_series = $infoCustomerArmSales['passport']->series;
        $debtor->passport_number = $infoCustomerArmSales['passport']->number;
        $debtor->save();

        return Passport::updateOrCreate([
            'series' => $debtor->passport_series,
            'number' => $debtor->passport_number,
        ], [
            'birth_date' => $infoCustomerArmSales['passport']->birth_date,
            'birth_city' => $infoCustomerArmSales['passport']->birth_city,
            'series' => $infoCustomerArmSales['passport']->series,
            'number' => $infoCustomerArmSales['passport']->number,
            'issued' => $infoCustomerArmSales['passport']->issued,
            'issued_date' => $infoCustomerArmSales['passport']->issued_date,
            'subdivision_code' => $infoCustomerArmSales['passport']->subdivision_code,
            'zip' => $infoCustomerArmSales['passport']->zip,
            'fact_zip' => $infoCustomerArmSales['passport']->fact_zip,
            'address_region' => $infoCustomerArmSales['passport']->address_region,
            'address_district' => $infoCustomerArmSales['passport']->address_district,
            'address_city' => $infoCustomerArmSales['passport']->address_city,
            'address_street' => $infoCustomerArmSales['passport']->address_street,
            'address_house' => $infoCustomerArmSales['passport']->address_house,
            'address_building' => $infoCustomerArmSales['passport']->address_building,
            'address_apartment' => $infoCustomerArmSales['passport']->address_apartment,
            'address_reg_date' => $infoCustomerArmSales['passport']->address_reg_date,
            'fact_address_region' => $infoCustomerArmSales['passport']->fact_address_region,
            'fact_address_district' => $infoCustomerArmSales['passport']->fact_address_district,
            'fact_address_city' => $infoCustomerArmSales['passport']->fact_address_city,
            'fact_address_street' => $infoCustomerArmSales['passport']->fact_address_street,
            'fact_address_house' => $infoCustomerArmSales['passport']->fact_address_house,
            'fact_address_building' => $infoCustomerArmSales['passport']->fact_address_building,
            'fact_address_apartment' => $infoCustomerArmSales['passport']->fact_address_apartment,
            'customer_id' => $debtor->customer->id,
            'fio' => $infoCustomerArmSales['passport']->fio,
            'address_city1' => $infoCustomerArmSales['passport']->address_city1,
            'fact_address_city1' => $infoCustomerArmSales['passport']->fact_address_city1,
        ]);
    }

    private function updateOrCreateClaim(Debtor $debtor, $loanArm, int $passportId, int $aboutClientId)
    {
        $subdivisionArmSales = $this->armClient->getSubdivisions()->filter(function ($subdivision) use ($loanArm) {
            return $subdivision->id === $loanArm->claim->subdivision_id;
        })->first();
        $subdivision = Subdivision::where('name_id', $subdivisionArmSales->id_1c)->first();

        if (!$subdivision && $subdivisionArmSales->is_lead === 1) {
            $subdivision = Subdivision::where('name_id', 'П00000035')->first();
        }
        if (!$subdivision) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить подразделение');
        }
        $userArmSales = $this->armClient->getUserById($loanArm->claim->user_id);
        $user = User::where('id_1c', $userArmSales['id_1c'])->first();
        if (!$user) {
            $user = User::where('id', 18)->first();
        }
        return Claim::updateOrCreate([
            'customer_id' => $debtor->customer->id,
            'id_1c' => $loanArm->claim->id_1c,
        ], [
            'customer_id' => $debtor->customer->id,
            'srok' => $loanArm->claim->srok,
            'summa' => $loanArm->claim->summa,
            'date' => $loanArm->claim->date,
            'comment' => $loanArm->claim->comment,
            'user_id' => $user->id,
            'status' => $loanArm->claim->status,
            'passport_id' => $passportId,
            'about_client_id' => $aboutClientId,
            'subdivision_id' => $subdivision->id,
            'id_1c' => $loanArm->claim->id_1c,
            'seb_phone' => $loanArm->claim->seb_phone,
            'special_percent' => $loanArm->claim->special_percent,
            'claimed_for_remove' => $loanArm->claim->claimed_for_remove,
            'max_money' => $loanArm->claim->max_money,
            'terminal_loantype_id' => $loanArm->claim->terminal_loantype_id,
            'terminal_guid' => $loanArm->claim->terminal_guid,
            'uki' => $loanArm->claim->uki,
            'timestart' => $loanArm->claim->timestart,
            'id_teleport' => $loanArm->claim->id_teleport,
            'agrid' => $loanArm->claim->agrid,
            'scorista_status' => $loanArm->claim->scorista_status,
            'scorista_decision' => $loanArm->claim->scorista_decision,
            'teleport_status' => $loanArm->claim->teleport_status,
        ]);
    }

    private function updateOrCreateLoan(Debtor $debtor, $loanArm, Claim $claim)
    {
        if ($debtor->loan) {
            return $debtor->loan;
        }
        $subdivisionArmSales = $this->armClient->getSubdivisions()->filter(function ($subdivision) use ($loanArm) {
            return $subdivision->id === $loanArm->subdivision_id;
        })->first();
        $subdivision = Subdivision::where('name_id', $subdivisionArmSales->id_1c)->first();
        if (!$subdivision) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить подразделение');
        }
        $userArmSales = $this->armClient->getUserById($loanArm->user_id);
        $user = User::where('id_1c', $userArmSales['id_1c'])->first();
        if (!$user) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить ответственного в заявке');
        }

        $loanType = $this->getOrCreateLoantype($loanArm);
        $card = $this->createCard($debtor, $loanArm);


        return Loan::updateOrCreate([
            'id_1c' => $loanArm->id_1c,
        ], [
            'money' => $loanArm->money,
            'time' => $loanArm->time,
            'claim_id' => $claim->id,
            'loantype_id' => $loanType->id,
            'card_id' => $card->id ?? null,
            'closed' => $loanArm->closed,
            'order_id' => null,
            'subdivision_id' => $subdivision->id,
            'id_1c' => $loanArm->id_1c,
            'enrolled' => $loanArm->enrolled,
            'in_cash' => $loanArm->in_cash,
            'user_id' => $user->id,
            'promocode_id' => $loanArm->promocode_id,
            'fine' => $loanArm->fine,
            'last_payday' => $loanArm->last_payday,
            'special_percent' => $loanArm->special_percent,
            'claimed_for_remove' => $loanArm->claimed_for_remove,
            'on_balance' => $loanArm->on_balance,
            'uki' => $loanArm->uki,
            'cc_call' => $loanArm->cc_call,
            'tranche_number' => $loanArm->tranche_number,
            'first_loan_id_1c' => $loanArm->first_loan_id_1c,
            'first_loan_date' => $loanArm->first_loan_date,
            'created_at' => $loanArm->created_at,
        ]);
    }

    private function createCard(Debtor $debtor, $loanArm)
    {
        if (!isset($loanArm->card) || empty($loanArm->card)) {
            return null;
        }
        return Card::updateOrCreate([
            'card_number' => $loanArm->card->card_number,
            'customer_id' => $debtor->customer->id,
        ],[
            'card_number' => $loanArm->card->card_number,
            'secret_word' => $loanArm->card->secret_word,
            'status' => $loanArm->card->status,
            'customer_id' => $debtor->customer->id,
        ]);
    }

    private function getOrCreateLoantype($loanArm)
    {
        return LoanType::updateOrCreate([
            'id_1c' => $loanArm->loantype->id_1c,
        ], [
            'name' => $loanArm->loantype->name,
            'money' => $loanArm->loantype->money,
            'time' => $loanArm->loantype->time,
            'percent' => $loanArm->loantype->percent,
            'start_date' => $loanArm->loantype->start_date,
            'end_date' => $loanArm->loantype->end_date,
            'contract_form_id' => $loanArm->loantype->contract_form_id,
            'card_contract_form_id' => $loanArm->loantype->card_contract_form_id,
            'basic' => $loanArm->loantype->basic,
            'status' => $loanArm->loantype->status,
            'id_1c' => $loanArm->loantype->id_1c,
            'show_in_terminal' => $loanArm->loantype->show_in_terminal,
            'docs' => $loanArm->loantype->docs,
            'exp_pc' => $loanArm->loantype->exp_pc,
            'exp_pc_perm' => $loanArm->loantype->exp_pc_perm,
            'fine_pc' => $loanArm->loantype->fine_pc,
            'fine_pc_perm' => $loanArm->loantype->fine_pc_perm,
            'pc_after_exp' => $loanArm->loantype->pc_after_exp,
            'special_pc' => $loanArm->loantype->special_pc,
            'additional_contract_id' => $loanArm->loantype->additional_contract_id,
            'additional_contract_perm_id' => $loanArm->loantype->additional_contract_perm_id,
            'perm_contract_form_id' => $loanArm->loantype->perm_contract_form_id,
            'perm_card_contract_form_id' => $loanArm->loantype->perm_card_contract_form_id,
            'additional_card_contract_id' => $loanArm->loantype->additional_card_contract_id,
            'additional_card_contract_perm_id' => $loanArm->loantype->additional_card_contract_perm_id,
            'terminal_promo_discount' => $loanArm->loantype->terminal_promo_discount,
            'has_special_pc_for_dop' => $loanArm->loantype->has_special_pc_for_dop,
            'min_time' => $loanArm->loantype->min_time,
            'min_money' => $loanArm->loantype->min_money,
            'data' => json_encode($loanArm->loantype->data),
        ]);
    }
}


