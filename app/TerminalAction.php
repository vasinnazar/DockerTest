<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TerminalAction extends Model {

    protected $table = 'terminal_actions';
    protected $fillable = ['ActionID', 'ActionType', 'CreditID', 'ClientID', 'DateIns', 'ActionType', 'ActionText', 'Amount', 'ExtInt','PayPointID'];
//    protected $dates = ['DateIns', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * регистрация нового пользователя
     */
    const ACTION_REGISTER = 0;
    /*
     * операции с заявкой/займом
     */
    const ACTION_CLAIM = 1;

    /**
     * подписание договора
     */
    const ACTION_SIGN = 5;

    /**
     * снятие наличных
     */
    const ACTION_CASH_OUT = 10;

    /**
     * пополнение баланса
     */
    const ACTION_CASH_IN = 50;

    /**
     * гашение
     */
    const ACTION_REPAY = 51;

    /**
     * продление
     */
    const ACTION_PROLONGATION = 52;

    /**
     * штраф за просрочку
     */
    const ACTION_FINE = 99;
    
    /**
     * инкассация
     */
    const ACTION_INCASS = 1001;
    
    /**
     * пополнение
     */
    const ACTION_REFILL = 1002;

}
