<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocalCallOnlyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Request::server('REMOTE_ADDR') == '192.168.1.167'
            || Request::server('REMOTE_ADDR') == '192.168.1.47'
            || Request::server('REMOTE_ADDR') == '192.168.34.206') {

            if (!config('app.dev')) {
                return ['res' => 1];
            }
        }
        if (!(substr(Request::server('REMOTE_ADDR'), 0, 10) == '192.168.1.'
            || substr(Request::server('REMOTE_ADDR'), 0, 11) == '192.168.32.'
            || substr(Request::server('REMOTE_ADDR'), 0, 11) == '192.168.34.'
            || in_array(substr(Request::server('REMOTE_ADDR'), 0, 10),
                ['172.16.2.3','172.16.2.4','172.16.2.5','172.16.2.6','172.16.1.1','172.16.1.29'])
        )) {
            return App::abort(404);
        }

        return $next($request);
    }
}
