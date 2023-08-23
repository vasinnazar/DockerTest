<?php

namespace App\Providers;

use Dadata\DadataClient;
use Illuminate\Support\ServiceProvider;

class DIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DadataClient::class, function () {
            return new DadataClient(
                env('DADATA_TOKEN',''),
                env('DADATA_SECRET', '')
            );
        });
        $this->app->alias(DadataClient::class, 'dadata');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
