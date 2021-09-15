<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Carbon\Carbon;

class CheckForEmploymentDocs {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        //если пользователь не авторизован или запрос идет на страницу связаннаю с авторизацией или заполнением даты рождения, то пропускать дальше
        //если пользователь не согласился с условиями то возвращать на страницу печати документов
        //если согласился и не ввел трекномера то на следующий день после согласия с условиями выводить окно с трекномером
        if (!is_null(Auth::user()) && strpos($request->path(), 'auth') === FALSE && strpos($request->path(), 'employment/user/update') === FALSE) {
            if (!is_null(Auth::user()->subdivision) && Auth::user()->subdivision->is_terminal) {
                return $next($request);
            }
            if (Auth::user()->name == 'Teleport' || Auth::user()->name == '1C') {
                return $next($request);
            }
            if ((Auth::user()->employment_agree == '0000-00-00 00:00:00' || empty(Auth::user()->employment_agree))) {
                if (strpos($request->path(), 'employment/docs') === FALSE) {
                    return redirect('employment/docs');
                }
            } else if (empty(Auth::user()->employment_docs_track_number) && !empty(Auth::user()->employment_agree) && with(new Carbon(Auth::user()->employment_agree))->addDay()->setTime(0, 0, 0)->lt(Carbon::now())) {
                if (strpos($request->path(), 'employment/tracknumber') === FALSE) {
                    return redirect('employment/tracknumber');
                }
            }
        }
        return $next($request);
    }

}
