<?php

namespace App\Contracts;

use App\Loom\Filter;

interface DefaultFilterable
{
    /**
     * @return Filter[]
     */
    function getDefaultFilters();
}