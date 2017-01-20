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

        Webstuhl::setResourceModelBasePath(config('webstuhl.resources.modelBasePath'));
        Webstuhl::setResourceModelNamespace(config('webstuhl.resources.modelNamespace'));

        foreach (WebstuhlResource::all() as $resource) {

        }
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
