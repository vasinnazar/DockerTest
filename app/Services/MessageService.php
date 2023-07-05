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
                    $message->bcc(config('mail.username'));
                }
            );
        } catch (\Exception $exception) {
            Log::error("$exception->errorName:", [
                'email' => $email,
                'file' => __FILE__,
                'method' => __METHOD__,
                'line' => __LINE__,
                'id' => $exception->errorId,
                'message' => $exception->errorMessage,
            ]);
            return false;
        }
        if (count($mailer->failures()) > 0) {
            return false;
        }
        return true;
    }

}
