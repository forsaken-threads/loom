<?php

namespace App\Resources;

use Illuminate\Support\Facades\Facade;

class WebstuhlFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return WebstuhlHelper::class;
    }
}