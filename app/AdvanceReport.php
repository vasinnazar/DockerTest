<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;
use Log;

class AdvanceReport extends Model {

    protected $table = 'advance_reports';
    protected $fillable = ['data', 'subdivision_id', 'user_id', 'customer_id', 'subdivision_store_id', 'created_at', 'id_1c'];

    public function subdivision() {
        return $this->belongsTo('App\Subdivision', 'subdivision_id');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function customer() {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function subdivision_store() {
        return $this->belongsTo('App\SubdivisionStore', 'subdivision_store_id');
    }

    /**
     * возвращает список номенклатуры для вставки в селекты
     * @return type
     */
    static function getNomenclatureList() {
        return Nomenclature::pluck('name', 'id_1c');
    }

    /**
     * возвращает ордеры на подотчет которых нет ни в одном отчете
     * @param \App\Subdivision $subdiv
     * @param \App\User $user
     * @param \App\AdvanceReport $report
     * @param boolean $handle
     * @return type
     */
    static function getIssueOrders($subdiv, $user, $report = null, $handle = true) {
        if (is_null($report) || is_null($report->id)) {
            $reports = AdvanceReport::where('subdivision_id', $subdiv->id)->where('user_id', $user->id)->get();
        } else {
            $reports = AdvanceReport::where('subdivision_id', $subdiv->id)->where('user_id', $user->id)->where('id', '<>', $report->id)->get();
        }
        $orders_id_1c_list = [];
        foreach ($reports as $rep) {
            $data = json_decode($rep->data);
            if (is_object($data) && isset($data->advance) && !is_null($data->advance)) {
                foreach ($data->advance as $advOrder) {
                    if (isset($advOrder->order_id_1c)) {
                        $orders_id_1c_list[] = $advOrder->order_id_1c;
                    }
                }
            }
        }
        $issue_text_ids = array_merge(IssueClaim::getIssueOrderTypesTextIds(), [OrderType::PODOTCHET]);
        \PC::debug($issue_text_ids);
        $orderTypes = OrderType::whereIn('text_id', $issue_text_ids)->pluck('id')->toArray();
        $orders = Order::whereIn('type', $orderTypes)
                ->where('subdivision_id', $subdiv->id)
                ->whereNotIn('number', $orders_id_1c_list)
//                ->where('user_id', $user->id)
                ->get();
        if (!is_null($orders) && !is_null($report)) {
            $orders->merge($report->getOrdersFromReport());
        }
        \PC::debug($orders);
        if ($handle) {
            $res = [];
            foreach ($orders as $order) {
                $res[$order->id] = 'Расходно кассовый ордер ' . $order->number . ' от ' . $order->created_at->format('d.m.Y H:i:s');
            }
            return $res;
        }
        return $orders;
    }

    public function getOrdersFromReport() {
        $orders_id_1c_list = [];
        $data = json_decode($this->data);
        if (is_object($data) && isset($data->advance) && !is_null($data->advance)) {
            foreach ($data->advance as $advOrder) {
                if (isset($advOrder->order_id_1c)) {
                    $orders_id_1c_list[] = $advOrder->order_id_1c;
                }
            }
        }
        return Order::whereIn('number', $orders_id_1c_list)->get();
    }

    /**
     * сохраняет отчет в 1с и в базе
     * @return boolean
     */
    public function saveThrough1c() {
        $res1c = $this->sendTo1c();
        if (!isset($res1c->result) || (int) $res1c->result === 0) {
            return ['result' => false, 'error' => (string) $res1c->error];
        } else {
            $this->id_1c = (string) $res1c->advance_id_1c;
            return ['result' => $this->save()];
        }
    }

    /**
     * отправляет отчет в 1с
     * @return type
     */
    public function sendTo1c() {
        $tableData = json_decode($this->data, true);
        $params = [
            'type' => 'UpdateAdvanceReport',
            'user_id_1c' => $this->user->id_1c,
            'subdivision_id_1c' => $this->subdivision->name_id,
            'customer_id_1c' => $this->customer->id_1c,
            'store_id_1c' => $this->subdivision_store->id_1c,
            'advance_id_1c' => (is_null($this->id_1c)) ? '' : $this->id_1c,
            'created_at' => $this->created_at->format('YmdHis')
        ];
        \PC::debug([$tableData, $this->data]);
        foreach ($tableData as &$item) {
            if (is_array($item)) {
                foreach ($item as &$row) {
                    if (is_array($row)) {
                        foreach ($row as $k => &$v) {
                            if (in_array($k, ['doc_date'])) {
                                $v = with(new Carbon($v))->format('YmdHis');
                            }
                            if ($k == 'order_id') {
                                $v = Order::where('id', $v)->value('number');
                            }
                        }
                    }
                }
            }
        }
        if(
                isset($tableData['other']) 
                && count($tableData['other'])==1 
                && isset($tableData['other']['0']) 
                && isset($tableData['other']['0']['nomenclature_id']) 
                && empty($tableData['other']['0']['nomenclature_id'])
                && isset($tableData['other']['0']['doc_number']) 
                && empty($tableData['other']['0']['doc_number'])
        ){
            $tableData['other'] = null;
        }
        if(
                isset($tableData['goods']) 
                && count($tableData['goods'])==1 
                && isset($tableData['goods']['0']) 
                && isset($tableData['goods']['0']['nomenclature_id']) 
                && empty($tableData['goods']['0']['nomenclature_id'])
                && isset($tableData['goods']['0']['doc_number']) 
                && empty($tableData['goods']['0']['doc_number'])
        ){
            $tableData['goods'] = null;
        }
        $params = array_merge($params, $tableData);
        Log::info(MySoap::createXML($params));
        return MySoap::sendExchangeArm(MySoap::createXML($params));
    }

    static function uploadFrom1c($advance_id_1c) {
        $res1c = AdvanceReport::getFrom1c($advance_id_1c);
        if ((int) $res1c->result == 0 && isset($res1c->error)) {
            return (string) $res1c->error;
        }
        \PC::debug($res1c);
        $advRep = AdvanceReport::where('id_1c', $advance_id_1c)->first();
        if (is_null($advRep)) {
            $advRep = new AdvanceReport();
            $advRep->id_1c = (string) $res1c->advance_id_1c;
        }
        $advRep->status = (int) $res1c->status;
        $advRep->user_id = User::where('id_1c', (string) $res1c->user_id_1c)->value('id');
        $advRep->subdivision_store_id = SubdivisionStore::where('id_1c', (string) $res1c->store_id_1c)->value('id');
        $advRep->customer_id = Customer::where('id_1c', (string) $res1c->customer_id_1c)->value('id');
        $data = [];
        $tables = ['goods', 'advance', 'other'];
        foreach ($tables as $table) {
            $data[$table] = json_decode(json_encode($res1c->{$table}));
            if ($table == 'advance') {
                foreach ($data[$table] as &$item) {
                    $item->order_id = Order::where('number', $item->order_id)->where('created_at', with(new Carbon($item->created_at))->format('Y-m-d H:i:s'))->value('id');
                }
            }
        }
        $advRep->data = json_encode($data);
        \PC::debug($advRep, 'advrep');
        $advRep->save();
        return $advRep;
    }

    static function getFrom1c($advance_id_1c) {
        return MySoap::sendExchangeArm(MySoap::createXML(['type' => 'CheckAdvanceReport', 'advance_id_1c' => $advance_id_1c]));
    }

    public function createPdf() {
        $contractForm = ContractForm::where('text_id', 'advance_report')->first();
        if (is_null($contractForm)) {
            return null;
        }

        $data = json_decode($this->data);
        $html = $contractForm->template;
        $objects = [
            'advance_reports' => $this->toArray(),
            'users' => $this->user->toArray(),
            'subdivisions' => $this->subdivision->toArray(),
        ];
        $passport = $this->customer->getLastPassport();
        if (!is_null($passport)) {
            $objects['passports'] = $passport->toArray();
        }
        $total_money = 0;

        foreach ($data->advance as $item) {
            $order = Order::find($item->order_id);
            if (!is_null($order)) {
                $total_money += $order->money / 100;
            }
        }

        $objects['advance_reports']['money'] = number_format($total_money, 2, '.', '');
        $dotpos = strpos($objects['advance_reports']['money'], '.');
        $objects['advance_reports']['total_money_rub'] = substr($objects['advance_reports']['money'], 0, $dotpos);
        $objects['advance_reports']['total_money_kop'] = substr($objects['advance_reports']['money'], $dotpos + 1);

        $objects['advance_reports']['docs_num'] = 2;
        $objects['advance_reports']['lists_num'] = 2;
        $objects['advance_reports']['tabel_number'] = '';
        $objects['advance_reports']['goal'] = '';

        $objects['advance_reports']['currency'] = '';
        $objects['advance_reports']['got_from_cashbox'] = '';
        $objects['advance_reports']['got_from_cashbox_in_valuta'] = '';
        $objects['advance_reports']['got_from_cashbox_by_cards'] = '';
        $objects['advance_reports']['got_from_cashbox_by_cards_in_valuta'] = '';
        $objects['advance_reports']['money_spent'] = '';

        $res1c = MySoap::sendExchangeArm(MySoap::createXML(['type' => 'PrintAdvanceReport', 'advance_id_1c' => $this->id_1c]));
        $objects['advance_reports']['init_balance'] = (string) $res1c->init_balance;
        $objects['advance_reports']['init_overspend'] = (string) $res1c->init_overspend;
        $objects['advance_reports']['final_balance'] = (string) $res1c->final_balance;
        $objects['advance_reports']['final_overspend'] = (string) $res1c->final_overspend;

        $i = 1;
        foreach ($data->goods as $item) {
            $objects['advance_reports']['sub_acc_dt_' . $i] = '10.09';
            $objects['advance_reports']['sum_dt_' . $i] = number_format($item->total_money, 2, ',', '');
            $objects['advance_reports']['sub_acc_ct_' . $i] = '71.01';
            $objects['advance_reports']['sum_ct_' . $i] = number_format($item->total_money, 2, ',', '');
            $i++;
        }
        foreach ($data->other as $item) {
            $objects['advance_reports']['sub_acc_dt_' . $i] = '26';
            $objects['advance_reports']['sum_dt_' . $i] = number_format($item->total_money, 2, ',', '');
            $objects['advance_reports']['sub_acc_ct_' . $i] = '71.01';
            $objects['advance_reports']['sum_ct_' . $i] = number_format($item->total_money, 2, ',', '');
            $i++;
        }

        $html = str_replace('<td colspan="9">{{advance_report.table}}</td>', $this->generateAdvanceTable($data), $html);

        $html = Http\Controllers\ContractEditorController::processParams($objects, $html);
        $html = Http\Controllers\ContractEditorController::replaceConfigVars($html);
        $html = Http\Controllers\ContractEditorController::clearTags($html);
        return Utils\PdfUtil::getPdf($html);
    }

    function generateAdvanceTable($data) {
        $html = '';
        $i = 1;
        $total = 0;
        foreach ($data->goods as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . with(new Carbon($item->doc_date))->format('d.m.Y') . '</td>';
            $html .= '<td>' . $item->doc_type . ' № ' . $item->doc_number . '</td>';
            $html .= '<td></td>';
            $html .= '<td>' . number_format($item->total_money, 2, ',', '') . '</td>';
            $html .= '<td></td>';
            $html .= '<td>' . number_format($item->total_money, 2, ',', '') . '</td>';
            $html .= '<td></td>';
            $html .= '<td>10.09</td>';
            $html.='</tr>';
            $i++;
            $total += $item->total_money;
        }
        foreach ($data->other as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td>' . with(new Carbon($item->doc_date))->format('d.m.Y') . '</td>';
            $html .= '<td>' . $item->doc_type . ' № ' . $item->doc_number . '</td>';
            $html .= '<td></td>';
            $html .= '<td>' . number_format($item->total_money, 2, ',', '') . '</td>';
            $html .= '<td></td>';
            $html .= '<td>' . number_format($item->total_money, 2, ',', '') . '</td>';
            $html .= '<td></td>';
            $html .= '<td>26</td>';
            $html.='</tr>';
            $i++;
            $total += $item->total_money;
        }
        $html .= '<tr>';
        $html .= '<td></td><td></td><td></td>';
        $html .= '<td>Итого</td>';
        $html .= '<td>' . $total . '</td>';
        $html .= '<td></td>';
        $html .= '<td>' . $total . '</td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        return $html;
    }

    public function deleteThrough1c() {
        $res1c = MySoap::sendExchangeArm(MySoap::createXML(['type' => 'Delete', 'Number' => $this->id_1c, 'doc_type' => MySoap::ITEM_ADVANCE_REPORT]));
        if ((int) $res1c->result == 1) {
            if ($this->delete()) {
                return new MyResult(true);
            } else {
                return new MyResult(false);
            }
        } else {
            return new MyResult(false, (string) $res1c->error);
        }
    }

}
