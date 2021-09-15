<?php

namespace App\Http\Middleware;

use Closure;

class CheckForMaintenance {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $allowedIpList = [
            '192.168.1.60',
            '127.0.0.1',
            '192.168.1.31',
            '192.168.1.123',
            '192.168.1.114',
            '192.168.1.47'
        ];
        if (config('admin.maintenance_mode') && strstr($request->path(),'maintenance')===FALSE && !in_array($request->ip(),$allowedIpList)) {
            return redirect('maintenance');
        } else {
            return $next($request);
        }
    }

}
