<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyCashReport extends Model {

    protected $fillable = ['data', 'start_balance', 'end_balance'];
    protected $table = 'daily_cash_reports';

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision', 'subdivision_id');
    }

    /**
     * посчитать баланс по отчету на основе данных из прошлого отчета
     * @return type
     */
    public function calculateReportEndBalance($startBalance = null) {
        if (is_null($startBalance)) {
            $startBalance = $this->start_balance;
        }

        try {
            $data = json_decode($this->data, true);
        } catch (\Exception $exc) {
            $data = null;
        }

        $moneyRes = $startBalance;
        if (is_array($data) || is_object($data)) {
            foreach ($data as $row) {
                if (array_key_exists('money', $row) && array_key_exists('action', $row)) {
                    if ($row['action'] == '0' || $row['action'] == '3') {
                        $m = 1;
                    } else if (in_array($row['action'], ['1', '4', '5'])) {
                        $m = -1;
                    } else {
                        $m = 0;
                    }
                    $rm = (is_array($row['money'])) ? $row['money'][0] : $row['money'];
                    $rm = number_format((float) str_replace(',', '.', $rm), 2, '.', '') * $m * 100;
                    $moneyRes += $rm;
                }
            }
        }
        return $moneyRes;
    }

    /**
     * Сумма на конец дня по предыдущему отчету
     * Если такого отчета нет или сумма в нем не заполнена, то взять начальную сумму из текущего отчета
     * @return integer
     */
    public function getPrevReportEndBalance() {
        $reportDate = (is_null($this->created_at)) ? Carbon::now() : $this->created_at;
        $prevReport = DailyCashReport::where('created_at', '<', $reportDate)->where('subdivision_id', $this->subdivision_id)->orderBy('created_at', 'desc')->first();
        if (!is_null($prevReport) && !is_null($prevReport->report_end_balance)) {
            return $prevReport->report_end_balance;
        } else {
            return $this->start_balance;
        }
    }

    /**
     * Заполнить баланс по отчету
     * @param boolean $save сохранить сразу
     */
    public function fillReportBalance($save = false) {
        $this->report_start_balance = $this->getPrevReportEndBalance();
        $this->report_end_balance = $this->calculateReportEndBalance($this->report_start_balance);
        if ($save) {
            $this->save();
        }
    }

    /**
     * Сравнивает отчёт с кассовой книгой 
     * @param Array $balance массив со стартовым и конечным балансом кассовой книги за заданный период (не обязательный параметр, передаётся для сокращения количества запросов)
     * @return boolean
     */
    public function matchWithCashbook($balanceStart = null, $balanceEnd = null) {
        if (is_null($balanceStart)) {
            $balanceStart = $this->start_balance;
        }
        if (is_null($balanceEnd)) {
            $balanceEnd = $this->end_balance;
        }
        $moneyRes = $this->calculateReportEndBalance($balanceStart);
        Log::info('match with cashbook: ', ['mres' => $moneyRes, 'balance' => $balanceEnd, 'start' => $balanceStart]);
        return ((int) $moneyRes == (int) $balanceEnd);
    }

}
