<?php

namespace App\Providers;

use App\Loom\Helper;
use Illuminate\Support\ServiceProvider;
use Loom;

class LoomServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Loom::setResourceControllerBasePath(config('loom.resources.controllerBasePath'));
        Loom::setResourceControllerNamespace(config('loom.resources.controllerNamespace'));

        Loom::setResourceBasePath(config('loom.resources.resourceBasePath'));
        Loom::setResourceNamespace(config('loom.resources.resourceNamespace'));
        Loom::setResourceRouteFilePath(config('loom.resources.resourceRouteFilePath'));
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
