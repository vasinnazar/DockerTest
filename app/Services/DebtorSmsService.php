<?php

namespace App\Services;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorEvent;
use App\DebtorSmsTpls;
use App\Model\DebtorEventSms;
use App\Repositories\DebtorEventSmsRepository;
use App\Repositories\DebtorSmsRepository;
use App\StrUtils;
use App\User;
use App\Utils\SMSer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\Boolean;

class DebtorSmsService
{

    private $armClient;
    private $smsRepository;
    private $debtorEventSmsRepository;

    public function __construct(
        ArmClient $armClient,
        DebtorSmsRepository $smsRepository,
        DebtorEventSmsRepository $debtorEventSmsRepository
    ) {
        $this->armClient = $armClient;
        $this->smsRepository = $smsRepository;
        $this->debtorEventSmsRepository = $debtorEventSmsRepository;

    }

    public function getSmsForDebtor(User $user, Debtor $debtor): \Illuminate\Support\Collection
    {
        $recoveryType = null;
        $isUbytki = null;
        if ($user->isDebtorsRemote()) {
            $recoveryType = 'remote';
            $isUbytki = ($debtor->base == 'Архив убытки' || $debtor->base == 'Архив компании') ? true : false;
        }
        if ($user->isDebtorsPersonal()) {
            $recoveryType = 'personal';
            $isUbytki = false;
        }
        if (is_null($recoveryType) && is_null($isUbytki)) {
            return collect();
        }

        return $this->smsRepository->getSms($recoveryType, $isUbytki)->map(function ($item) use ($debtor) {
            $item->text_tpl = str_replace(
                [
                    '##sms_till_date##',
                    '##sms_loan_info##'
                ],
                [
                    Carbon::today()->format('d.m.Y'),
                    $debtor->loan_id_1c . ' от ' . StrUtils::dateToStr($debtor->loan->created_at),
                ],
                $item->text_tpl
            );
            return $item;
        })->reject(function ($item) use ($debtor) {
            if (!$this->hasSmsMustBeSentOnce($debtor, $item->id)) {
                return $item;
            }
        });
    }

    public function hasSmsMustBeSentOnce(Debtor $debtor, int $smsTemplateId)
    {
        if ($smsTemplateId !== 21 && $smsTemplateId !== 45) {
            return true;
        }
        $isBadBaseOne = in_array($debtor->base, [
                'Б-3',
                'Б-риски',
                'КБ-График',
                'Б-График',
                'З-МС',
                'КЗ-График',
                'З-График'
            ]
        );
        $isBadBaseTwo = $debtor->base === 'Б-МС';
        $delaysArray = [
            21 => [
                'first' => 95,
                'second' => 20
            ],
            45 => [
                'first' => 103,
                'second' => 25
            ]
        ];
        $eventSms = $this->debtorEventSmsRepository->findByCustomerAndSmsId($debtor->customer_id_1c, $smsTemplateId);
        if ($eventSms && $debtor->base === $eventSms->debtor_base) {
            $isSendOnce = false;
        } else {

            if ($eventSms) {
                $oldBase = in_array($eventSms->debtor_base, [
                    'Б-3',
                    'Б-риски',
                    'КБ-График',
                    'Б-График',
                    'Б-МС'
                ]);
                $newBase = in_array($debtor->base, [
                    'З-МС',
                    'КЗ-График',
                    'З-График'
                ]);
                if ($oldBase && $newBase) {
                    $eventSms->delete();
                }
            }
            $isSendOnce = true;
        }
        $isFirstCondition = ($debtor->qty_delays >= $delaysArray[$smsTemplateId]['first'] && $isBadBaseOne);
        $isSecondCondition = ($debtor->qty_delays >= $delaysArray[$smsTemplateId]['second'] && $isBadBaseTwo);
        if (($isFirstCondition || $isSecondCondition) && $isSendOnce) {
            return true;
        }
        return false;
    }


    public function sendSms(
        Debtor $debtor,
        User $user,
        string $phone,
        $smsId = null,
        string $smsType = null,
        string $smsText = null,
        float $amount = null
    ) {
        $smsLink = '';
        if ($smsType && ($smsType == 'link' || $smsType == 'msg')) {
            if ($amount) {
                $amount *= 100;
            }
            $smsText = 'Направляем ссылку для оплаты долга в ООО МКК"ФИНТЕРРА"88003014344';
            $result = $this->armClient->getTinkoffLink($debtor, $amount, $phone);

            if ($result && $result->success) {
                $smsLink = $result->url;
            } else {
                return [
                    'title' => 'Ошибка',
                    'msg' => 'Не удалось сформировать ссылку'
                ];
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
            return [
                'title' => 'Ошибка',
                'msg' => 'Не правильный номер'
            ];
        }
        // увеличиваем счетчик отправленных пользователем смс
        $user->increaseSentSms();

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
        $debtorEvent->user_id = $user->id;
        $debtorEvent->user_id_1c = $user->id_1c;
        $debtorEvent->save();

        if ($smsId == 21 || $smsId == 45) {
            DebtorEventSms::create([
                'event_id' => $debtorEvent->id,
                'sms_id' => $smsId,
                'customer_id_1c' => $debtor->customer_id_1c,
                'debtor_base' => $debtor->base
            ]);
        }
        return [
            'title' => 'Готово',
            'msg' => 'Сообщение отправленно'
        ];

    }
}
