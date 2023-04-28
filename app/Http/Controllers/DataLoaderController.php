<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\User;
use Illuminate\Support\Facades\DB;

class DataLoaderController extends Controller {

    public function __construct() {
        
    }

    public function test(Request $request) {
        return 2;
    }

    public function index() {
        return $this->loadSums();
        return view('adminpanel/dataloader');
    }

    public function loadCustomers() {
//        $this->loadUsersFile();
//        return;
//        $reader = new XMLReader();
//        $reader->open("./xml/customers.xml");
//        while ($reader->read()) {
//            switch ($reader->nodeType) {
//                case (XMLREADER::ELEMENT):
//                    if ($reader->localName == "TR") {
//                        
//                    }
//            }
//        }
//        echo 'ok';
    }

    public function loadUsersFile() {
        $row = 1;
        $total = 0;
        $found = 0;
        $unfound = 0;
        $cols = [];
        if (($handle = fopen(storage_path() . '/app/sums1.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                $num = count($data);
                $item = null;
                $total++;
                for ($c = 0; $c < $num; $c++) {
                    if ($row == 1) {
                        $cols[$c] = $data[$c];
                    } else {
                        if ($cols[$c] == 'id_1c') {
                            $item = \App\User::where('id_1c', $data[$c])->first();
                            if (is_null($item)) {
                                $item = new User();
                                $item->id_1c = $data[$c];
                            }
                        } else {
                            if (empty($item->id)) {
                                $item->name = $data[$c];
                                $sameLoginCount = User::where('name', $data[$c])->count();
                                $item->login = ($sameLoginCount > 0) ? ($data[$c] . ' ' . $sameLoginCount) : $data[$c];
                            }
                        }
                    }
                }
                if (empty($item->id) && !is_null($item)) {
                    $item->banned = 1;
                    $item->subdivision_id = 113;
                    $item->group_id = 1;
                    $item->created_at = Carbon::now()->format('Y-m-d H:i:s');
                    $item->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                    $item->begin_time = '00:00:00';
                    $item->end_time = '00:00:01';
                    $item->last_login = with(new Carbon('2000-01-01 00:00:00'))->format('Y-m-d H:i:s');
                    $item->employment_agree = with(new Carbon('2000-01-01 00:00:00'))->format('Y-m-d H:i:s');
                    $item->employment_docs_track_number = 'testfill';
                    $unfound++;
                } else {
                    $found++;
                }
                if (isset($item) && empty($item->id)) {
//                    $item->save();
//                    $double->save();
                }
                $row++;
            }
            fclose($handle);
        }
        echo '<hr>' . $row;
    }

    public function loadSums() {
        if (($handle = fopen(storage_path() . '/app/sums2.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                DB::connection('mysql')->table('sums2')->insert(['customer' => $data[0], 'loan' => $data[1], 'amount' => (float) $data[2] * 100]);
            }
            fclose($handle);
        }
    }

    public function loadCsvFile() {
        $row = 1;
        $cols = [];
        if (($handle = fopen(storage_path() . '/app/debtors_doubles.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                $num = count($data);
                if ($row > 1) {
                    $double = new \App\AddressDouble();
                }
                for ($c = 0; $c < $num; $c++) {
                    if ($row == 1) {
                        $cols[$c] = $data[$c];
                    } else {
                        if ($cols[$c] == 'date') {
                            $double->{$cols[$c]} = with(new Carbon($data[$c]))->format('Y-m-d H:i:s');
                        } else if ($cols[$c] == 'is_debtor') {
                            $double->{$cols[$c]} = ($data[$c] == 'Да') ? 1 : 0;
                        } else {
                            $double->{$cols[$c]} = $data[$c];
                        }
                    }
                }
                if (isset($double)) {
                    $double->save();
                }
                $row++;
//                if ($row > 20) {
//                    break;
//                }
            }
            fclose($handle);
        }
        echo '<hr>' . $row;
    }

}
