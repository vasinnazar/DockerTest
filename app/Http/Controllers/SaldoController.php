<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\MaterialsClaim,
    yajra\Datatables\Datatables,
    App\Utils\StrLib,
    Auth,
    App\Utils\HtmlHelper,
    App\MySoap,
    App\Customer,
    App\Passport,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    App\ContractForm,
    mikehaertl\wkhtmlto\Pdf,
    Input,
    App\Loan,
    App\NpfContract,
    App\Order,
    Carbon\Carbon;

class SaldoController extends BasicController {

    public function __construct() {
        
    }

    public function getSaldoView(Request $req) {
        $res1c = MySoap::sendXML(MySoap::createXML(['type' => '7', 'user_id_1c' => Auth::user()->id_1c]));
        if (!((int) $res1c->result)) {
            $error = (string) $res1c->error;
            return view('reports.saldo')->with('balanceDt', 0)->with('balanсeKt', 0)->with('balance', 0)->with('msg_err', $error);
        }
        return view('reports.saldo')->with('balanceDt', $res1c->balanceDt)->with('balanсeKt', $res1c->balanceKt)->with('balance', $res1c->balance);
    }

}
