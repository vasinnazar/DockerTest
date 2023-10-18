<?php

namespace App\Services;

use App\Debtor;
use App\MassRecurrentTask;
use App\Model\Status;
use App\Repositories\MassRecurrentRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MassRecurrentService
{
    private $massRecurrentRepository;

    public function __construct(MassRecurrentRepository $massRecurrentRepository)
    {
        $this->massRecurrentRepository = $massRecurrentRepository;
    }

    /**
     * Проверяем пользователя на соответствие структурному подразделению
     * @param $str_podr
     * @return bool
     */
    public function checkStrPodrUser($str_podr)
    {
        $user = auth()->user();

        $str_podr = str_replace('-1', '', $str_podr);

        if ($str_podr == '000000000006' && $user->hasRole('debtors_remote')) {
            return true;
        }

        if ($str_podr == '000000000007' && $user->hasRole('debtors_personal')) {
            return true;
        }

        return false;
    }

    public function createTask(string $str_podr, string $timezone, int $qtyDelaysFrom, int $qtyDelaysTo)
    {
        $user = auth()->user();

        if ($this->_checkTaskCanStart($str_podr, $timezone)) {
            $task = MassRecurrentTask::create([
                'user_id' => $user->id,
                'debtors_count' => 0,
                'str_podr' => $str_podr,
                'timezone' => $timezone,
                'completed' => MassRecurrentTask::NOT_COMPLETED
            ]);

            $debtorsQuery = $this->getDebtorsQuery($str_podr, $timezone, $qtyDelaysFrom, $qtyDelaysTo);

            if ($debtorsQuery->count()) {
                $task->update([
                    'debtors_count' => $debtorsQuery->count()
                ]);
            } else {
                $task->update([
                    'completed' => MassRecurrentTask::COMPLETED
                ]);
            }

            return $task;
        }

        return false;
    }

    public function executeTask(int $taskId, int $qtyDelaysFrom, int $qtyDelaysTo): void
    {
        try {
            $task = MassRecurrentTask::find($taskId);
            $debtorsQuery = $this->getDebtorsQuery($task->str_podr, $task->timezone, $qtyDelaysFrom, $qtyDelaysTo);
            $debtorsQuery->chunkById(100, function($debtors) use ($taskId, $task) {
                $dataInsert = [];
                foreach ($debtors as $debtor) {
                    $dataInsert[] = [
                        'task_id' => $taskId,
                        'sum_indebt' => $debtor->sum_indebt,
                        'debtor_id' => $debtor->id,
                        'status_id' => Status::UNDEFINED
                    ];
                    $task->increment('debtors_processed');
                }
                $this->massRecurrentRepository->insert($dataInsert);
            });
            $task->completed = MassRecurrentTask::COMPLETED;
            $task->save();
            $this->massRecurrentRepository->updateByTask($taskId, [
                'status_id' => Status::NEW_SEND
            ]);
        } catch (\Exception $exception) {
            Log::error('Error executing without accept task', [
                'message' => $exception->getMessage(),
                'taskId' => $taskId
            ]);
        }
    }

    /**
     * @param $tasks
     * @return array|string[]
     */
    public function getExecutingTasksProcessedDebtors($tasks)
    {
        $arTasksCount = [
            'status' => 'progress'
        ];

        foreach ($tasks as $arTask) {
            $task = MassRecurrentTask::find($arTask['value']);
            if ($task->completed) {
                $arTasksCount = [
                    'status' => 'completed'
                ];
                break;
            }
            $arTasksCount['tasks'][$arTask['value']] = $task->debtors_processed;
        }

        return $arTasksCount;
    }

    private function getDebtorsQuery(string $str_podr, string $timezone, int $qtyDelaysFrom, int $qtyDelaysTo)
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
            $qtyDelaysFrom = $qtyDelaysFrom !== 0 ? $qtyDelaysFrom: 22;
            $qtyDelaysTo = $qtyDelaysTo !== 0 ? $qtyDelaysTo: 69;
            $debtorsQuery->where('str_podr', '000000000006')
                ->where('qty_delays', '>=', $qtyDelaysFrom)
                ->where('qty_delays', '<=', $qtyDelaysTo)
                ->where('base', '<>', 'ХПД')
                ->whereIn('debt_group_id', [2, 4, 5, 6, 8]);
        }

        if ($str_podr == '000000000007') {
            $qtyDelaysFrom = $qtyDelaysFrom !== 0 ? $qtyDelaysFrom: 60;
            $qtyDelaysTo = $qtyDelaysTo !== 0 ? $qtyDelaysTo: 150;
            $debtorsQuery->where('str_podr', '000000000007')
                ->where('qty_delays', '>=', $qtyDelaysFrom)
                ->where('qty_delays', '<=', $qtyDelaysTo)
                ->where('base', '<>', 'ХПД')
                ->whereIn('debt_group_id', [5, 6]);
        }

        if ($str_podr == '000000000006-1') {
            $debtorsQuery->where('str_podr', '000000000006')
                ->where('qty_delays', '>=', $qtyDelaysFrom)
                ->where('qty_delays', '<=', $qtyDelaysTo)
                ->whereIn('responsible_user_id_1c', [
                    'Осипова Е. А.                                ',
                    'Ленева Алина Андреевна                      '
                ])
                ->whereIn('base', ['Б-1', 'Б-МС', 'Б-риски', 'Б-График']);
        }

        if ($str_podr == '000000000007-1') {
            $qtyDelaysFrom = $qtyDelaysFrom !== 0 ? $qtyDelaysFrom: 60;
            $qtyDelaysTo = $qtyDelaysTo !== 0 ? $qtyDelaysTo: 150;
            $debtorsQuery->where('str_podr', '000000000007')
                ->where('responsible_user_id_1c', 'Ведущий специалист личного взыскания')
                ->where('qty_delays', '>=', $qtyDelaysFrom)
                ->where('qty_delays', '<=', $qtyDelaysTo)
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

        return (bool)!$task;
    }
}
