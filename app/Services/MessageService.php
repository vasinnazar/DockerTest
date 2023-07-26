<?php

namespace App\Services;

use App\Message;
use App\Repositories\MessageRepository;
use App\User;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Log;

class MessageService
{
    public MessageRepository $messageRepository;
    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function deleteMessageIfExist(User $user, string $loanId1c)
    {
        // если у должника есть уведомление "должник на точке" - "удаляем" его
        if ($user->isDebtorsRemote() || ($user->isDebtorsPersonal() && $user->isChiefSpecialist())) {
            $pfx = $user->isDebtorsPersonal() ? 'pn' : 'ln';
            $type = $pfx . $loanId1c;
            $msg = $this->messageRepository->getMessageByType($type);
            if ($msg) {
                $msg->delete();
            }
        }
        // если у должника есть уведомление "должник на сайте" - "удаляем" его
        if ($user->isDebtorsRemote()) {
            $msg = $this->messageRepository->getMessageByType('sn' . $loanId1c);
            if ($msg) {
                $msg->delete();
            }
        }
    }

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
                        ]
                ]);
            $mailer->send(
                'emails.sendMessage',
                ['messageText' => $messageText],
                function ($message) use ($email) {
                    /** @var Message $message */
                    $message->subject(config('vars.company_new_name'));
                    $message->from(config('mail.username'));
                    $message->to($email);
                }
            );
        } catch (\Exception $exception) {
            Log::error("Email send error : ", [
                'email' => $email,
                'line' => __LINE__,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
        if (count($mailer->failures()) > 0) {
            Log::error("Error send email: ", [
                'email' => $email
            ]);
            return false;
        }
        return true;
    }

}
