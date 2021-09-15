<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MySoap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Log;
use Auth;
use App\Spylog\Spylog;

class Order extends Model {

    const P_OD = 0;
    const P_PC = 1;
    const P_EXPPC = 2;
    const P_FINE = 3;
    const P_TAX = 4;
    const P_UKI = 5;
    const P_UKI_NDS = 6;
    const P_COMMISSION = 7;
    const P_COMMISSION_NDS = 8;

    protected $fillable = ['type', 'number', 'user_id', 'subdivision_id', 'customer_id', 'money', 'passport_id', 'reason', 'purpose', 'repayment_id', 'loan_id', 'used', 'created_at', 'peace_pay_id', 'comment'];
    protected $table = 'orders';

    public function orderType() {
        return $this->belongsTo('App\OrderType', 'type');
    }

    public function passport() {
        return $this->belongsTo('App\Passport');
    }

    public function subdivision() {
        return $this->belongsTo('App\Subdivision');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function repayment() {
        return $this->belongsTo('App\Repayment');
    }

    public function loan() {
        return $this->belongsTo('App\Loan');
    }

    public function peacePay() {
        return $this->belongsTo('App\PeacePay', 'peace_pay_id');
    }

    /**
     * возвращает массив идентификаторов назначений приходников
     * @return type
     */
    public static function getPurposeTypes() {
        return [
            'pc' => Order::P_PC,
            'exp_pc' => Order::P_EXPPC,
            'fine' => Order::P_FINE,
            'od' => Order::P_OD,
            'tax' => Order::P_TAX,
            'uki' => Order::P_UKI,
            'uki_nds' => Order::P_UKI_NDS,
            'com' => Order::P_COMMISSION,
            'com_nds' => Order::P_COMMISSION_NDS
        ];
    }

    /**
     * возвращает массив наименований назначения приходников
     * @return array
     */
    public static function getPurposeNames() {
        return ['Основной долг', 'Проценты', 'Просроченные проценты', 'Штрафы, пени, неустойки', 'Гос. пошлина', 'Оплата УКИ', 'УКИ НДС', 'Комиссия', 'НДС по комиссии'];
    }

    /**
     * возвращает номер счета для данного ордера
     * @return type
     */
    public function getInvoice() {
        if ($this->type != 5) {
            return $this->orderType->invoice;
        } else {
            $invoicesList = Order::getInvoicesList();
            if (is_null($this->purpose) || $this->purpose > count($invoicesList)) {
                return $this->orderType->invoice;
            } else {
                return $invoicesList[$this->purpose];
            }
        }
    }

    /**
     * возвращает массив счетов для назначения приходников
     * @return array
     */
    static function getInvoicesList() {
        return [
            Order::P_PC => '62.01',
            Order::P_EXPPC => '62.04',
            Order::P_FINE => '76.02',
            Order::P_OD => '58.03',
            Order::P_TAX => '76.10',
            Order::P_UKI => '76.09',
            Order::P_UKI_NDS => '76.09',
            Order::P_COMMISSION => '76.09',
            Order::P_COMMISSION_NDS => '76.09'
        ];
    }

    /**
     * возвращает массив названий счетов для назначения приходников
     * @return array
     */
    static function getInvoicesNamesList() {
        return [
            Order::P_PC => 'Проценты',
            Order::P_EXPPC => 'Пр. проценты',
            Order::P_FINE => 'Пеня',
            Order::P_OD => 'Основной долг',
            Order::P_TAX => 'Гос. пошлина',
            Order::P_UKI => 'Оплата УКИ',
            Order::P_UKI_NDS => 'Оплата НДС',
            Order::P_COMMISSION => 'Комиссия',
            Order::P_COMMISSION_NDS => 'Комиссия НДС',
        ];
    }

    /**
     * сохраняет ордер в базе и в 1с, а так же добавляет номер ордера из 1с в базу
     * @return boolean
     */
    public function saveThrough1c($created_at = null) {
//        if (config('admin.orders_without_1c') == 1 || Auth::user()->subdivision_id==107) {
//            return $this->saveWithNumber();
//        }        
        $this->created_at = (is_null($created_at)) ? Carbon::now()->format('Y-m-d H:i:s') : with(new Carbon($created_at))->format('Y-m-d H:i:s');
        if($this->orderType->text_id == OrderType::RKO){
            return Order::saveByCreateOrder($this);
        } else {
            return Order::saveByCreateKO($this);
        }
    }
    /**
     * Сохраняет приходники или расходники, которые не на кредитник в 1с
     * @param \App\Order $order
     * @return boolean
     */
    static function saveByCreateKO($order){
        if (!is_null($order->repayment)) {
            $docType = $order->repayment->repaymentType->getMySoapItemID();
        } else {
            $docType = MySoap::ITEM_LOAN;
        }
        $data = [
            'created_at' => with(new Carbon($order->created_at))->format('YmdHis'),
            'Number' => (empty($order->number)) ? "" : $order->number,
            'passport_series' => $order->passport->series,
            'passport_number' => $order->passport->number,
            'money' => $order->money,
            'reason' => (is_null($order->reason)) ? "" : $order->reason,
            'repayment_id_1c' => (!is_null($order->repayment) && !is_null($order->repayment->id_1c)) ? $order->repayment->id_1c : '',
            'loan_id_1c' => (!is_null($order->loan)) ? $order->loan->id_1c : '',
            'peace_pay_id_1c' => (!is_null($order->peacePay)) ? $order->peacePay->id_1c : '',
            'order_Type' => $order->type,
            'type' => 'Create_KO',
            'purpose' => $order->purpose,
            'subdivision_id_1c' => $order->subdivision->name_id,
            'user_id_1c' => $order->user->id_1c,
            'doc_type' => $docType
        ];
        $res1c = MySoap::sendExchangeArm(MySoap::createXML($data));
        if (!is_null($res1c) && isset($res1c->result) && $res1c->result && isset($res1c->value)) {
            $order->number = $res1c->value;
            $order->sync = 1;
        } else {
            $order->number = '!!УДАЛИТЬ!!';
        }
        if(!$order->subdivision->is_terminal){
            $order->updateDailyCashReport();
        }
        return $order->save();
    }
    /**
     * Сохранить расходник для кредитника на нал
     * @param \App\Order $order
     * @return boolean
     */
    static function saveByCreateOrder($order){
        $res1c = MySoap::sendExchangeArm(MySoap::createXML([
                    'loan_id_1c' => $order->loan->id_1c,
                    'created_at' => Carbon::now()->format('YmdHis'),
                    'user_id_1c' => $order->user->id_1c,
                    'subdivision_id_1c' => $order->subdivision->name_id,
                    'customer_id_1c' => $order->loan->claim->customer->id_1c,
                    'number' => $order->number,
                    'type' => 'Create_order'
                ]));
        if ((int)$res1c->result == 0) {
            return false;
        } else {
            $order->sync = 1;
        }
        return $order->save();
    }
    
    public function deleteThrough1c() {
        DB::beginTransaction();
        if (!$this->delete()) {
            DB::rollback();
            return false;
        }
        if (!is_null($this->number) && $this->number != '' && $this->number != '!!УДАЛИТЬ!!') {
            $doc_type = ($this->orderType->plus) ? MySoap::ITEM_PKO : MySoap::ITEM_RKO;
            $rep = $this->repayment;
            if(!is_null($rep) && $rep->id_1c==''){
                $rep = null;
            }
            if(is_null($rep)){
                if(!is_null($this->loan)){
                    $lastRep = $this->loan->getLastRepayment();
                    if(!is_null($lastRep) && $lastRep->repaymentType->isPeace()){
                        $rep = $lastRep;
                    }
                }
            }
            if(!is_null($rep) && $rep->repaymentType->text_id == config('options.rtype_peace')){
                $res1c = MySoap::removeItem($doc_type, $this->number, $this->getCustomerID1C(), $rep->repaymentType->getMySoapItemID(), $rep->id_1c);
            } else {
                $res1c = MySoap::removeItem($doc_type, $this->number, $this->getCustomerID1C());
            }
            if ($res1c->result == 0) {
                DB::rollback();
                return false;
            }
        }
        DB::commit();
        return true;
    }

    public function getCustomerID1C() {
        if (!is_null($this->passport) && !is_null($this->passport->customer)) {
            return $this->passport->customer->id_1c;
        }
        return '';
    }

    public function getMySoapItemID() {
        return ($this->orderType->plus) ? MySoap::ITEM_PKO : MySoap::ITEM_RKO;
    }

    public function saveThrough1cWithRegister($created_at = null) {
        if (config('admin.orders_without_1c') == 1 || Auth::user()->subdivision_id==107) {
            return $this->saveWithNumber();
        }
        return Order::saveByMoleRegister($this);
    }
    /**
     * Сохраняет ордер через mole. используется для приходников по заявлениям, мировым, допникам с комиссией.
     * @param \App\Order $order
     * @return boolean
     */
    static function saveByMoleRegister($order){
        if(is_null($order->created_at)){
            $order->created_at = Carbon::now();
        }
        $xmlArray = [
            'type' => 0,
            'number' => (empty($order->number))?'':$order->number,
            'purpose' => $order->purpose,
            'created_at' => $order->created_at->format('YmdHis'),
            'customer_id_1c' => $order->loan->claim->customer->id_1c,
            'money' => number_format($order->money / 100, 2, '.', ''),
            'loan_id_1c' => $order->loan->id_1c,
            'subdivision_id_1c' => $order->subdivision->name_id,
            'user_id_1c' => Auth::user()->id_1c,
            'fine_left' => 0,
            'repayment_id_1c' => 'null',
            'repayment_type' => 'null'
        ];
        if (!is_null($order->repayment)) {
            $xmlArray['repayment_id_1c'] = $order->repayment->id_1c;
            $xmlArray['repayment_type'] = $order->repayment->repaymentType->getMySoapItemID();
        }
        if ($order->comment == 'peace_exp_pc') {
            $xmlArray['comment'] = 'peace_exp_pc';
        }
        $res1c = MySoap::sendXML(MySoap::createXML($xmlArray));
        if (!is_null($res1c) && ((bool) $res1c->result)) {
            $order->number = (string) $res1c->number;
            $order->sync = 1;
            $order->updateDailyCashReport();
            return $order->save();
        } else {
            return false;
        }
    }
    /**
     * Сохранить ордер и сгенерировать номер в базе. В базе есть триггер, который сгенерирует номер
     * @return boolean
     */
    public function saveWithNumber() {
//        $this->number = $this->getNextNumber($this->orderType->plus);
        $this->number = '';
        $this->sync = 0;
        return $this->save();
    }
    /**
     * Сгенерировать номер ордера. НЕ ИСПОЛЬЗУЕТСЯ. В БАЗЕ ДЛЯ ЭТОГО ЕСТЬ ТРИГГЕР
     * @param boolean $isPko
     * @return string
     */
    public function getNextNumber($isPko = 1) {
        $number = 'А0000000001';
        $lastOrder = DB::select('select orders.number,orders.created_at from orders,order_types where order_types.id=orders.type and order_types.plus=' . $isPko . ' and number<>\'!!УДАЛИТЬ!!\' and YEAR(orders.created_at)=' . Carbon::now()->year . ' order by SUBSTRING(orders.number, 2) desc limit 1');
        \PC::debug($lastOrder, 'lastorder');
        $intNumber = intval(StrUtils::removeNonDigits($lastOrder[0]->number));
        \PC::debug($intNumber, 'int number');
        $number = 'А' . StrUtils::addChars(strval($intNumber + 1), 10, '0', false);
        \PC::debug($number, 'number');
        return $number;
    }
    /**
     * Выставить галочку что ежедневный отчет не совпадает для подразделения на котором создается ордер.
     * @return boolean
     */
    public function updateDailyCashReport() {
        $dcr = DailyCashReport::where('user_id', Auth::user()->id)
                ->where('subdivision_id', Auth::user()->subdivision_id)
                ->whereBetween('created_at', [
                    Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                    Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
                ])
                ->first();
        if (!is_null($dcr)) {
            $dcr->matches = 0;
            $dcr->save();
            return true;
        }
        return false;
    }

    public function getPurposeName() {
        $names = Order::getPurposeNames();
        if (is_null($this->purpose) || $this->purpose > count($names)) {
            return '';
        }
        if ($this->purpose == Order::P_EXPPC) {
            return 'Проценты';
        }
//        if ($this->repayment->repaymentType->isDopCommission()) {
//            return 'Оплата комиссии';
//        }
        return $names[$this->purpose];
    }
    /**
     * Отправляет все неотправленные в 1С ордера
     * @return int
     */
    static function syncOrders() {
        if(Order::where('sync',0)->count()==0){
            return;
        }        
        $orders = Order::where('sync', 0)->get();
        $orders_num = count($orders);
        $failed_num = 0;
        $suc_num = 0;
        $failed = [];
        foreach ($orders as $order) {
            $orderSaved = false;
            
            if($order->orderType->text_id == OrderType::RKO){
                $orderSaved = Order::saveByCreateOrder($order);
            } else if(Order::mustBeSavedThroughRegister($order)){
                $orderSaved = Order::saveByMoleRegister($order);
            } else {
                $orderSaved = Order::saveByCreateKO($order);
            }
            if ($orderSaved) {
                $suc_num++;
            } else {
                $failed[] = $order->number;
                $failed_num++;
            }
        }
        $res = [
            'orders_num'=>$orders_num,
            'failed_num' => $failed_num,
            'failed' => $failed,
            'suc_num' => $suc_num
        ];
        Log::info('Order.syncOrders',['res'=>$res]);
        return $res;
    }
    /**
     * Определяет нужно ли сохранить приходник через моле
     * @param \App\Order $order
     * @return boolean
     */
    static function mustBeSavedThroughRegister($order){
        $res = false;
        if(!is_null($order->repayment) && !is_null($order->repayment->repaymentType)){
            $repType = $order->repayment->repaymentType;
            if($repType->isClaim() || $repType->isDopCommission() || $repType->isPeace()){
                $res = true;
            }
        }
        return $res;
    }

    public function eqType($type_text_id) {
        if (is_array($type_text_id)) {
            return (!is_null($this->orderType) && in_array($this->orderType->text_id, $type_text_id));
        } else {
            return (!is_null($this->orderType) && $this->orderType->text_id == $type_text_id);
        }
    }
    
    public function canBeClaimedForRemove(){
        if(is_null(Auth::user()) || !is_null($this->claimed_for_remove) || !$this->created_at->isToday()){
            return false;
        }
        if(is_null($this->repayment_id)){
            return true;
        } else {
            if($this->created_at->setTime(0,0,0)->eq($this->repayment->created_at->setTime(0,0,0)) && !Auth::user()->isAdmin()){
                return false;
            } else {
                return true;
            }
        }
    }
    
    static function uploadQiwiAndBank(){
        Synchronizer::updateOrders(Carbon::yesterday()->format('Y-m-d H:i:s'), null, null, '7494');
        Synchronizer::updateOrders(Carbon::yesterday()->format('Y-m-d H:i:s'), null, null, '000000012');
    }

}
