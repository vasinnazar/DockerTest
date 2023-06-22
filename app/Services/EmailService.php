<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorEvent;
use App\EmailMessage;
use App\Loan;
use App\Message;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private $armClient;

    public function __construct(ArmClient $client)
    {
        $this->armClient = $client;
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

    /**
     * @param array $arrayParam
     * @return bool
     */
    public function sendEmailDebtor($arrayParam)
    {
        $user = $arrayParam['user'];
        $debtor = Debtor::where('id', $arrayParam['debtor_id'])->first();
        $loan = Loan::where('id_1c', $debtor->loan_id_1c)->first();
        $arraySumDebtor = $loan->getDebtFrom1cWithoutRepayment();
        $arrayParam['debtor_sum'] = $arraySumDebtor->money / 100;

        $templateMessage = EmailMessage::where('id', $arrayParam['email_id'])->first();
        $messageText = $this->replaceKeysTemplateMessage($user, $debtor, $templateMessage->template_message, $arrayParam);
        $armfCustomer = DB::Table('armf.customers')->where('id_1c', $debtor->customer_id_1c)->first();
        $aboutClient = DB::Table('armf.about_clients')->where('customer_id', $armfCustomer->id)->first();
        $userArm = $this->armClient->getUserById1c($user->id_1c);

        if (!isset($userArm[0]['email_user']['email']) && empty($userArm[0]['email_user']['email'])) {
            return false;
        }
        $this->setConfig($userArm[0]['email_user']['email'], $userArm[0]['email_user']['password']);
        try {
            $mailer = app()->make(Mailer::class);
            $mailer->getSwiftMailer()->getTransport()->setStreamOptions(
                [
                    'ssl' =>
                        [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                ]);
            $mailer->send(
                'emails.sendMessage',
                ['messageText' => $messageText],
                function ($message) use ($aboutClient) {
                    /** @var Message $message */
                    $message->subject(config('vars.company_new_name'));
                    $message->from(config('mail.username'));
                    $message->to($aboutClient->email);
                    $message->bcc(config('mail.username'));
                }
            );
        } catch (\Exception $exception) {
            return false;
        }

        if (count($mailer->failures()) > 0) {
            return false;
        }
        DebtorEvent::create([
            'debtor_id' => $debtor->id,
            'debtor_id_1c' => $debtor->debtor_id_1c,
            'customer_id_1c' => $debtor->customer_id_1c,
            'loan_id_1c' => $debtor->loan_id_1c,
            'debt_group_id' => $debtor->debt_group_id,
            'user_id' => $user->id,
            'last_user_id' => $user->id,
            'user_id_1c' => $user->id_1c,
            'event_type_id' => 24,
            'report' => 'Отправленно ' . $aboutClient->email . ' сообщение :' . $messageText,
            'refresh_date' => Carbon::now(),
            'overdue_reason_id' => 0,
            'event_result_id' => 17,
            'completed' => 1,
        ]);
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

    public function setConfig($email, $password)
    {
        config()->set('mail.username', $email);
        config()->set('mail.password', $password);
    }
}
