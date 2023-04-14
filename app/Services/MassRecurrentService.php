<?php

namespace App\Services;

use App\Debtor;
use App\MassRecurrent;
use App\MassRecurrentTask;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MassRecurrentService
{
    private $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Проверяем пользователя на соответствие структурному подразделению
     * @param $str_podr
     * @return bool
     */
    public function checkStrPodrUser($str_podr)
    {
        $str_podr = str_replace('-1', '', $str_podr);

        if ($str_podr == '000000000006' && $this->user->hasRole('debtors_remote')) {
            return true;
        }

        if ($str_podr == '000000000007' && $this->user->hasRole('debtors_personal')) {
            return true;
        }

        return false;
    }

    public function createTask($str_podr, $timezone)
    {
        if ($this->_checkTaskCanStart($str_podr, $timezone)) {
            $task = MassRecurrentTask::create([
                'user_id' => $this->user->id,
                'debtors_count' => 0,
                'str_podr' => $str_podr,
                'timezone' => $timezone,
                'completed' => 0
            ]);

            $debtorsQuery = $this->getDebtorsQuery($str_podr, $timezone);

            if ($debtorsQuery->count()) {
                $task->update([
                    'debtors_count' => $debtorsQuery->count()
                ]);
            } else {
                $task->update([
                    'completed' => 1
                ]);
            }

            return $task;
        }

        return false;
    }

    public function executeTask($task_id)
    {
        $task = MassRecurrentTask::find($task_id);

        $debtorsQuery = $this->getDebtorsQuery($task->str_podr, $task->timezone);

        $debtors = $debtorsQuery->get();

        foreach ($debtors as $debtor) {
            $postdata = [
                'customer_external_id' => $debtor->customer_id_1c,
                'loan_external_id' => $debtor->loan_id_1c,
                'amount' => $debtor->sum_indebt,
                'purpose_id' => 3,
                'is_recurrent' => 1,
                'details' => '{"is_debtor":true,"is_mass_debtor":true}'
            ];

            $url = 'http://192.168.35.69:8080/api/v1/payments';

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
                'X-Requested-With: XMLHttpRequest'
            ));
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            curl_exec($ch);

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                Log::error('DebtorsController.massRecurrentQuery cURL error: ',
                    [curl_error($ch), $httpcode, $debtor]);
            }

            MassRecurrent::create([
                'task_id' => $task_id,
                'debtor_id' => $debtor->id
            ]);

            curl_close($ch);

            sleep(1);
        }

        $task->completed = 1;
        $task->save();
    }

    private function getDebtorsQuery($str_podr, $timezone)
    {
        $debtorsQuery = Debtor::select('debtors.*')
            ->where('is_debtor', 1);

        if ($timezone == 'east') {
            $debtorsQuery->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.passport_number');
            })
                ->whereBetween('passports.fact_timezone', [-1, 5]);
        } else if ($timezone == 'west') {
            $debtorsQuery->leftJoin('passports', function ($join) {
                $join->on('passports.series', '=', 'debtors.passport_series');
                $join->on('passports.number', '=', 'debtors.passport_number');
            })
                ->whereBetween('passports.fact_timezone', [-5, -2]);
        }

        if ($str_podr == '000000000006') {
            $debtorsQuery->where('str_podr', '000000000006')
                ->where('qty_delays', '>=', 22)
                ->where('qty_delays', '<=', 69)
                ->where('base', '<>', 'ХПД')
                ->whereIn('debt_group_id', [2, 4, 5, 6]);
        }

        if ($str_podr == '000000000007') {
            $debtorsQuery->where('str_podr', '000000000007')
                ->where('qty_delays', '>=', 60)
                ->where('qty_delays', '<=', 150)
                ->where('base', '<>', 'ХПД')
                ->whereIn('debt_group_id', [5, 6]);
        }

        if ($str_podr == '000000000006-1') {
            $debtorsQuery->where('str_podr', '000000000006')
                ->whereIn('responsible_user_id_1c', [
                    'Осипова Е. А.                                ',
                    'Ленева Алина Андреевна                      '
                ])
                ->whereIn('base', ['Б-1', 'Б-МС', 'Б-риски', 'Б-График']);
        }

        if ($str_podr == '000000000007-1') {
            $debtorsQuery->where('str_podr', '000000000007')
                ->where('responsible_user_id_1c', 'Ведущий специалист личного взыскания')
                ->where('qty_delays', '>=', 60)
                ->where('qty_delays', '<=', 150)
                ->whereIn('debt_group_id', [5, 6])
                ->whereIn('base', ['Б-3', 'Б-МС', 'Б-риски', 'Б-График']);
        }

        return $debtorsQuery;
    }

    /**
     * Проверка на уже существующую задачу, созданную сегодня, с определенными параметрами
     * @param $str_podr
     * @param $timezone
     * @return bool
     */
    private function _checkTaskCanStart($str_podr, $timezone)
    {
        $task = MassRecurrentTask::whereDate('created_at', '=', Carbon::today())
            ->where('str_podr', $str_podr)
            ->where('timezone', $timezone)
            ->first();

        return (bool)$task;
    }
}
