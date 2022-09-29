<?php

namespace App\Services;

use App\Debtor;
use App\EmailMessage;
use App\Message;
use App\Role;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailService
{
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
        $user = Auth::user();
        $debtor = Debtor::where('id', $arrayParam['debtor_id'])->first();
        $templateMessage = EmailMessage::where('id', $arrayParam['email_id'])->pluck('template_message');
//        $client = $debtor->customer()->getLastAboutClient();

        $messageText =  $this->replaceKeysTemplateMessage($user, $debtor, $templateMessage, $arrayParam);
        $companyName = config('vars.company_new_name');
//        $email = $client->email;

        Mail::send('emails.sendMessage',['messageText' => $messageText],
        function ($message) use ($companyName){
            $message->subject($companyName);
            $message->from(config('mail.username'));
            $message->to('e.chernenok@fterra.ru');
        });

        if(count(Mail::failures()) > 0){
            return false;
        }
        return true;
    }

    public function replaceKeysTemplateMessage($user, $debtor, $templateMessage, $arrayParam)
    {
        $passport = $debtor->customer()->getLastPassport();
        $fio = $passport->fio;
        $templateMessage = str_replace('{{company_new_name}}', config('vars.company_new_name'), $templateMessage);
        $templateMessage = str_replace('{{company_phone}}', config('vars.company_phone'), $templateMessage);
        $templateMessage = str_replace('{{company_site}}', config('vars.company_site'), $templateMessage);
        $templateMessage = str_replace('{{fio_spec}}', $user->login, $templateMessage);
        $templateMessage = str_replace('{{fio_debtors}}', $fio, $templateMessage);

        if (array_key_exists('dateAnswer', $arrayParam)) {
            $templateMessage = str_replace('{{date_answer}}', $arrayParam['dateAnswer'], $templateMessage);
        }
        if (array_key_exists('datePayment', $arrayParam)) {
            $templateMessage = str_replace('{{date_payment}}', $arrayParam['datePayment'], $templateMessage);
        }
        if (array_key_exists('discountPayment', $arrayParam)) {
            $templateMessage = str_replace('{{discount_payment}}', $arrayParam['discountPayment'], $templateMessage);
        }
        if (array_key_exists('debtor_money_on_day', $arrayParam)) {
            $templateMessage = str_replace('{{debtor_money_on_day}}', $arrayParam['debtor_money_on_day'],
                $templateMessage);
        }

        return $templateMessage;
    }
}
