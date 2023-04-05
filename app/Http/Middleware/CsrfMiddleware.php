<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

class CsrfMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = Request::ajax() ? Request::header('X-CSRF-Token') : Input::get('_token');
        if (Session::token() != $token) {
            throw new TokenMismatchException;
        }
        return $next($request);
    }
}
