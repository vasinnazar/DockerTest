<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InfinityOnlyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!(Request::server('REMOTE_ADDR') == '192.168.1.50'
            || Request::server('REMOTE_ADDR') == '192.168.1.60')) {
            return '';
        }
        return $next($request);
    }
}
