<?php

namespace App\Services;

use App\Debtor;
use App\DebtorEvent;
use App\EmailMessage;
use App\Loan;
use App\Repositories\AboutClientRepository;
use App\Repositories\DebtorEventEmailRepository;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private $debtorEventEmailRepository;
    private $aboutClientRepository;
    private $mailerService;

    public function __construct(
        DebtorEventEmailRepository $debtorEventEmailRepository,
        AboutClientRepository      $aboutClientRepository,
        MailerService              $mailerService
    )
    {
        $this->debtorEventEmailRepository = $debtorEventEmailRepository;
        $this->aboutClientRepository = $aboutClientRepository;
        $this->mailerService = $mailerService;
    }

    /**
     * @param $userId
     * @return array
     */
    public function getListEmailsMessages($userId)
    {
        $user = User::where('id', $userId)->first();
        $collectMessages = [];
        if ($user->isDebtorsRemote() && $user->isDebtorsPersonal()) {
            $collectMessages = EmailMessage::get();

        } elseif ($user->isDebtorsPersonal() && !$user->isDebtorsRemote()) {
            $collectMessages = EmailMessage::where('role_id', Role::DEBTORS_PERSONAL)->get();

        } elseif (!$user->isDebtorsPersonal() && $user->isDebtorsRemote()) {
            $collectMessages = EmailMessage::where('role_id', Role::DEBTORS_REMOTE)->get();
        }
        return $collectMessages;
    }

    public function sendEmailDebtor(array $arrayParam): bool
    {
        $user = $arrayParam['user'];
        $debtor = Debtor::where('id', $arrayParam['debtor_id'])->first();
        if (empty($debtor->customer)) {
            Log::channel('email')->error("Customer not found:", [
                'customer_id_1c' => $debtor->customer_id_1c,
                'debtor_id' => $debtor->id
            ]);
            return false;
        }
        $loan = Loan::where('id_1c', $debtor->loan_id_1c)->first();
        if (empty($loan)) {
            Log::channel('email')->error("Loan not found:", [
                'loan_id_1c' => $debtor->loan_id_1c,
                'debtor_id' => $debtor->id
            ]);
            return false;
        }
        try {
            $arraySumDebtor = $loan->getDebtFrom1cWithoutRepayment();
            $arrayParam['debtor_sum'] = $arraySumDebtor->money / 100;
        } catch (\Throwable $e) {
            Log::error("Error getting sum debtor: ", [
                'customer_id_1c' => $debtor->customer_id_1c,
                'loan_id_1c' => $debtor->loan_id_1c,
                'error' => $e
            ]);
            return false;
        }
        $templateMessage = EmailMessage::where('id', $arrayParam['email_id'])->first();
        $aboutClient = $this->aboutClientRepository->firstByCustomerId($debtor->customer->id);
        $validateEmail = $aboutClient ? filter_var($aboutClient->email, FILTER_VALIDATE_EMAIL) : false;
        $debtorEmail = $validateEmail ? $aboutClient->email : null;
        $messageText = $this->replaceKeysTemplateMessage($user, $debtor, $templateMessage->template_message, $arrayParam);
        if (empty(trim($debtorEmail))) {
            $this->debtorEventEmailRepository->create($debtor->customer_id_1c, $messageText, false);
            Log::channel('email')->error("Incorrect email debtor:", [
                'customer_id_1c' => $debtor->customer_id_1c,
                'email' => $debtorEmail
            ]);
            return false;
        }

        if (!$this->mailerService->sendEmailMessage($messageText, $debtorEmail)) {
            $this->debtorEventEmailRepository->create($debtor->customer_id_1c, $messageText, false);
            return false;
        }

        $debtorEvent = DebtorEvent::create([
            'debtor_id' => $debtor->id,
            'debtor_id_1c' => $debtor->debtor_id_1c,
            'customer_id_1c' => $debtor->customer_id_1c,
            'loan_id_1c' => $debtor->loan_id_1c,
            'debt_group_id' => $debtor->debt_group_id,
            'user_id' => $user->id,
            'last_user_id' => $user->id,
            'user_id_1c' => $user->id_1c,
            'event_type_id' => 24,
            'report' => 'Отправленно ' . $debtorEmail . ' сообщение :' . $messageText,
            'refresh_date' => Carbon::now(),
            'overdue_reason_id' => 0,
            'event_result_id' => 17,
            'completed' => 1,
        ]);
        $this->debtorEventEmailRepository->create($debtor->customer_id_1c, $messageText, true, $debtorEvent->id);
        return true;
    }

    public function replaceKeysTemplateMessage($user, $debtor, $templateMessage, $arrayParam)
    {
        $passport = $debtor->customer->getLastPassport();
        $fio = $passport->fio;
        $templateMessage = str_replace('{{company_new_name}}', config('vars.company_new_name'), $templateMessage);
        $templateMessage = str_replace('{{company_phone}}', config('vars.company_phone'), $templateMessage);
        $templateMessage = str_replace('{{company_site}}', config('vars.company_site'), $templateMessage);
        $templateMessage = str_replace('{{fio_spec}}', $user->login, $templateMessage);
        $templateMessage = str_replace('{{spec_phone}}', $user->phone, $templateMessage);
        $templateMessage = str_replace('{{fio_debtors}}', $fio, $templateMessage);
        $templateMessage = str_replace('{{debtor_money_on_day}}', $arrayParam['debtor_sum'], $templateMessage);
        $templateMessage = str_replace('{{email_user}}', $arrayParam['userEmail'], $templateMessage);

        if (array_key_exists('dateAnswer', $arrayParam)) {
            $templateMessage = str_replace('{{date_answer}}', $arrayParam['dateAnswer'], $templateMessage);
        }
        if (array_key_exists('datePayment', $arrayParam)) {
            $templateMessage = str_replace('{{date_payment}}', $arrayParam['datePayment'], $templateMessage);
        }
        if (array_key_exists('discountPayment', $arrayParam)) {
            $templateMessage = str_replace('{{discount_payment}}', $arrayParam['discountPayment'], $templateMessage);
        }
        return $templateMessage;
    }
}
