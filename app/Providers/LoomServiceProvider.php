<?php

namespace App\Providers;

use App\Resources\LoomHelper;
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

        Loom::setResourceModelBasePath(config('loom.resources.modelBasePath'));
        Loom::setResourceModelNamespace(config('loom.resources.modelNamespace'));
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LoomHelper::class, function() {
            return new LoomHelper();
        });
    }
}
