<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use App\MySoap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Log;
use Auth;
use App\Spylog\Spylog;
use App\DebtorEvent;
use Illuminate\Http\Request;
use App\Region;
use App\Subdivision;

class Graph {
	// для purpose
    const P_OD = 0;
    const P_PC = 1;
    const P_EXPPC = 2;
    const P_FINE = 3;
    const P_TAX = 4;
    const P_UKI = 5;
    const P_UKI_NDS = 6;
    const P_COMMISSION = 7;
    const P_COMMISSION_NDS = 8;
	
    protected $dbname = 'armf'; // на боевом armf
    protected $dbmysql = 'arm'; 
    
    /* подключение к 1С */
    public function getDataSalesFrom1c($endDate) {
        $connection = [
            //'url' => '192.168.34.227:80/BP_test/ws/ExchangeARM/?wsdl', // Ваня
            'url' => '192.168.1.34:81/PersonaArea1/ws/ExchangeARM/?wsdl', //- боевая
            'login' => 'KAadmin',
            'password' => 'dune25',
            'absolute_url' => true 
        ];
        $xml = ['type' => 'GetSalesReport2', 'date' => $endDate];
        $res1c = MySoap::call1C('Main', ['params' => MySoap::createXML($xml)], false, FALSE, $connection, false, false);
        $items = simplexml_load_string($res1c['value']);
        return $items->subdivisions->children();
    }    
    
    /* Конец  подключение к 1С */ 
     
    public function getOrders(Request $req) {
        /* Запрос в АРМ */
        $beginOldDate = with(new Carbon($req->get('OldDate')))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $endOldDate   = with(new Carbon($req->get('OldDate')))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
        
        $graphOldDay = DB::connection($this->dbname)->select('select DATE_FORMAT(o.created_at, "%d.%m.%Y") AS date, DATE_FORMAT(o.created_at, "%H:%i:%s") AS time, o.money, r.name from orders o left JOIN subdivisions s on (o.subdivision_id=s.id) left JOIN cities c ON (s.city_id = c.id) LEFT JOIN regions r ON (c.region_id = r.id) where o.created_at >="' . $beginOldDate . '" and o.created_at <="' . $endOldDate . '" and o.type in (0,3) and (o.number is not null and o.number <> \'\') and o.money>100000 and o.money % 100000 = 0 ORDER BY o.created_at, time');              

        $beginCurDate = with(new Carbon($req->get('CurDate')))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $endCurDate   = with(new Carbon($req->get('CurDate')))->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $graphToDay = DB::connection($this->dbname)->select('select DATE_FORMAT(o.created_at, "%d.%m.%Y") AS date, DATE_FORMAT(o.created_at, "%H:%i:%s") AS time, o.money, r.name from orders o left JOIN subdivisions s on (o.subdivision_id=s.id) left JOIN cities c ON (s.city_id = c.id) LEFT JOIN regions r ON (c.region_id = r.id) where o.created_at >="' . $beginCurDate . '" and o.created_at <="' . $endCurDate . '" and o.type in (0,3) and (o.number is not null and o.number <> \'\') and o.money>100000 and o.money % 100000 = 0 ORDER BY o.created_at, time');
        /* КОНЕЦ запроса в АРМ */
        $graph = array_merge_recursive($graphOldDay, $graphToDay);
        /* Получаем таблици */
        /* Запрос в 1C */
        $tmpCurDate = date('Y-m-d');
        
        if ( ((isset($tmpEndDate)) and ($tmpEndDate < $tmpCurDate) and ($tmpEndDate != with(new Carbon($req->get('OldDate')))->format('Y-m-d'))) or (!isset($tmpEndDate))) {
            $dateStart = with(new Carbon($req->get('OldDate')))->format('YmdHis');
            //$dateStart = '20181219000000';
            $onedata = $this->getDataSalesFrom1c($dateStart);
            $onedatemass = with(new Carbon($dateStart))->format('d.m.Y');
            $onesumRegion1C = [$onedatemass=>[]];
            $onetotal1C = 0;
            $regions[] = '';
       
            foreach ($onedata as $items) {
                $leftColums = DB::connection($this->dbmysql)->select('SELECT r.name FROM subdivisions s LEFT JOIN cities c ON (s.city_id = c.id) LEFT JOIN regions r ON (c.region_id = r.id) WHERE s.name_id = "'.$items->subdivision_id_1c.'" ');
                if (empty($leftColums)) { // проверяем вдруг словим чего не того в запросе
                    if (!array_key_exists((string)$items->subdivision_id_1c, $onesumRegion1C[$onedatemass])) {
                        $onesumRegion1C[$onedatemass][(string)$items->subdivision_id_1c]['money'] = 0;
                    }
                    $onesumRegion1C[$onedatemass][(string)$items->subdivision_id_1c]['money'] += $items->sum/100;
                    $onetotal1C += $items->sum/100;
                } else {
                    if (!array_key_exists((string)$leftColums[0]->name, $onesumRegion1C[$onedatemass])) {
                        $onesumRegion1C[$onedatemass][(string)$leftColums[0]->name]['money'] = 0;
                    } 
                    $onesumRegion1C[$onedatemass][(string)$leftColums[0]->name]['money'] += $items->sum/100;
                    $onetotal1C += $items->sum/100;
                }
            }
            foreach ($onesumRegion1C as $value) {
                foreach ($value as $name => $summa) {
                    $regions[] = $name;
                }
            }
            $tmpEndDate = with(new Carbon($req->get('OldDate')))->format('Y-m-d');
        }
        if ( ((isset($tmpStartDate)) and ($tmpStartDate < $tmpCurDate) and ($tmpStartDate != with(new Carbon($req->get('CurDate')))->format('Y-m-d'))) or (!isset($tmpStartDate))){
            $dateEnd = with(new Carbon($req->get('CurDate')))->format('YmdHis');
            //$dateEnd = '20181219000000';
            $twodata = $this->getDataSalesFrom1c($dateEnd);
            $twodatemass = with(new Carbon($dateEnd))->format('d.m.Y');
            $twosumRegion1C = [$twodatemass=>[]];
            $twototal1C = 0;

            foreach ($twodata as $items) {
                $rightColums = DB::connection($this->dbmysql)->select('SELECT r.name FROM subdivisions s LEFT JOIN cities c ON (s.city_id = c.id) LEFT JOIN regions r ON (c.region_id = r.id) WHERE s.name_id = "'.$items->subdivision_id_1c.'" ');
                // \PC::debug($items,'$items');
                if (empty($rightColums)) { // проверяем вдруг словим чего не того в запросе
                    if (!array_key_exists((string)$items->subdivision_id_1c, $twosumRegion1C[$twodatemass])) {
                        $twosumRegion1C[$twodatemass][(string)$items->subdivision_id_1c]['money'] = 0;
                    }
                    $twosumRegion1C[$twodatemass][(string)$items->subdivision_id_1c]['money'] += $items->sum/100;
                    $twototal1C += $items->sum/100;
                } else {
                    if (!array_key_exists((string)$rightColums[0]->name, $twosumRegion1C[$twodatemass])) {
                        $twosumRegion1C[$twodatemass][(string)$rightColums[0]->name]['money'] = 0;
                    } 
                    $twosumRegion1C[$twodatemass][(string)$rightColums[0]->name]['money'] += $items->sum/100;
                    $twototal1C += $items->sum/100;
                }
            }
            foreach ($regions as $region) {
                if (!array_key_exists((string)$region, $twosumRegion1C[$twodatemass])) {
                    $twosumRegion1C[$twodatemass][$region]['money'] = 0;
                    //\PC::debug($twosumRegion1C,'$twosumRegion1C');
                }
            }
            $tmpStartDate = with(new Carbon($req->get('CurDate')))->format('Y-m-d');  
        }
        /* КОНЕЦ запроса в 1С */
        \PC::debug([$twosumRegion1C],'twosum');
        return [
            'graph' => $graph,
            'onetotal1C' => $onetotal1C,
            'onesumRegion1C' => $onesumRegion1C,
            'twototal1C' => $twototal1C,
            'twosumRegion1C' => $twosumRegion1C
        ];
    }

    public function getOrdersTable($Orders,$oldDate,$newDate,$onetotal1C,$onesumRegion1C,$twototal1C,$twosumRegion1C) {
//        $regions = Region::getRegions();

//        $html = view('graph/orderstable',['orders' => $Orders, 'regions' => $regions, 'oldDate' => $oldDate, 'newDate' => $newDate, 'onetotal1C' => $onetotal1C, 'onesumRegion1C' => $onesumRegion1C, 'twototal1C' => $twototal1C, 'twosumRegion1C' => $twosumRegion1C])->render();
        $html = view('graph/orderstable',['orders' => $Orders, 'oldDate' => $oldDate, 'newDate' => $newDate, 'onetotal1C' => $onetotal1C, 'onesumRegion1C' => $onesumRegion1C, 'twototal1C' => $twototal1C, 'twosumRegion1C' => $twosumRegion1C])->render();

        return $html;
    }
    
    function getSubdivBlock($subdivName, $city_id) {
        return [
            'name' => $subdivName,
            'city_id' => $city_id,
            'sum' => 0,
            'average_sum' => 0,
            'q_uki' => 0,
            'q_claim' => 0,
            'max_claim_sum' => 0,
            'q_new_client' => 0
        ];
    }

    function getCityBlock($cityname, $city_id) {
        return [
            'name' => $cityname,
            'city_id' => $city_id,
            'sum' => 0,
            'subdivisions' => [],
            'total' => [
                'sum' => 0,
                'average_sum' => 0,
                'q_uki' => 0,
                'q_claim' => 0,
                'max_claim_sum' => 0,
                'q_new_client' => 0
            ]
        ];
    } 
}
