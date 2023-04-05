<?php

namespace App\Http;

use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateOnceWithBasicAuth;
use App\Http\Middleware\AuthOnlyMiddleware;
use App\Http\Middleware\ChangeSubdivOnceMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\InfinityOnlyMiddleware;
use App\Http\Middleware\LocalCallOnlyMiddleware;
use App\Http\Middleware\OfficeOnlyMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{

    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
        'App\Http\Middleware\CheckForMaintenance',
        'Illuminate\Cookie\Middleware\EncryptCookies',
        'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
        'Illuminate\Session\Middleware\StartSession',
        'Illuminate\View\Middleware\ShareErrorsFromSession',
        'App\Http\Middleware\VerifyCsrfToken',
        'App\Http\Middleware\CheckForEmploymentDocs',
    ];

    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            'throttle:60,1',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic.once' => AuthenticateOnceWithBasicAuth::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'guest' => RedirectIfAuthenticated::class,
        'admin_only' => AdminOnlyMiddleware::class,
        'auth_only' => AuthOnlyMiddleware::class,
        'office_only' => OfficeOnlyMiddleware::class,
        'infinity_only' => InfinityOnlyMiddleware::class,
        'localCallOnly' => LocalCallOnlyMiddleware::class,
        'csrf' => CsrfMiddleware::class,
        'change_subdiv_once' => ChangeSubdivOnceMiddleware::class,
    ];

}
