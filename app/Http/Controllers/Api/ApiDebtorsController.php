<?php

namespace App\Http\Controllers\Api;

use App\Clients\ArmClient;
use App\Debtor;
use App\DebtorsSiteLoginLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OnSiteRequest;
use App\Http\Requests\Api\OnSubdivisionRequest;
use App\Message;
use App\Repositories\DebtorEventsRepository;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiDebtorsController extends Controller
{

    /**
     * Создает сообщение для специалиста и начальников, если должник пришел на точку
     * @param Request $req
     */
    public function onSubdivision(OnSubdivisionRequest $req, ArmClient $armClient)
    {
        $input = $req->validated();
        Log::info('alertDebtorOnSubdivision ', ['data' => $input]);

        $userArm = collect($armClient->getUserById1c($input['user_id_1c']))->first();
        $specName = $userArm->login ??'не определен';
        $subdivisionName = $userArm->subdivision->name ?? 'не определена';
        $subdivisionNameId = $userArm->subdivision->name_id ?? '-';
        $subdivisionPhone = $userArm->subdivision->telephone ?? '-';

        $debtor = Debtor::where('customer_id_1c', $input['customer_id_1c'])
            ->where('loan_id_1c', $input['loan_id_1c'])
            ->first();

        if ($debtor) {
            // префикс в зависимости от отдела взыскания: 1 -личное/0 - удаленное
            $pfxLoan = $input['is_debtor_personal'] ? 'pn' : 'ln';

            $msgExists = Message::where('type', $pfxLoan . $input['loan_id_1c'])
                ->where('created_at', '>=', Carbon::now()->subMinutes(60))
                ->first();
            if (!$msgExists) {
                $userTo = User::where('id_1c', $debtor->responsible_user_id_1c)->first();

                $fio = $debtor->passport->fio ?? 'Имя Не Найдено';

                return Message::create([
                    'text' => 'Должник <a href="/debtors/debtorcard/' . $debtor->id . '" target="_blank">' . $fio
                        . '</a> пришел на точку: ' . $subdivisionName
                        . '<br>Код подразделения: ' . $subdivisionNameId
                        . '<br>Телефон: ' . $subdivisionPhone
                        . '<br>Специалист: ' . $specName,
                    'recepient_id' => $userTo->id,
                    'type' => $pfxLoan . $input['loan_id_1c'],
                    'message_type' => $pfxLoan
                ]);
            }
        }
    }

    public function onSite(OnSiteRequest $req, DebtorEventsRepository $eventsRepository)
    {
        $arrayStrPodr = ['000000000006', '000000000007'];
        $input = $req->validated();

        $debtor = Debtor::where('customer_id_1c', $input['customer_id_1c'])
            ->where('is_debtor', 1)
            ->first();

        $siteLogTodayExists = DebtorsSiteLoginLog::where('customer_id_1c', $input['customer_id_1c'])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->first();

        if (!$debtor || !in_array($debtor->str_podr, $arrayStrPodr) || !is_null($siteLogTodayExists)) {
            return 0;
        }

        $debtorItems = Debtor::where('customer_id_1c', $input['customer_id_1c'])
            ->where('is_debtor', 1);

        DebtorsSiteLoginLog::create([
            'customer_id_1c' => $input['customer_id_1c'],
            'str_podr' => $debtor->str_podr,
            'sum_loans_debt' => $debtorItems->sum('sum_indebt'),
            'debt_loans_count' => $debtorItems->count(),
            'debt_group_id' => $debtor->debt_group_id,
        ]);

        if ($debtor->str_podr == '000000000006') {
            $pfxLoan = 'sn';
        }
        if ($debtor->str_podr == '000000000007') {
            $pfxLoan = 'vn';
        }


        $msgExists = Message::where('type', $pfxLoan . $debtor->loan_id_1c)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if (is_null($msgExists)) {

            $userTo = User::where('id_1c', $debtor->responsible_user_id_1c)->first();
            $fio = $debtor->passport->fio ?? 'Имя Не Найдено';

            $msg = Message::create([
                'text' => 'Должник <a href="/debtors/debtorcard/'
                    . $debtor->id . '" target="_blank">' . $fio
                    . '</a> зашел на сайт.',
                'recepient_id' => $userTo->id,
                'type' => $pfxLoan . $debtor->loan_id_1c,
                'message_type' => $pfxLoan,
            ]);


            // добавляем мероприятие
            if ($pfxLoan === 'sn') {
                $currentTime = now()->format('Y-m-d H:i:s');
                $report = 'Должник ' . $fio . ' зашел на сайт. ' . date('d.m.Y H:i',
                        strtotime($msg->created_at)) . ', отв: ' . $userTo->name;

                $event = $eventsRepository->createEvent(
                    $debtor,
                    $userTo,
                    $report,
                    9,
                    0,
                    17,
                    1
                );

                if ($debtor->base !== 'Архив компании' && $debtor->base !== 'Архив убытки') {
                    $event->debt_group_id = 2;
                }

                $event->date = '0000-00-00 00:00:00';
                $event->created_at = $currentTime;
                $event->refresh_date = $currentTime;
                $event->save();

                if ($debtor->base !== 'Архив компании' && $debtor->base !== 'Архив убытки') {
                    Debtor::where('customer_id_1c', $input['customer_id_1c'])
                        ->where('is_debtor', 1)
                        ->update([
                            'debt_group_id' => 2,
                            'refresh_date' => $currentTime,
                        ]);
                }
                $debtor->debt_group_id = 2;
                $debtor->refresh_date = $currentTime;
                $debtor->save();

                $planEvent = $eventsRepository->createEvent(
                    $debtor,
                    $userTo,
                    $report,
                    6,
                    0,
                );

                $planEvent->date = $currentTime;
                $planEvent->created_at = $currentTime;
                $planEvent->refresh_date = $currentTime;
                $planEvent->save();
            }
        }
        return 1;
    }
}
