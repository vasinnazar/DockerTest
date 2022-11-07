<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Auth,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Card,
    App\User,
    App\Cashbook,
    App\Subdivision,
    App\Services\Graph,
    App\Region,
    App\OrderType,
    App\ContractForm,
    App\Repayment,
    App\StrUtils,
    App\MySoap,
    App\Passport,
    App\RemoveRequest,
    Illuminate\Support\Facades\DB,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel,
    mikehaertl\wkhtmlto\Pdf,
    Illuminate\Support\Facades\Response,
    Log,
    App\Utils\StrLib,
    App\Utils\FileToPdfUtil,
    Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use App\Permission;


class GraphController extends BasicController {

    public function __construct() {
       // $this->middleware('auth');
    }
	
    public function index(Request $req) {
        //$user_id = Auth::id();					
        return view('graph.index');
    }
    
    public function getGraphData(Request $req) {
        //logger("hello world");
	$graphf = new Graph();
	$graphg = $graphf->getOrders($req);
        $orders = $graphg['graph'];
        $onetotal1C = $graphg['onetotal1C'];
        $onesumRegion1C = $graphg['onesumRegion1C'];
        $twototal1C = $graphg['twototal1C'];
        $twosumRegion1C = $graphg['twosumRegion1C'];
        //\PC::debug($sumRegion1C);
	$tablHTML = $graphf->getOrdersTable($orders,$req->get('OldDate'),$req->get('CurDate'),$onetotal1C,$onesumRegion1C,$twototal1C,$twosumRegion1C);

        return ['HTML'=>$tablHTML,'graph'=>$orders];
    } 
    
}
