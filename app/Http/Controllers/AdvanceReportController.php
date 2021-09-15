<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use yajra\Datatables\Datatables;
use App\AdvanceReport;
use Auth;
use App\Utils\StrLib;
use Carbon\Carbon;

class AdvanceReportController extends BasicController {
    public function __construct() {
        $this->middleware('auth');
    }
    /**
     * таблица с авансовыми отчетами
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('reports.advancereports.index');
    }
    /**
     * запрос списка для таблицы отчетов
     * @param Request $req
     * @return type
     */
    public function ajaxList(Request $req) {
        $cols = [
            'advance_reports.created_at as ar_created_at',
            'advance_reports.id as ar_id',
            'users.name as user_name',
            'subdivisions.name as subdiv_name',
        ];
        $items = AdvanceReport::select($cols)
                ->leftJoin('subdivisions', 'subdivisions.id', '=', 'advance_reports.subdivision_id')
                ->leftJoin('users', 'users.id', '=', 'advance_reports.user_id');
        if (!Auth::user()->isAdmin()) {
            $items->where('advance_reports.subdivision_id', Auth::user()->subdivision_id);
        }
        if($req->has('created_at')){
            $items->where('advance_reports.created_at',$req->get('created_at'));
        }
        if($req->has('user_id')){
            $items->where('advance_reports.user_id',$req->get('user_id'));
        }
        if($req->has('subdivision_id')){
            $items->where('advance_reports.subdivision_id',$req->get('subdivision_id'));
        }
        return Datatables::of($items)
                        ->editColumn('ar_created_at', function($item) {
                            return with(new Carbon($item->ar_created_at))->format('d.m.Y H:i:s');
                        })
                        ->addColumn('actions', function($item) {
                            $html = '<div class="btn-group">';
                            $html .= \App\Utils\HtmlHelper::Buttton(url('/reports/advancereports/pdf/' . $item->ar_id), ['glyph' => 'print','size'=>'sm','target'=>'_blank']);
                            $html .= \App\Utils\HtmlHelper::Buttton(url('/reports/advancereports/edit/' . $item->ar_id), ['glyph' => 'pencil','size'=>'sm']);
                            $html .= \App\Utils\HtmlHelper::Buttton(url('/reports/advancereports/destroy/' . $item->ar_id), ['glyph' => 'remove','size'=>'sm']);
                            $html .= '</div>';
                            return $html;
                        })
                        ->removeColumn('ar_id')
                        ->make();
    }

    /**
     * Открывает страницу редактирования отчета
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id = null) {
        $advRep = AdvanceReport::findOrNew($id);
        if (is_null($advRep)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $subdiv = (is_null($advRep->subdivision))?Auth::user()->subdivision:$advRep->subdivision;
        $user = (is_null($advRep->user))?Auth::user():$advRep->user;
        $data = [
            'advRep' => $advRep,
            'nomenclatureList' => AdvanceReport::getNomenclatureList(),
            'orders'=> AdvanceReport::getIssueOrders($subdiv, $user, $advRep, false)
        ];
        return view('reports.advancereports.edit', $data);
    }

    /**
     * Сохранить отчет
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req) {
        $advRep = AdvanceReport::findOrNew($req->get('id'));
        \PC::debug($req->input());
        $input = $req->input();
        if(empty($input['created_at'])){
            $input['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
        }
        $advRep->fill($input);
        $data = [
            'goods' => json_decode($req->get('goods_data')),
            'other' => json_decode($req->get('other_data')),
            'payment' => json_decode($req->get('payment_data')),
            'advance' => json_decode($req->get('advance_data')),
        ];
        $advRep->data = json_encode($data);
//        $advRep->data = str_replace('"','\"',$advRep->data);
        if(empty($advRep->user_id)){
            $advRep->user_id = Auth::user()->id;
            $advRep->subdivision_id = Auth::user()->subdivision_id;
        }
        if(is_null($advRep->customer) && empty($req->get('customer_id'))){
            $advRep->fill(['customer_id'=>\App\Passport::where('fio','=',$advRep->user->name)
                    ->where('birth_date',$advRep->user->birth_date)
                    ->value('customer_id')]);
        }
        \PC::debug([$advRep->customer_id,$advRep->customer],'customer');
        if(is_null($advRep->customer)){
            return $this->backWithErr('Не заполнен контрагент');
        }
        if(is_null($advRep->subdivision_store)){
            return $this->backWithErr('Не заполнен склад');
        }
        $totalOrders = 0;
        $totalSpent = 0;
        if(isset($data->advance)){
            foreach($data->advance as $order){
                $totalOrders += $order->advance_money;
                $totalSpent += $order->advance_spent;
            }
        }
        if($totalSpent>$totalOrders){
            return $this->backWithErr('Сумма израсходавано превышает сумму ордеров');
        }
        $res = $advRep->saveThrough1c();
        if(!$res['result']){
            return redirect('reports/advancereports/index')->with('msg_err', StrLib::ERR.' '.$res['error']);
        }
//        $advRep->save();
        return redirect('reports/advancereports/index')->with('msg_suc', StrLib::SUC_SAVED);
    }

    /**
     * Удалить отчет
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $advRep = AdvanceReport::find($id);
        if (is_null($advRep)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        $res = $advRep->deleteThrough1c();
        if($res->result){
            return $this->backWithSuc();
        } else {
            return $this->backWithErr($res->error);
        }
    }
    /**
     * Сгенерировать пдф
     * @param integer $id идентификатор отчета
     * @return type
     */
    public function pdf($id){
        $advRep = AdvanceReport::find($id);
        if (is_null($advRep)) {
            return $this->backWithErr(StrLib::ERR_NULL);
        }
        return $advRep->createPdf();
    }
    /**
     * Подгрузит отчет с переданным номером из 1с в арм
     * @param Request $req
     * @return type
     */
    public function upload(Request $req){
        $rep = AdvanceReport::uploadFrom1c($req->get('advance_id_1c'));
        if(is_null($rep) || is_string($rep)){
            return $this->backWithErr($rep);
        } else {
            return $this->backWithSuc();
        }
    }
    
    public function getIssueOrders(Request $req){
        
    }

}
