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
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SynchronizeService
{
    public ArmClient $armClient;

    public function __construct(ArmClient $armClient)
    {
        $this->armClient = $armClient;
    }

    public function synchronizeDebtor(Debtor $debtor)
    {
        if ($debtor->passport && $debtor->loan) {
            return true;
        }
        $loanArm = $this->armClient->getLoanById1c($debtor->loan_id_1c);
        $customerArm = $this->armClient->getCustomerById($loanArm->claim->customer_id);

        DB::beginTransaction();
        $customer = Customer::where('id_1c', $debtor->customer_id_1c)->first();

        if ($customer->telephone !== $customerArm->telephone) {
            $customer->telephone = $customerArm->telephone;
            $customer->save();
        }

        try {
            $aboutClient = $this->updateOrCreateAboutClient($debtor, $customerArm);
            $passport = $this->updateOrCreatePassport($debtor, $customerArm);
            $claim = $this->updateOrCreateClaim($debtor, $customerArm, $passport->id, $aboutClient->id);
            $loan = $this->updateOrCreateLoan($debtor, $loanArm, $claim);
        } catch (\Throwable $exception) {
            Log::error('Critical error update debtors', [
                'customerId1c' => $debtor->customer_id_1c,
                'message' => $exception->getMessage()
            ]);
            DB::rollBack();
            return false;
        }
        DB::commit();
        return true;
    }

    private function updateOrCreateAboutClient(Debtor $debtor, Collection $customerArmSales)
    {
        return about_client::updateOrCreate([
            'customer_id' => $debtor->customer->id
        ], [
            'customer_id' => $debtor->customer->id,
            'sex' => $customerArmSales->about->sex,
            'goal' => $customerArmSales->about->goal,
            'zhusl' => $customerArmSales->about->zhusl,
            'deti' => $customerArmSales->about->deti,
            'fiosuprugi' => $customerArmSales->about->fiosuprugi,
            'fioizmena' => $customerArmSales->about->fioizmena,
            'avto' => $customerArmSales->about->avto,
            'telephonehome' => $customerArmSales->about->telephonehome,
            'organizacia' => $customerArmSales->about->organizacia,
            'innorganizacia' => $customerArmSales->about->innorganizacia,
            'dolznost' => $customerArmSales->about->dolznost,
            'vidtruda' => $customerArmSales->about->vidtruda,
            'fiorukovoditel' => $customerArmSales->about->fiorukovoditel,
            'adresorganiz' => $customerArmSales->about->adresorganiz,
            'telephoneorganiz' => $customerArmSales->about->telephoneorganiz,
            'credit' => $customerArmSales->about->credit,
            'dohod' => $customerArmSales->about->dohod,
            'dopdohod' => $customerArmSales->about->dopdohod,
            'stazlet' => $customerArmSales->about->stazlet,
            'adsource' => $customerArmSales->about->adsource,
            'pensionnoeudost' => $customerArmSales->about->pensionnoeudost,
            'telephonerodstv' => $customerArmSales->about->telephonerodstv,
            'obrasovanie' => $customerArmSales->about->obrasovanie,
            'pensioner' => $customerArmSales->about->pensioner,
            'postclient' => $customerArmSales->about->postclient,
            'armia' => $customerArmSales->about->armia,
            'poruchitelstvo' => $customerArmSales->about->poruchitelstvo,
            'zarplatcard' => $customerArmSales->about->zarplatcard,
            'alco' => $customerArmSales->about->alco,
            'drugs' => $customerArmSales->about->drugs,
            'stupid' => $customerArmSales->about->stupid,
            'badspeak' => $customerArmSales->about->badspeak,
            'pressure' => $customerArmSales->about->pressure,
            'dirty' => $customerArmSales->about->dirty,
            'smell' => $customerArmSales->about->smell,
            'badbehaviour' => $customerArmSales->about->badbehaviour,
            'soldier' => $customerArmSales->about->soldier,
            'other' => $customerArmSales->about->other,
            'watch' => $customerArmSales->about->watch,
            'stepenrodstv' => $customerArmSales->about->stepenrodstv,
            'anothertelephone' => $customerArmSales->about->anothertelephone,
            'marital_type_id' => $customerArmSales->about->marital_type_id,
            'recomend_phone_1' => $customerArmSales->about->recomend_phone_1,
            'recomend_phone_2' => $customerArmSales->about->recomend_phone_2,
            'recomend_phone_3' => $customerArmSales->about->recomend_phone_3,
            'recomend_fio_1' => $customerArmSales->about->recomend_fio_1,
            'recomend_fio_2' => $customerArmSales->about->recomend_fio_2,
            'recomend_fio_3' => $customerArmSales->about->recomend_fio_3,
            'other_mfo' => $customerArmSales->about->other_mfo,
            'other_mfo_why' => $customerArmSales->about->other_mfo_why,
            'email' => $customerArmSales->about->email,
            'dohod_husband' => $customerArmSales->about->dohod_husband,
            'pension' => $customerArmSales->about->pension,
        ]);
    }

    private function updateOrCreatePassport(Debtor $debtor, Collection $customerArmSales)
    {
        return Passport::updateOrCreate([
            'series' => $debtor->passport_series,
            'number' => $debtor->passport_number,
        ], [
            'birth_date' => $customerArmSales->passport->birth_date,
            'birth_city' => $customerArmSales->passport->birth_city,
            'series' => $customerArmSales->passport->series,
            'number' => $customerArmSales->passport->number,
            'issued' => $customerArmSales->passport->issued,
            'issued_date' => $customerArmSales->passport->issued_date,
            'subdivision_code' => $customerArmSales->passport->subdivision_code,
            'zip' => $customerArmSales->passport->zip,
            'fact_zip' => $customerArmSales->passport->fact_zip,
            'address_region' => $customerArmSales->passport->address_region,
            'address_district' => $customerArmSales->passport->address_district,
            'address_city' => $customerArmSales->passport->address_city,
            'address_street' => $customerArmSales->passport->address_street,
            'address_house' => $customerArmSales->passport->address_house,
            'address_building' => $customerArmSales->passport->address_building,
            'address_apartment' => $customerArmSales->passport->address_apartment,
            'address_reg_date' => $customerArmSales->passport->address_reg_date,
            'fact_address_region' => $customerArmSales->passport->fact_address_region,
            'fact_address_district' => $customerArmSales->passport->fact_address_district,
            'fact_address_city' => $customerArmSales->passport->fact_address_city,
            'fact_address_street' => $customerArmSales->passport->fact_address_street,
            'fact_address_house' => $customerArmSales->passport->fact_address_house,
            'fact_address_building' => $customerArmSales->passport->fact_address_building,
            'fact_address_apartment' => $customerArmSales->passport->fact_address_apartment,
            'customer_id' => $debtor->customer->id,
            'created_at' => $customerArmSales->passport->created_at,
            'updated_at' => $customerArmSales->passport->updated_at,
            'fio' => $customerArmSales->passport->fio,
            'address_city1' => $customerArmSales->passport->address_city1,
            'fact_address_city1' => $customerArmSales->passport->fact_address_city1,
        ]);
    }

    private function updateOrCreateClaim(Debtor $debtor, Collection $loanArm, int $passportId, int $aboutClientId)
    {
        $subdivisionArmSales = $this->armClient->getSubdivisions()->map(function ($subdivision) use ($loanArm) {
            return $subdivision->id === $loanArm->claim->subdivision_id;
        });
        $subdivision = Subdivision::where('name_id', $subdivisionArmSales->id_1c)->first();
        if (!$subdivision) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить подразделение');
        }
        $userArmSales = $this->armClient->getUserById($loanArm->claim->user_id);
        $user = User::where('id_1c', $userArmSales->id_1c)->first();
        if (!$subdivision) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить ответственного в заявке');
        }

        return Claim::updateOrCreate([
            'customer_id' => $debtor->customer_id_1c,
        ], [
            'customer_id' => $debtor->customer_id_1c,
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
            'promocode_id' => $loanArm->claim->promocode_id,
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

    private function updateOrCreateLoan(Debtor $debtor, Collection $loanArm, Claim $claim)
    {
        $subdivisionArmSales = $this->armClient->getSubdivisions()->map(function ($subdivision) use ($loanArm) {
            return $subdivision->id === $loanArm->subdivision_id;
        });
        $subdivision = Subdivision::where('name_id', $subdivisionArmSales->id_1c)->first();
        if (!$subdivision) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить подразделение');
        }
        $userArmSales = $this->armClient->getUserById($loanArm->user_id);
        $user = User::where('id_1c', $userArmSales->id_1c)->first();
        if (!$subdivision) {
            throw new DebtorException('synchronize_exception', 'Не удалось определить ответственного в заявке');
        }
        $loanType = $this->getOrCreateLoantype($loanArm);
        $card = $this->createCard($debtor, $loanArm);

        return Loan::updateOrCreate([
            'customer_id' => $debtor->customer_id_1c,
        ], [
            'customer_id' => $debtor->customer_id_1c,
            'money' => $loanArm->money,
            'time' => $loanArm->time,
            'claim_id' => $claim->id,
            'loantype_id' => $loanType->id,
            'card_id' => $card->id,
            'closed' => $loanArm->closed,
            'order_id' => $loanArm->order_id,
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
        ]);
    }

    private function createCard(Debtor $debtor, Collection $loanArm)
    {
        return Card::create([
            'card_number' => $loanArm->card->card_number,
            'secret_word' => $loanArm->card->secret_word,
            'status' => $loanArm->card->status,
            'customer_id' => $debtor->customer->id,
        ]);
    }

    private function getOrCreateLoantype(Collection $loanArm)
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
            'data' => $loanArm->loantype->data,
        ]);
    }
}


