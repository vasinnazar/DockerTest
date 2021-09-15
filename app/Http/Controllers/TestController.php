<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;

class TestController extends Controller {

    public function __construct() {
        
    }

    public function test(Request $req) {
//        return $this->collateSums();
//        if (is_null(Auth::user()) || Auth::user()->id != 5) {
//            return;
//        }
//        if ($req->has('cabinet')) {
//            return $this->testCabinet();
//        }
//        if ($req->has('teleport')) {
//            return $this->testTeleport();
//        }
    }

    function collateSums() {
        $sums1 = \App\Sums::orderBy('customer', 'asc')->orderBy('loan', 'asc')->get();
        $sums2 = \App\Sums2::orderBy('customer', 'asc')->orderBy('loan', 'asc')->get();
        $sum1count = count($sums1);
        $sum2count = count($sums2);
//        $fp = fopen('file.csv', 'w');
//
//        foreach ($sums1 as $fields) {
//            fputcsv($fp, $fields);
//        }
//
//        fclose($fp);
        
        echo '<table>';
        for($i=0;$i<$sum1count;$i++){
            echo '<tr>';
            if($i<$sum2count){
                if($sums1[$i]->amount!=$sums2[$i]->amount){
                    echo '<td>'.$sums1[$i]->customer.'</td><td>'.$sums1[$i]->loan.'</td><td>'.$sums1[$i]->amount.'</td>';
                    echo '<td>'.$sums2[$i]->customer.'</td><td>'.$sums2[$i]->loan.'</td><td>'.$sums2[$i]->amount.'</td>';
                }
            } else {
                echo '<td>'.$sums1[$i]->customer.'</td><td>'.$sums1[$i]->loan.'</td><td>'.$sums1[$i]->amount.'</td>';
                echo '<td></td><td></td><td></td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        echo 'wow';
    }

    function testTeleport() {
        return \App\Utils\HelperUtil::SendPostRequest('http://192.168.1.60/armff.ru/teleport/claim/create', [
                    'uid' => 'test',
                    'data' => json_encode([
                        'id' => '112',
                        'passport_series' => '1704',
                        'passport_number' => '081216',
                        'passport_date_of_issue' => '1990-01-01',
                        'birthplace' => 'Кемерово',
                        'passport_org' => 'asd',
                        'passport_code' => '123-123',
                        'last_name' => 'last name',
                        'first_name' => 'first_name',
                        'middle_name' => 'middle_name',
                        'birthday' => '1990-01-01',
                        'registrarion_region' => 'asd',
                        'registrarion_city' => 'asd',
                        'registrarion_street' => 'asd',
                        'registrarion_house' => '1',
                        'registrarion_building' => '1',
                        'registrarion_apartment' => '1',
                        'phone' => '78000000000',
                        'incoming' => '100',
                        'home_phone' => '12345687',
                        'work_name' => 'qweoipqoiw poqiwoeipoqwie',
                        'amount' => '1000',
                        'period' => '30',
                        'sex' => '1'
                    ])
        ]);
    }

    function testCabinet() {
        $xml = new \SimpleXMLElement('<root/>');
        $xml->addChild('Success', 'False');
        return $xml->asXML();
    }

}
