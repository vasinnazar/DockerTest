<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->isAdmin()) {
            return $next($request);
        }
        return redirect('home')->with('msg', 'Недостаточно прав доступа!')->with('class', 'alert-danger');
    }
}
