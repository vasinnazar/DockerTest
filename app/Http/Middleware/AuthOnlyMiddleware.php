<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthOnlyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()) {
            return $next($request);
        }
        return redirect('auth/login');
    }
}
