<?php

namespace App\Facades;

use App\Webstuhl\Helper;
use Illuminate\Support\Facades\Facade as BaseFacade;

class WebstuhlFacade extends BaseFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Helper::class;
    }
}