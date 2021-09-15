<?php

namespace App\Utils;

/**
 * 
 *
 * @author Andrey
 */
class ReqMoneyDetails {

    /**
     * @var int сумма всех начисленных процентов в копейках
     */
    public $all_pc = 0;

    /**
     * @var int начисленные проценты в копейках
     */
    public $pc = 0;

    /**
     * @var int вся сумма задолжности по займу с процентами в копейках
     */
    public $money = 0;

    /**
     * @var int просроченные проценты в копейках
     */
    public $exp_pc = 0;

    /**
     * @var int начисленная пеня в копейках
     */
    public $fine = 0;

    /**
     * @var int задолжность по основному долгу
     */
    public $od = 0;

    /**
     * @var array массив графика платежей по мировому
     */
    public $peace_pays = 0;

    /**
     * @var int вся пеня
     */
    public $all_fine = 0;

    /**
     * @var int количество дней просрочки
     */
    public $exp_days = 0;

    /**
     * @var int количество дней на которые начислены проценты
     */
    public $pc_days = 0;

    /**
     * @var int общее количество дней пользования
     */
    public $all_days = 0;

    /**
     * @var int госпошлинаы
     */
    public $tax = 0;

    /**
     * @var int пр. проценты по платежам
     */
    public $peace_pays_exp_pc = 0;

    /**
     * @var int пеня по платежам
     */
    public $peace_pays_fine = 0;

    /*
     * улучшение кредитной истории налог и остальная сумма
     */
    public $uki = 0;
    public $free_pays = ['pc' => 0, 'exp_pc' => 0, 'od' => 0, 'fine' => 0];
    public $fine_left = 0;
    
    public $percent = 2.00;
    public $exp_percent = 2.20;
    /**
     *
     * @var bool есть ли превышение четырехкратной суммы займа
     */
    public $odx4 = false;
    
    public $commission = 0;

    public function __construct($params = null) {
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->{$k} = $v;
            }
        }
    }

}
