<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChangeSubdivOnceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (is_null($user)) {
            return redirect('home')
                ->with('msg', 'Не авторизированный пользователь')
                ->with('class', 'alert-danger');
        }
        $scdb = $user->subdivision_change;
        $sc = new Carbon($scdb);
        if (!$user->isAdmin()
            && $scdb != null
            && (Carbon::now()->diffInHours($sc) < 8
                && Carbon::now()->day == $sc->day)) {

            return redirect('home')
                ->with('msg', 'Нельзя менять подразделение более одного раза в день!')
                ->with('class', 'alert-danger');
        }
        return $next($request);
    }
}
