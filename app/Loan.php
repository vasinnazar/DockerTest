<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Utils\ReqMoneyDetails;
use App\Http\Controllers\PeacePaysController;
use Illuminate\Support\Facades\DB;
use App\MySoap;
use Illuminate\Support\Facades\Log;
use App\Spylog\Spylog;
use Auth;

/**
 * Займ (кредитник)
 * @var $money int
 * @var $time int
 */
class Loan extends Model {

//    use SoftDeletes;
    protected $table = 'loans';
    protected $fillable = ['money', 'time', 'claim_id', 'loantype_id', 'card_id', 'in_cash', 'closed', 'fine', 'last_payday', 'created_at', 'special_percent', 'uki'];

    const STATUS_OPENED = 0;
    const STATUS_CLOSED = 1;

    public function claim() {
        return $this->belongsTo('App\Claim');
    }

    public function loantype() {
        return $this->belongsTo('App\LoanType');
    }

    public function card() {
        return $this->belongsTo('App\Card');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision');
    }

    public function order() {
        return $this->belongsTo('App\Order');
    }

    public function orders() {
        return $this->hasMany('App\Order', 'loan_id');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function promocode() {
        return $this->belongsTo('App\Promocode');
    }

    public function repayments() {
        return $this->hasMany('App\Repayment', 'loan_id');
    }

//    public function paysavings() {
//        return $this->hasMany('App\PaySaving', 'loan_id');
//    }
//    public function getPlusPaysavings() {
//        $lastRep = $this->getLastRepayment();
//        return PaySaving::where('loan_id', $this->id)->where('created_at', '>', ((is_null($lastRep)) ? $this->created_at : $lastRep->created_at))->where('plus', 1)->get();
//    }

    public function getUnusedOrders($all = false) {
        $orders = [];
        $PKOid = OrderType::getPKOid();
        foreach ($this->orders as $order) {
            if ($order->orderType->id == $PKOid && $order->repayment_id == NULL) {
                $orders[] = $order;
            }
        }
        if ($all) {
            foreach ($this->repayments as $rep) {
                foreach ($rep->orders as $order) {
                    if (!$order->used && $order->orderType->id == $PKOid) {
                        $orders[] = $order;
                    }
                }
            }
        }
        return $orders;
    }

    /**
     * возвращает детализацию прцентных ставок по договору
     * @return type
     */
    public function getPercents() {
        $res = ['pc' => 2];
        if ($this->loantype->basic == 1) {
            $brate = config('options.basic_rate');
            foreach ($brate as $r) {
                if ($this->money >= $r['min'] && $this->money < $r['max']) {
                    $res['pc'] = $r['percent'];
                    break;
                }
            }
        } else if ($this->loantype->special_pc == 1 && !is_null($this->special_percent)) {
            $res['pc'] = $this->special_percent;
        } else {
            $res['pc'] = $this->loantype->percent;
        }
        if ($this->claim->about_client->pensioner == 1 || $this->claim->about_client->postclient == 1) {
            $res['fine_pc'] = (is_null($this->loantype->fine_pc_perm)) ? config('options.fine_percent_perm') : $this->loantype->fine_pc_perm;
            $res['exp_pc'] = (is_null($this->loantype->exp_pc_perm)) ? config('options.exp_percent_perm') : $this->loantype->exp_pc_perm;
            //если кредитник просрочен то установить основной процент равным проценту после просрочки
            if (!is_null($this->loantype->pc_after_exp) && $this->loantype->pc_after_exp != '0.00' &&
                    Carbon::now()->setTime(0, 0, 0)->diffInDays(with(new Carbon($this->created_at))->setTime(0, 0, 0)) > $this->time &&
                    $this->created_at->gte(new Carbon(config('options.perm_new_rules_day')))) {
                $res['pc'] = $this->loantype->pc_after_exp;
            }
        } else {
            $res['fine_pc'] = (is_null($this->loantype->fine_pc)) ? config('options.fine_percent') : $this->loantype->fine_pc;
            $res['exp_pc'] = (is_null($this->loantype->exp_pc)) ? config('options.exp_percent') : $this->loantype->exp_pc;

            //если кредитник просрочен у нового клиента то установить основной процент равным проценту после просрочки
            if (!is_null($this->loantype->pc_after_exp) && $this->loantype->pc_after_exp != '0.00' && Carbon::now()->setTime(0, 0, 0)->diffInDays(with(new Carbon($this->created_at))->setTime(0, 0, 0)) > $this->time) {
                $res['pc'] = $this->loantype->pc_after_exp;
            }
        }
        $res['pc_money'] = $this->money * ($res['pc'] / 100) * $this->time;
        $res['cur_pc_money'] = $this->money * ($res['pc'] / 100) * (Carbon::now()->setTime(0, 0, 0)->diffInDays(with(new Carbon($this->created_at))->setTime(0, 0, 0)));
        $rate = $this->getLoanRate();
        $res['exp_pc'] = $rate->exp_pc;
        $res['fine'] = $rate->fine;
        return $res;
    }

    public function getLoanRate() {
        return LoanRate::getByDate($this->created_at);
    }

    public function getPrintPercent() {
        return with($this->getLoanRate())->pc;


        if ($this->created_at->gte(new Carbon(config('options.new_rules_day_010717')))) {
            return 2.18;
        } else if ($this->created_at->gte(new Carbon(config('options.new_rules_day_010117')))) {
            return 2.17;
        } else if ($this->created_at->gte(new Carbon(config('options.new_rules_day')))) {
            return 2.2;
        } else {
            return $this->loantype->percent;
        }
    }

    /**
     * получить детализированную требуемую сумму
     * @return ReqMoneyDetails
     */
    public function getRequiredMoneyDetails($importantMoneyData = null) {
        /**
         * кстати все расчеты здесь не нужны, потому что суммы по допникам и 
         * кредитникам запрашиваются в 1с, мировое и суз приходят уже посчитанные, 
         * а вот от непросроченного заявления нужно отнимать суммы в регистре
         */
        $reps = Repayment::where('loan_id', $this->id)->orderBy('created_at', 'asc')->get();
        $repsNum = count($reps);
        $lastActiveRep = null;
        if ($repsNum > 0) {
            $lastActiveRep = ($reps[$repsNum - 1]->repaymentType->isSuzStock()) ? $reps[$repsNum - 2] : $reps[$repsNum - 1];
        }
        $pc = $this->getPercents();
        //проценты
        $mPc = 0;
        //просроченные проценты
        $mExpPc = 0;
        //пеня
        $mFine = 0;
        //основной долг
        $mOD = $this->money * 100;
        $mTax = 0;
        $mPaysExpPc = 0;
        $mPaysFine = 0;
        //требуемая сумма
        $reqMoney = $this->money * 100;
        $mTotal = 0;
        $loanDate = with(new Carbon($this->created_at))->setTime(0, 0, 0);
        //сумма всех штрафов по всем договорам, включая кредитный. Если доп. договоров нет, то равен нулю
//        $allFine = 0;
        $mOD = $reqMoney;
        $peacePays = [];
        $repPcDays = 0;
        $repExpPcDays = 0;

        /**
         * запрашивает данные о задолженности через mole если тип последнего документа кредитник, допник или заявление
         * для мирового, закрытия и суза данные приходят в самих договорах через ARMLOANKNUMBER
         */
        if ($repsNum == 0 || (!$lastActiveRep->repaymentType->isPeace() && !$lastActiveRep->repaymentType->isSUZ() && !$lastActiveRep->repaymentType->isClosing())) {
            $xml = ['type' => '4', 'loan_id_1c' => $this->id_1c, 'customer_id_1c' => $this->claim->customer->id_1c, 'repayment_id_1c' => '0', 'repayment_type' => '0', 'created_at' => Carbon::now()->format('YmdHis')];
            if ($repsNum == 0) {
                $calc_res1c = MySoap::sendXML(MySoap::createXML($xml));
            } else if (!$lastActiveRep->repaymentType->isPeace() && !$lastActiveRep->repaymentType->isClosing()) {
                $xml['repayment_type'] = $lastActiveRep->repaymentType->getMySoapItemID();
                $xml['repayment_id_1c'] = $lastActiveRep->id_1c;
                $calc_res1c = MySoap::sendXML(MySoap::createXML($xml));
            }
        }
        if (isset($calc_res1c)) {
            $pc['pc'] = (float) $calc_res1c->percent;
            if ((float) $calc_res1c->exp_percent != 0) {
                $pc['exp_pc'] = (float) $calc_res1c->exp_percent;
            }

            //А ЗДЕСЬ ВНЕЗАПНО МЕНЯЕМ ТИП ПОСЛЕДНЕГО ДОПНИКА ЕСЛИ ВДРУГ ПРОЦЕНТ ПРИШЕЛ 2.2 НА ПРОСРОЧЕННЫЙ ДОПНИК
            //лежит здесь чтобы не плодить запросы с расчетом суммы, нужно будет убрать как только с 1с будут приходить типы допников
            \PC::debug($pc, 'percents');
            if (config('app.version_type') != 'debtors') {
                if ($repsNum > 0 && $lastActiveRep->repaymentType->text_id != config('options.rtype_dopnik5') && $pc['pc'] > 2) {
                    if ($lastActiveRep->repaymentType->isDopnik()) {
                        if ($this->created_at->gte(new Carbon(config('options.new_rules_day_010117')))) {
                            $lastActiveRep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik7'))->first())->id;
                        } else {
                            $lastActiveRep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik5'))->first())->id;
                        }
                        $lastActiveRep->save();
                    }
                } else if ($repsNum > 0 && $lastActiveRep->repaymentType->text_id == config('options.rtype_dopnik5') && $pc['pc'] == 2) {
                    if ($this->created_at->gte(new Carbon(config('options.card_nal_dop_day')))) {
                        $lastActiveRep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik6'))->first())->id;
                    } else {
                        $lastActiveRep->repayment_type_id = with(RepaymentType::where('text_id', config('options.rtype_dopnik4'))->first())->id;
                    }
                    $lastActiveRep->save();
                }
            }
        }

        //считаем задолжность по кредитнику
        if ($repsNum != 0) {
            //по остальным договорам
            //последний договор
            $lastRep = $lastActiveRep;
            //получаем количество дней с последнего договора и отнимаем от них срок заморозки процентов по типу договора
            $repDays = Carbon::now()->diffInDays(with(new Carbon($lastRep->created_at))->setTime(0, 0, 0));

            if ($lastRep->repaymentType->isPeace()) {
                //если мировое
                $lastPayday = (is_null($this->last_payday) || $this->last_payday == '0000-00-00 00:00:00' || with(new Carbon($this->last_payday))->lt($lastRep->created_at)) ? $this->created_at->addDays($this->time) : (new Carbon($this->last_payday));
                $fine_percent = $lastRep->repaymentType->fine_percent;
                $peacePays = PeacePaysController::getPeacePays($lastRep->id);
                $mPc = $lastRep->pc;
                $mExpPc = $lastRep->exp_pc;
                $mFine = $lastRep->fine;
                $mOD = $lastRep->od;
//                foreach ($peacePays as $pay) {
//                    $mTotal += $pay["total"] + $pay['exp_pc'];
//                    $mPaysFine += $pay->fine;
//                }
                $mTotal = $mOD + $mPc + $mExpPc + $mFine;
                $repExpPcDays = $repDays - $lastRep->time;
            } else if ($lastRep->repaymentType->isSUZ()) {
                //если суз
                $mPc = $lastRep->pc;
                $mExpPc = $lastRep->exp_pc;
                $mFine = $lastRep->fine;
                $mTax = $lastRep->tax;
                $mOD = $lastRep->od;
                $mTotal = $mOD + $mPc + $mExpPc + $mFine + $mTax;
                $repExpPcDays = 0;
                $lastPayday = (is_null($this->last_payday) || $this->last_payday == '0000-00-00 00:00:00' || with(new Carbon($this->last_payday))->lt($lastRep->created_at)) ? $this->created_at->addDays($this->time) : (new Carbon($this->last_payday));
                $fine_percent = $lastRep->repaymentType->fine_percent;
            }
        } else {
            $loanDays = Carbon::now()->setTime(0, 0, 0)->diffInDays($loanDate);
            $loanPcDays = ($this->time < $loanDays) ? $this->time : $loanDays;
        }
        $res = new ReqMoneyDetails();
        if ($this->closed) {
            $mPc = 0;
            $mExpPc = 0;
            $mFine = 0;
            $mOD = 0;
            $mTotal = 0;
        }
        $res->pc = round($mPc);
        $res->exp_pc = round($mExpPc) + round($mPaysExpPc);
        $res->all_pc = $res->pc + $res->exp_pc;
        $res->fine = round($mFine);
        $res->od = $mOD;
        $res->all_fine = $res->fine;
        $res->money = round($mTotal);
        if (isset($lastRep) && !is_null($lastRep) && $lastRep->active) {
            $res->money += round($mPaysFine);
        }
        if (!is_null($lastActiveRep) && $lastActiveRep->repaymentType->isSUZ()) {
            $res->money = $lastActiveRep->req_money;
        }
        $res->peace_pays = $peacePays;
        $res->exp_days = ($repsNum > 0) ? $repExpPcDays : ($loanDays - $this->time);
        $res->pc_days = ($repsNum > 0) ? $repPcDays : $loanPcDays;
        $res->all_days = ($repsNum > 0) ? $repDays : $loanDays;
        $res->tax = $mTax;
        $res->peace_pays_exp_pc = $mPaysExpPc;
        $res->peace_pays_fine = $mPaysFine;
        $res->exp_percent = with($this->getLoanRate())->exp_pc;

        if (isset($calc_res1c)) {
            /**
             * ДОБАВЛЯЕМ РАСЧЕТ С 1С
             */
            $res->pc = ((float) $calc_res1c->pc) * 100;
            $res->exp_pc = ((float) $calc_res1c->exp_pc) * 100;
            $res->all_pc = $res->pc + $res->exp_pc;
            $res->fine = ((float) $calc_res1c->fine) * 100;
            $res->fine_left = number_format((float) $res->fine, 2, '', '');
            $res->od = ((float) $calc_res1c->od) * 100;
            $res->all_fine = $res->fine;
            $res->money = $res->pc + $res->exp_pc + $res->od + $res->fine;
            $res->peace_pays = $peacePays;
            $res->exp_days = (int) $calc_res1c->exp_time;
            $res->pc_days = ($repsNum > 0) ? $repPcDays : $loanPcDays;
            $res->all_days = ($repsNum > 0) ? $repDays : $loanDays;
            $res->peace_pays_exp_pc = $mPaysExpPc;
            $res->peace_pays_fine = $mPaysFine;
            $res->percent = (float) $calc_res1c->percent;
            $ep = (float) $calc_res1c->exp_percent;
            if ($ep > 0) {
                $res->exp_percent = (float) $calc_res1c->exp_percent;
            }
            if (isset($calc_res1c->ODx4) && !is_null($calc_res1c->ODx4)) {
                $res->odx4 = ($calc_res1c->ODx4 == 1);
            }
        }
        if (!$this->closed && $this->uki && $this->isUkiActive()) {
            $res->money += config('options.uki_money');
            $res->uki = config('options.uki_money');
        }
        if ($this->isCommissionRequired()) {
            $res->commission = round($res->od * 0.3);
        }

        /**
         * Перезаписываем все это дело если пришла более важная инфа
         * Например суммы по акции новый год без долгов
         */
        $res = Loan::rewriteDebtImportantData($res, $importantMoneyData);
        return $res;
    }

    static function rewriteDebtImportantData(ReqMoneyDetails $res, $importantMoneyData) {
        if (is_array($importantMoneyData)) {
            foreach ($importantMoneyData as $k => $v) {
                $res->{$k} = $v;
            }
            $res->money = $res->exp_pc + $res->pc + $res->od + $res->fine + $res->tax;
        }
        return $res;
    }

    public function getReqMoneyFrom1c() {
        
    }

    public function calculateFine($lastPayday, $mDet, $fine_percent) {
        $res = 0;
        $loanFineDays = $lastPayday->diffInDays(Carbon::now());
        \PC::debug($loanFineDays, 'loanfinedays');
        $res += ((($mDet->od + $mDet->pc + $mDet->exp_pc) * ($fine_percent / 100)) / (365 + date("L"))) * $loanFineDays;
        return round($res);
    }

    /**
     * возвращает требуемую сумму
     * @param boolean $onlyPercents
     * @return type
     */
    public function getRequiredMoney($onlyPercents = false) {
        $details = $this->getRequiredMoneyDetails();
        return ($onlyPercents) ? $details->all_pc : $details->money;
    }

    /**
     * закрытие кредитника
     * @param type $createClosing (пока нигде не задействовано)
     * @return type
     */
    public function close($createClosing = false) {
        return $this->update(['closed' => 1, 'last_payment_fine_left' => '0']);
    }

    public function deleteThrough1c() {
        if (Order::where('loan_id', $this->id)->count() > 0) {
            return false;
        }
        DB::beginTransaction();
        if (!$this->delete()) {
            DB::rollback();
            return false;
        }
        $customer_id_1c = (!is_null($this->claim) && !is_null($this->claim->customer)) ? $this->claim->customer->id_1c : '';
        $res1c = MySoap::removeItem(MySoap::ITEM_LOAN, $this->id_1c, $customer_id_1c);
//        if((config('app.dev') && $res1c->result == 0) || (!config('app.dev') && $res1c['res']==0)){
        if ((int) $res1c->result == 0) {
            DB::rollback();
            return false;
        }
        DB::commit();
        return true;
    }

    public function getEndDate() {
        return with(new Carbon($this->created_at))->setTime(0, 0, 0)->addDays($this->time);
    }

    /**
     * Возвращает сумму возврата в копейках
     * @param boolean $withOD
     * @return integer
     */
    public function getEndDateMoney($withOD = false, $expPc = false) {
        $pc = ($expPc) ? $this->loantype->pc_after_exp : $this->loantype->percent;
        $res = $pc / 100 * $this->time * $this->money;
        if ($withOD) {
            $res += $this->money;
        }
        return $res * 100;
    }

    public function getLastRepayment() {
        return Repayment::where('loan_id', $this->id)->orderBy('created_at', 'desc')->first();
    }

    public function getPaysavingsMoney() {
        $res = ['pc' => 0, 'exp_pc' => 0, 'od' => 0, 'fine' => 0];
        return $res;
    }

    /**
     * количество дней просрочки
     * @return int
     */
    public function getOverdueDays($beforeNextRep = false) {
//        $endDate = $this->created_at->setTime(0, 0, 0)->addDays($this->time);
//        if (Carbon::now()->gt($endDate)) {
//            return Carbon::now()->setTime(0, 0, 0)->diffInDays($endDate);
//        } else {
//            return 0;
//        }
        //дата окончания кредитника
        $endDate = $this->created_at->setTime(0, 0, 0)->addDays($this->time);
        if (Carbon::now()->gt($endDate)) {
            if ($beforeNextRep) {
                $nextRep = Repayment::where('loan_id', $this->id)->where('created_at', '>', $this->created_at->format('Y-m-d H:i:s'))->orderBy('created_at', 'asc')->first();
                if (!is_null($nextRep) && $nextRep->created_at->gt($endDate)) {
                    return $nextRep->created_at->setTime(0, 0, 0)->diffInDays($endDate);
                } else {
                    return 0;
                }
            }
            return Carbon::now()->setTime(0, 0, 0)->diffInDays($endDate);
        } else {
            return 0;
        }
    }

    /**
     * получить количество дней пользования займом (НЕ РАБОТАЕТ)
     * @return type
     */
    public function getUseDays() {
        $startDate = with(new Carbon($this->created_at))->setTime(0, 0, 0);
        $reps = Repayment::where('loan_id', $this->id)->orderBy('created_at', 'asc')->get();
        $days = 0;
        $prevRepDate = $startDate;
        $is_loan_181_days = Carbon::now()->subDays(181)->gte($this->created_at);
        foreach ($reps as $r) {
            $curRepDate = $r->created_at->setTime(0, 0, 0);
            if ($r->repaymentType->add_after_freeze && $r->isActive()) {
                $prevRepDate = $r->getEndDate();
                continue;
            }
            if ($r->repaymentType->isSuz() || ($r->repaymentType->isPeace() && $r->isActive()) || $r->repaymentType->isDopCommission()) {
                break;
            }
            $days += $prevRepDate->diffInDays($curRepDate);
        }
        return $days;
    }

    /**
     * проверяет можно ли оплатить уки по данному кредитнику
     * @return boolean
     */
    public function isUkiActive() {
        if ($this->uki) {
            $lastRep = $this->getLastRepayment();
            //если нет никаких документов и количество дней просрочки не превышает 20 дней
            if (is_null($lastRep) && $this->getOverdueDays() <= config('options.uki_days')) {
                return true;
            }
            //если доп доки есть и последний док - не просроченный
            if (!is_null($lastRep) && $lastRep->repaymentType->isDopnik() && $lastRep->repaymentType->percent == 2) {
                return true;
            }
        } else {
            return false;
        }
//        return ($this->uki && with(new Carbon($this->created_at))->addDays(config('options.uki_days') + $this->time)->gte(Carbon::now()));
    }

    public function isCommissionRequired() {
        return false;
        if ($this->created_at->gt(new Carbon(config('options.new_rules_day'))) && $this->created_at->gt(with(new Carbon($this->created_at))->addDays(120))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * недоделанная проверка на возможность применения скидки. пока работает только для предновогодней акции
     * @return boolean
     */
    public function canUseDiscount($data = null) {
        if (!is_null($data)) {
            if (array_key_exists('exDopnikData', $data) && is_array($data['exDopnikData']) && array_key_exists('can_use_discount', $data['exDopnikData']) && !$data['exDopnikData']['can_use_discount']) {
                return false;
            }
        }
        $lastRep = $this->getLastRepayment();
        if (!is_null($lastRep) && $lastRep->repaymentType->isDopnik()) {
            return true;
        }
        if ($this->loantype->id_1c == 'ARM000020') {
            return false;
        }
        return true;
    }

    /**
     * проверяет есть ли у кредитника просроченный допник
     * @return boolean
     */
    public function hasOverduedDopnik() {
        $reps = Repayment::where('loan_id', $this->id)->get();
        foreach ($reps as $r) {
            if ($r->repaymentType->isDopnik() && $r->getOverdueDays(true) > 0) {
                return true;
            }
        }
        return false;
    }

    public function getDebtFrom1c(Loan $loan = null, $date = null, $repayment = null) {
        if (is_null($loan)) {
            $loan = $this;
        }
        \PC::debug($loan);
        \PC::debug($date, 'date1');
        if (empty($date)) {
            $date = Carbon::now()->format('YmdHis');
        } else {
            $date = Carbon::createFromFormat('Y-m-d', $date)->format('YmdHis');
        }
        \PC::debug($date, 'date2');
        $xml = ['type' => '4', 'loan_id_1c' => $loan->id_1c, 'customer_id_1c' => $loan->claim->customer->id_1c, 'repayment_id_1c' => '0', 'repayment_type' => '0', 'created_at' => $date];
        if (!is_null($repayment)) {
            if ($repayment->repaymentType->isPeace()) {
                return $this->getRequiredMoneyDetails();
            }
            $xml['repayment_type'] = $repayment->repaymentType->getMySoapItemID();
            $xml['repayment_id_1c'] = $repayment->id_1c;
            $calc_res1c = MySoap::sendXML(MySoap::createXML($xml));
        } else {
            $last_rep = Repayment::where('loan_id', $loan->id)->orderBy('created_at', 'desc')->first();
            if (is_null($last_rep)) {
                $calc_res1c = MySoap::sendXML(MySoap::createXML($xml));
            } else if (!$last_rep->repaymentType->isPeace() && !$last_rep->repaymentType->isClosing()) {
                $xml['repayment_type'] = $last_rep->repaymentType->getMySoapItemID();
                $xml['repayment_id_1c'] = $last_rep->id_1c;
                $calc_res1c = MySoap::sendXML(MySoap::createXML($xml));
            } else {
                return $this->getRequiredMoneyDetails();
            }
        }
        $res = new ReqMoneyDetails();
        $res->pc = ((float) $calc_res1c->pc) * 100;
        $res->exp_pc = ((float) $calc_res1c->exp_pc) * 100;
        $res->all_pc = $res->pc + $res->exp_pc;
        $res->fine = ((float) $calc_res1c->fine) * 100;
        $res->fine_left = number_format((float) $res->fine, 2, '', '');
        $res->od = ((float) $calc_res1c->od) * 100;
        $res->all_fine = $res->fine;
        $res->money = $res->pc + $res->exp_pc + $res->od + $res->fine;
        $res->exp_days = (int) $calc_res1c->exp_time;
        $res->percent = (float) $calc_res1c->percent;
        $ep = (float) $calc_res1c->exp_percent;
        if ($ep > 0) {
            $res->exp_percent = $ep;
        } else {
            $res->exp_percent = with($this->getLoanRate())->exp_pc;
        }
        return $res;
    }

    public function getDebtFrom1cWithoutRepayment($date = null) {
        if (empty($date)) {
            $date = Carbon::now()->format('YmdHis');
        } else {
            $date = Carbon::createFromFormat('Y-m-d', $date)->format('YmdHis');
        }
        $xml = ['type' => '11', 'loan_id_1c' => $this->id_1c, 'customer_id_1c' => $this->claim->customer->id_1c, 'repayment_id_1c' => '0', 'repayment_type' => '0', 'created_at' => $date];
        $calc_res1c = MySoap::sendXML(MySoap::createXML($xml));

        $res = new ReqMoneyDetails();
        $res->pc = ((float) $calc_res1c->pc) * 100;
        $res->exp_pc = ((float) $calc_res1c->exp_pc) * 100;
        $res->all_pc = $res->pc + $res->exp_pc;
        $res->fine = ((float) $calc_res1c->fine) * 100;
        $res->fine_left = number_format((float) $res->fine, 2, '', '');
        $res->od = ((float) $calc_res1c->od) * 100;
        $res->all_fine = $res->fine;
        $res->money = $res->pc + $res->exp_pc + $res->od + $res->fine;
        $res->exp_days = (int) $calc_res1c->exp_time;
        $res->percent = (float) $calc_res1c->percent;
        $ep = (float) $calc_res1c->exp_percent;
        if ($ep > 0) {
            $res->exp_percent = $ep;
        }
        if (isset($calc_res1c->pays)) {
            $res->pays = $calc_res1c->pays;
        }
        return $res;
    }

    /**
     * показывает применим ли промокод для кредитника
     * @param \App\Utils\ReqMoneyDetails $mDet
     * @return boolean
     */
    public function canCloseWithPromocode($mDet = null) {
        if (is_null($this->claim->promocode)) {
            return false;
        }
        if ($this->claim->subdivision->is_terminal == 1) {
            return false;
        }
        if (!$this->canUseDiscount()) {
            return false;
        }
        if (is_null($mDet)) {
            $mDet = $this->getRequiredMoneyDetails();
        }
        //если процент договора меньше 2
        if ($mDet->percent < 2) {
            return false;
        }
        //если есть просрочка
        if ($mDet->exp_days > 0) {
            return false;
        }
        //если есть промокод в заявке и он был приеменен меньше максимального количества
        $promoNum = Claim::where('promocode_id', $this->claim->promocode_id)->count();
        if ($promoNum > config('options.promocode_activate_num') + 1) {
            return false;
        }
        return true;
    }

    /**
     * сохраняет кредитник попутно сохраняя его в 1с
     * @param int $promocode 0 - не получать промокод, 1 - получать промокод
     * @return boolean|\App\Loan
     */
    public function saveThrough1c($promocode = 0) {
        $update = (!is_null($this->id_1c));
        $card = $this->card;
        $data = [
            'money' => $this->money,
            'time' => $this->time,
            'claim_id_1c' => $this->claim->id_1c,
            'loantype_id_1c' => $this->loantype->id_1c,
            'card_number' => (isset($card) && !is_null($card) && !$this->in_cash) ? $card->card_number : '',
            'subdivision_id_1c' => $this->subdivision->name_id,
            'secret_word' => (isset($card) && !is_null($card) && !$this->in_cash) ? $card->secret_word : '',
            'created_at' => ($update) ? with(new Carbon($this->created_at))->format('YmdHis') : Carbon::now()->format('YmdHis'),
            'user_id_1c' => $this->user->id_1c,
            'promocode_number' => (int) $promocode,
            'uki' => $this->uki
        ];
        DB::beginTransaction();
        $res1c = MySoap::updateLoan($data);
        if ($res1c['res'] == 0) {
            DB::rollback();
            return false;
        } else {
            if (array_key_exists('promocode_number', $res1c) && $res1c['promocode_number'] != "") {
                $promo = new Promocode();
                $promo->number = $res1c['promocode_number'];
                if ($promo->save()) {
                    $this->promocode_id = $promo->id;
                }
            }
            if (array_key_exists('loan_id_1c', $res1c)) {
                $this->id_1c = $res1c['loan_id_1c'];
            }
            if (!$this->save()) {
                DB::rollback();
                return false;
            }
            DB::commit();
            return $this;
        }
    }

    public function syncWith1c() {
        $update = (!is_null($this->id_1c));
        $card = $this->card;
        $data = [
            'type' => 'CreateCreditAgreement',
            'money' => $this->money,
            'time' => $this->time,
            'claim_id_1c' => $this->claim->id_1c,
            'loantype_id_1c' => $this->loantype->id_1c,
            'card_number' => (isset($card) && !is_null($card) && !$this->in_cash) ? $card->card_number : '',
            'subdivision_id_1c' => $this->subdivision->name_id,
            'secret_word' => (isset($card) && !is_null($card) && !$this->in_cash) ? $card->secret_word : '',
            'created_at' => ($update) ? with(new Carbon($this->created_at))->format('YmdHis') : Carbon::now()->format('YmdHis'),
            'user_id_1c' => ($update) ? $this->user->id_1c : Auth::user()->id_1c,
            'promocode_number' => (is_null($this->promocode)) ? '' : $this->promocode->number,
            'uki' => $this->uki,
            'id_1c' => (is_null($this->id_1c)) ? '' : $this->id_1c
        ];
        $res1c = MySoap::sendExchangeArm(MySoap::createXML($data));
        if ((int) $res1c->result == 0) {
            return false;
        }
        $this->sync = 1;
        if ($this->save()) {
            return $this;
        } else {
            return false;
        }
    }

    /**
     * считает суммы для заключения допника с комиссией; 
     * тут же считает и суммы для возврата и суммы к оплате
     * 
     * @param type $time
     * @param type $od
     * @param \App\Repayment $lastRep
     * @return Utils\DopCommissionCalculation
     */
    public function calculateDopCommissionMoney($time = 15, $od = null, $lastRep = null) {
        $result = new Utils\DopCommissionCalculation($this, $time, $od, $lastRep);
        return $result;
    }

    /**
     * Возвращает следующий номер для кредитника
     * @return string
     */
    static function getNextNumber() {
        $number = 'А0000000001';
        $lastLoan = DB::select('select loans.id_1c from loans order by SUBSTRING(loans.id_1c, 2) desc limit 1');
        \PC::debug($lastLoan, 'lastloan');
        $intNumber = intval(StrUtils::removeNonDigits($lastLoan[0]->id_1c));
        \PC::debug($intNumber, 'int number');
        $number = 'А' . StrUtils::addChars(strval($intNumber + 1), 10, '0', false);
        \PC::debug($number, 'number');
        return $number;
    }

    /**
     * Возвращает следующий номер для транша, для контрагента из этого кредитника
     * @return string
     */
    public function getNextTrancheNumber() {
        $trancheNumber = 1;
        $lastLoanOnCard = Loan::leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->where('loans.id_1c', '<>', $this->id_1c)
                ->where('loans.in_cash', 0)
                ->whereNotNull('loans.tranche_number')
                ->where('claims.customer_id', $this->claim->customer_id)
                ->orderBy('created_at', 'desc')
                ->first();
        if (!is_null($lastLoanOnCard)) {
            $trancheNumber = intval($lastLoanOnCard->tranche_number) + 1;
        }
        return $trancheNumber;
    }

    /**
     * Отправляет все неотправленные кредитники в 1С
     * @return type
     */
    static function syncLoans() {
        $loans = Loan::where('sync', 0)->get();
        $num = count($loans);
        if ($num == 0) {
            return;
        }
        $failed_num = 0;
        $suc_num = 0;
        $failed = [];
        foreach ($loans as $loan) {
            if (!$loan->syncWith1c()) {
                $failed[] = $loan;
                $failed_num++;
            } else {
                $suc_num++;
            }
        }
        $res = [
            'orders_num' => $num,
            'failed_num' => $failed_num,
            'failed' => $failed,
            'suc_num' => $suc_num
        ];
        Log::info('Order.syncOrders', ['res' => $res]);
        return $res;
    }

    /**
     * Возвращает есть ли на кредитнике просрочка
     * @return boolean
     */
    public function hasOverdue() {
        $lastRep = $this->getLastRepayment();
        if (is_null($lastRep)) {
            return ($this->getOverdueDays() > 0);
        } else {
            return ($lastRep->getOverdueDays() > 0);
        }
    }

    static function getById1cAndCustomerId1c($loan_id_1c, $customer_id_1c) {
        $schema = \Illuminate\Support\Facades\Schema::getColumnListing('loans');
        $cols = [];
        foreach ($schema as $col) {
            $cols[] = 'loans.' . $col;
        }
        return Loan::select($cols)->where('loans.id_1c', $loan_id_1c)->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')->where('customers.id_1c', $customer_id_1c)->first();
    }

    static function getById1cAndCustomerId1c2($loan_id_1c, $customer_id_1c) {
        $loan_id = Loan::where('loans.true_id_1c', $loan_id_1c)
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('customers', 'customers.id', '=', 'claims.customer_id')
                ->where('customers.id_1c', $customer_id_1c)
                ->pluck('loans.id');
        return Loan::whereIn('id', $loan_id)->first();
    }

    /**
     * возвращает процент ПСК
     * @param boolean $formated отформатировать для печати или отдать как есть
     * @return type
     */
    public function getPSK($formated = false) {
        $pc = with($this->getLoanRate())->pc;
        $psk = $pc * 365;
        return ($formated) ? number_format($psk, 3, ',', '') : $psk;
    }

    public function enroll() {
        if ((bool) $this->enrolled) {
            return Order::find($this->order_id);
        }
        $user = \App\User::select('id_1c')->where('id', $this->user_id)->first();
        $subdiv = \App\Subdivision::select('name_id')->where('id', $this->subdivision_id)->first();
        if (is_null($user) || is_null($subdiv)) {
            //Не найден пользователь или подразделение.
            return null;
        }
        DB::beginTransaction();
        if ($this->subdivision->is_terminal) {
            $res1c = MySoap::enrollTerminal($this->id_1c, Carbon::now()->format('YmdHis'), $this->user->id_1c, $this->subdivision->name_id);
        } else {
            $res1c = MySoap::enrollLoan([
                        'loan_id_1c' => $this->id_1c,
                        'created_at' => Carbon::now()->format('YmdHis'),
                        'user_id_1c' => $user->id_1c,
                        'subdivision_id_1c' => $subdiv->name_id,
                        'customer_id_1c' => $this->claim->customer->id_1c
                            ], !$this->in_cash);
        }
        if ($res1c['res'] == 0) {
            Log::error('enrollLoan.mysoap', ['res1c' => $res1c]);
            return null;
        }

        $order = new Order();
        $order->type = ($this->in_cash) ? OrderType::getRKOid() : OrderType::getCARDid();
        $order->number = $res1c['order_id_1c'];
        $order->user_id = $this->user_id;
        $order->subdivision_id = $this->subdivision_id;
        $order->passport_id = $this->claim->passport_id;
        $order->money = $this->money * 100;
        $order->loan_id = $this->id;
        if (!$order->save()) {
            DB::rollback();
            Log::error('enrollLoan.order.cash', ['res1c' => $res1c, 'loan_id' => $loan_id, 'order' => $order]);
            return null;
        }
        $this->order_id = $order->id;
        $this->enrolled = 1;

        if (!is_null($this->promocode_id) && !$this->claim->customer->isPostClient()) {
            Utils\SMSer::send($this->claim->customer->telephone, '500 рублей за друга! Ваш промо код ' . $this->promocode->number);
        }

        if ($this->save()) {
            DB::commit();
            Spylog::log(Spylog::ACTION_ENROLLED, 'loans', $this->id);
            return $order;
        } else {
            DB::rollback();
            return null;
        }
    }

}
