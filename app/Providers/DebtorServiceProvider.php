<?php

namespace App\Providers;

use App\Services\MassRecurrentService;
use Illuminate\Support\ServiceProvider;

class DebtorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(MassRecurrentService::class, function () {
            return new MassRecurrentService(
                auth()->user()
            );
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
