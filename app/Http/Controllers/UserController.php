<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    Auth,
    Illuminate\Http\Request,
    Validator,
    Storage,
    Input,
    Log,
    Carbon\Carbon,
    Illuminate\Support\Facades\Redirect,
    App\User,
    Illuminate\Support\Facades\Session;

class UserController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }
    public function getAutocompleteList(Request $req) {
        return User::where('name', 'like', '%' . $req->term . '%')->select('name as label','id')->get();
    }
    public function setEmploymentFields($user_id){
        if(!Auth::user()->isAdmin()){
            return 0;
        }
        $user = User::find($user_id);
        if(is_null($user)){
            return 0;
        }
        if(!empty($user->employment_agree) && $user->employment_agree != '0000-00-00 00:00:00' && !empty($user->employment_docs_track_number)){
            return 1;
        }
        $user->employment_agree = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();
        return 1;
    }

}
