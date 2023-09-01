<?php

namespace App\Services;

use App\Message;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Log;

class MailerService
{
    public function sendEmailMessage(string $messageText, string $email): bool
    {
        try {
            $mailer = app()->make(Mailer::class);
            $mailer->getSwiftMailer()->getTransport()->setStreamOptions(
                [
                    'ssl' =>
                        [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ],
                ]);
            $mailer->send(
                'emails.sendMessage',
                compact('messageText'),
                function ($message) use ($email) {
                    /** @var Message $message */
                    $message->subject(config('vars.company_new_name'));
                    $message->from(config('mail.username'));
                    $message->to($email);
                    $message->bcc(config('mail.username'));
                }
            );
        } catch (\Exception $exception) {
            Log::channel('email')->error("Email send error : ", [
                'email' => $email,
                'line' => __LINE__,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
        if (count($mailer->failures()) > 0) {
            Log::channel('email')->error("Error send email: ", [
                'email' => $email
            ]);
            return false;
        }
        return true;
    }
}
