<?php

namespace App\Services;

use App\Repositories\MessageRepository;
use App\User;

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

}
