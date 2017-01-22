<?php

namespace App\Providers;

use App\Resources\WebstuhlResource;
use App\WebstuhlHelper;
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
        $this->app->singleton(WebstuhlHelper::class, function() {
            return new WebstuhlHelper();
        });
    }
}
