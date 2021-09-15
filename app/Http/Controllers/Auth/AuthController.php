<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use App\Spylog\Spylog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\MySoap;
use Auth;

class AuthController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Registration & Login Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles the registration of new users, as well as the
      | authentication of existing users. By default, this controller uses
      | a simple trait to add these behaviors. Why don't you explore it?
      |
     */

use AuthenticatesAndRegistersUsers;

    /**
     * Create a new authentication controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\Guard  $auth
     * @param  \Illuminate\Contracts\Auth\Registrar  $registrar
     * @return void
     */
    public function __construct() {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    public function postLogin(Request $request) {
        if (Auth::attempt($request->only('login', 'password'))) {
            $user = Auth::user();

            $now = Carbon::now();
            $msg = '';
            if (with(new Carbon($user->last_login))->addDays(config('options.days_until_user_block'))->lt($now) && $user->last_login != '0000-00-00 00:00:00' && !$user->isAdmin()) {
                if (!$user->hasRole('not_blockable_user')) {
                    $user->banned = 1;
                    $msg = 'Доступ был запрещён по причине отсутствия в программе в течении ' . config('options.days_until_user_block') . ' дней.';
                }
            }
//            $ssl_on = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on');
            if ($user->swapable == 1) {
                return redirect('subdivisions/swapable/change');
            }
//            if ($ssl_on) {
//                $ssl_user_name = (array_key_exists('SSL_CLIENT_S_DN_OU', $_SERVER)) ? $_SERVER['SSL_CLIENT_S_DN_OU'] : '';
//                if ($user->id != $ssl_user_name) {
//                    Auth::logout();
//                    return redirect('/auth/login')->with('msg', 'Выбран неверный сертификат. Перезапустите браузер и выберите свой сертификат' . $msg)->with('class', 'alert-danger');
//                }
//            }
            if ($user->banned || (!is_null($user->ban_at) && $user->ban_at != '0000-00-00' && $now->gte(new Carbon($user->ban_at)))) {
                Auth::logout();
                return redirect('/auth/login')->with('msg', 'Доступ запрещён. Обратитесь в техническую поддержку. ' . $msg)->with('class', 'alert-danger');
            }
            $begin = new Carbon($user->begin_time);
            $end = new Carbon($user->end_time);
            if ((Carbon::now()->gte($end) || Carbon::now()->lte($begin)) && !Auth::user()->id==1) {
                Auth::logout();
                return redirect('/auth/login')->with('msg', 'Доступ временно запрещён')->with('class', 'alert-danger');
            }
            $user->last_login = Carbon::now();
            $user->save();
            
            //если спец уже сегодня сохранял учет рабочего времени и выходил, и в нем заполнена дата выхода - стирать дату выхода, 
            //чтобы в отчете по учету рабочего времени не показывало что он вышел и не зашел
            $worktime = \App\WorkTime::where('user_id',$user->id)->where('created_at','>=', Carbon::today()->format('Y-m-d H:i:s'))->first();
            if(!is_null($worktime) && !empty($worktime->date_end)){
                $worktime->date_end = null;
                $worktime->save();
            }
            
            Spylog::log(Spylog::ACTION_LOGIN, 'users', $user->id, $_SERVER['REMOTE_ADDR']);
            if (config('app.version_type') == 'debtors') {
                return redirect('debtors/index')
                                ->with('msg', 'Добро пожаловать, ' . Auth::user()->name)
                                ->with('class', 'alert-success');
            } else {
				if(Auth::user()->hasPermission(\App\Permission::makeName(\App\Utils\PermLib::ACTION_SELECT,\App\Utils\PermLib::SUBJ_CANDIDATE_LIST))){
					return redirect('candidate/index');
				}
                return redirect('home')
                                ->with('msg', 'Добро пожаловать, ' . Auth::user()->name)
                                ->with('class', 'alert-success');
            }
        }
        return redirect()->back()->with('msg', 'Ошибка при вводе почты\\пароля')->with('class', 'alert-danger');
    }

    public function getLogout() {
        if (!is_null(Auth::user())) {
            Spylog::log(Spylog::ACTION_LOGOUT, 'users', Auth::user()->id, $_SERVER['REMOTE_ADDR']);
        }
        Auth::logout();
        return redirect('/auth/login');
    }

}
