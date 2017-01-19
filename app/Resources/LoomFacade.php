<?php

namespace App\Resources;

use Illuminate\Support\Facades\Facade;

class LoomFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoomHelper::class;
    }
}