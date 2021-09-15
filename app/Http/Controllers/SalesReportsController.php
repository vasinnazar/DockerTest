<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;
use App\Utils\StrLib;
use App\MySoap;
use App\ContractForm;
use Auth;
use DB;
use App\Loan,
    App\Claim,
    App\Repayment,
    App\Order,
    App\User,
    App\Subdivision;
use App\Utils\PdfUtil;

class SalesReportsController extends BasicController {

    public function __construct() {
        
    }

    public function getDataFrom1c($dateStart, $dateEnd = null) {
        $res = [];
        $res1c = MySoap::sendXML(MySoap::createXML(['type' => 10, 'start_date' => $dateStart]), false);
        return (isset($res1c->items)) ? $res1c->items : [];
    }

    public function getSalesReport1(Request $req) {
        if (!$req->has('start_date')) {
            return redirect()->back()->with('msg_err', StrLib::ERR_NO_PARAMS);
        }
        $dateStart = with(new Carbon($req->start_date))->format('Ymd');
        $dateEnd = ($req->has('finish_date')) ? with(new Carbon($req->finish_date))->format('Ymd') : $dateStart;
        $form = ContractForm::where('text_id', 'salesreport1')->first();
        if (is_null($form)) {
            return abort(404);
        }

        $user = ($req->has('user_id')) ? User::find($req->user_id) : Auth::user();
//        $subdiv = (Auth::user()->isAdmin() && $req->has('subdivision_id')) ? Subdivision::find($req->subdivision_id) : Auth::user()->subdivision;
        $html = $form->template;
//        $user_id_1c = (!is_null($user)) ? $user->id_1c : null;
        $data = $this->getDataFrom1c($dateStart, $dateEnd);
        $docstable = '<table class="docstable"><tr><th>Подразделение</th><th>Дата</th><th>Сумма</th></tr>';
        $total = 0;
        $cities = [];
//        foreach ($data as $items) {
//            foreach ($items as $item) {
//                $subdiv = Subdivision::where('name_id', $item->subdivision_id_1c)->first();
//                if (is_null($subdiv)) {
//                    continue;
//                }
//                if (!array_key_exists($subdiv->city_id, $cities)) {
//                    $cities[$subdiv->city_id] = [
//                        'id' => $subdiv->city_id,
//                        'name' => (!is_null($subdiv->getCity)) ? $subdiv->getCity->name : '',
//                        'sum' => 0,
//                        'subdivisions' => []
//                    ];
//                }
//                $cities[$subdiv->city_id]['subdivisions'][] = ['name' => $subdiv->name, 'sum' => $item->sum];
//                $cities[$subdiv->city_id]['sum'] += (float) $item->sum;
//                $total += (float) $item->sum;
//            }
//        }
        foreach ($data as $items) {
            foreach ($items as $item) {
                $cityname = (string)$item->city;
                $subdiv = Subdivision::where('name_id', $item->subdivision_id_1c)->first();
                $itemSum = (float)$item->sum;
                if (is_null($subdiv)) {
                    continue;
                }
                if (!array_key_exists($cityname, $cities)) {
                    $cities[$cityname] = [
                        'name' => $cityname,
                        'sum' => 0,
                        'subdivisions' => []
                    ];
                }
                $cities[$cityname]['subdivisions'][] = ['name' => $subdiv->name, 'sum' => $itemSum];
                $cities[$cityname]['sum'] += $itemSum;
                $total += $itemSum;
            }
        }
        $date = with(new Carbon($req->start_date))->format('d.m.Y');
//        \PC::debug($data);
        
        return view('reports.sales_report',['cities'=>$cities, 'date'=>$date, 'total'=>$total]);
        
//        foreach ($cities as $city) {
//            $docstable .= '<tr style="font-size:12px; font-weight:bold; background-color:#dcdcdc;"><td>' . $city['name'] . '</td><td>' . $date . '</td><td>' . $city['sum'] . ' р.</td></tr>';
//            foreach ($city['subdivisions'] as $s) {
//                $docstable .= '<tr><td>' . $s['name'] . '</td><td>' . $date . '</td><td>' . $s['sum'] . ' р.</td></tr>';
//            }
//        }
//        $docstable .= '<tr><td>Итого</td><td></td><td>' . $total . ' р.</td></tr>';
//        $docstable .= '</table>';
//        $html = str_replace('{{docs}}', $docstable, $html);
//        $html = str_replace('{{start_date}}', $date, $html);
//        $html = ContractEditorController::clearTags($html);
//        return PdfUtil::getPdf($html);
    }

}
