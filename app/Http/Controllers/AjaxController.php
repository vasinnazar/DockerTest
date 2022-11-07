<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Input,
    Validator,
    Session,
    Redirect,
    App\Loan,
    App\Card,
    Illuminate\Support\Facades\DB,
    Carbon\Carbon,
    App\Spylog\Spylog,
    App\Spylog\SpylogModel;
use App\User;

class AjaxController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }
    public function passportsAutocomplete(Request $req){
        return DB::table('passports')->select(DB::raw("CONCAT(fio,' (',series,' ',number,')') as label, id as id"))->where('fio', 'like', '%' . $req->term . '%')->get();
    }
    public function customersAutocomplete(Request $req){
        return DB::table('passports')
                ->leftJoin('customers','customers.id','=','passports.customer_id')
                ->select(DB::raw("CONCAT(passports.fio,' (',customers.id_1c,')') as label, customers.id as id"))
                ->where('passports.fio', 'like', '%' . $req->term . '%')
                ->distinct()
                ->get();
    }
    public function autocomplete(Request $req, $table){
        switch ($table){
            case 'passports':
                return $this->passportsAutocomplete($req);
            case 'users':
                return User::where('name', 'like', '%' . $req->term . '%')->select('name as label','id')->get();
            case 'customers':
                return $this->customersAutocomplete($req);
            case 'expenditures':
                return \App\Expenditure::where('name', 'like', '%' . $req->term . '%')->select('name as label','id_1c as id')->get();
            case 'subdivision_stores':
                return \App\SubdivisionStore::where('name', 'like', '%' . $req->term . '%')->select('name as label','id')->get();
            case 'subdivisions':
                $ctrl = new SubdivisionController();
                return $ctrl->getAutocompleteList($req);
            case 'nomenclatures':
                $col = \App\Nomenclature::where('name', 'like', '%' . $req->term . '%')
                    ->select('name as label','id_1c as id');
                if($req->has('type')){
                    $col->where('type',$req->get('type'));
                }
                return $col->get();
            default:
                return [];
        }
    }
    public function getLabelById(Request $req){
        $table = $req->get('table');
        $id = $req->get('id');
        $field = 'id_1c';
        switch ($table){
            case 'passports':
                return '';
            case 'users':
                return User::where($field, $id)->value('name');
            case 'customers':
                return $this->customersAutocomplete($req);
            case 'expenditures':
                return \App\Expenditure::where($field, $id)->value('name');
            case 'subdivision_stores':
                return \App\SubdivisionStore::where($field, $id)->value('name');
            case 'subdivisions':
                return \App\Subdivision::where($field,$id)->value('name');
            case 'nomenclatures':
                return \App\Nomenclature::where($field, $id)->value('name');
            default:
                return '';
        }
    }

}
