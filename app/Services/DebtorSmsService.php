<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorEvent;
use App\DebtorSmsTpls;
use App\Utils\SMSer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\Boolean;

class DebtorSmsService
{

    private $armClient;

    public function __construct(ArmClient $armClient)
    {
        $this->armClient = $armClient;
    }

    public function sendSms(
        Debtor $debtor,
        string $phone,
        string $smsType = null,
        string $smsText = null,
        int $amount = 0
    ) {
        $smsLink = '';
        if ($smsType && ($smsType == 'link' || $smsType == 'msg')) {
            $amount *= 100;
            $smsText = 'Направляем ссылку для оплаты долга в ООО МКК"ФИНТЕРРА"88003014344';
            $result = $this->armClient->getTinkoffLink($debtor, $amount, $phone);

            if ($result && $result->success) {
                $smsLink = $result->url;
            } else {
                return response()->json([
                    'title' => 'Ошибка',
                    'msg' => 'Не удалось сформировать ссылку'
                ]);
            }

            if ($smsType == 'msg') {
                return $smsText . ' ' . $smsLink;
            }
        }

        if ($smsType && $smsType == 'props') {
            $smsText = 'Направляем реквизиты для оплаты долга в ООО МКК"ФИНТЕРРА"88003014344'
                . ' путем оплаты в отделении банка https://финтерра.рф/faq/rekvizity';
            $smsLink = '';
        }

        if (mb_strlen($smsLink) > 0) {
            $smsLink = ' ' . $smsLink;
        }

        if (!SMSer::send($phone, $smsText . $smsLink)) {
            return response()->json([
                'title' => 'Ошибка',
                'msg' => 'Не правильный номер'
            ]);
        }
        // увеличиваем счетчик отправленных пользователем смс
        Auth::user()->increaseSentSms();

        // создаем мероприятие отправки смс
        $debtorEvent = new DebtorEvent();
        $data = [];
        $data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
        $data['event_type_id'] = 12;
        $data['overdue_reason_id'] = 0;
        if ($smsType && ($smsType == 'link' || $smsType == 'msg' || $smsType == 'props')) {
            $data['event_result_id'] = 17;
        } else {
            $data['event_result_id'] = 22;
        }
        $data['debt_group_id'] = $debtor->debt_group_id;
        $data['report'] = $phone . ' SMS: ' . $smsText;
        $data['completed'] = 1;
        $debtorEvent->fill($data);
        $debtorEvent->refresh_date = Carbon::now()->format('Y-m-d H:i:s');
        $debtorEvent->customer_id_1c = $debtor->customer_id_1c;
        $debtorEvent->debtor_id_1c = $debtor->debtor_id_1c;
        $debtorEvent->debtor_id = $debtor->id;
        $debtorEvent->user_id = Auth::user()->id;
        $debtorEvent->user_id_1c = Auth::user()->id_1c;
        $debtorEvent->save();

        return response()->json([
            'title' => 'Готово',
            'msg' => 'Сообщение отправленно'
        ]);

    }
}
