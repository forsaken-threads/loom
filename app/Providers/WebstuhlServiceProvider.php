<?php

namespace App\Providers;

use App\Webstuhl\Helper;
use Illuminate\Support\ServiceProvider;
use Webstuhl;

class WebstuhlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Webstuhl::setResourceControllerBasePath(config('webstuhl.resources.controllerBasePath'));
        Webstuhl::setResourceControllerNamespace(config('webstuhl.resources.controllerNamespace'));

        Webstuhl::setResourceBasePath(config('webstuhl.resources.resourceBasePath'));
        Webstuhl::setResourceNamespace(config('webstuhl.resources.resourceNamespace'));
        Webstuhl::setResourceRouteFilePath(config('webstuhl.resources.resourceRouteFilePath'));
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Helper::class, function() {
            return new Helper();
        });
    }
}
