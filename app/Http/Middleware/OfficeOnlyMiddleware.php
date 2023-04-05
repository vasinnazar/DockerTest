<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeOnlyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('app.dev')) {
            return redirect('home')->with('msg', 'Недостаточно прав доступа!')->with('class', 'alert-danger');
        }
        if (is_null(Auth::user()) || is_null(Auth::user()->subdivision)
            || !in_array(Auth::user()->subdivision->name_id, ['000000012', 'НСК00014'])) {
            return redirect('home')->with('msg', 'Недостаточно прав доступа!')->with('class', 'alert-danger');
        }
        return $next($request);
    }
}
