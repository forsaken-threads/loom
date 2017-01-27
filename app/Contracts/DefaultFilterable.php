<?php

namespace App\Contracts;

use App\Loom\FilterCollection;

interface DefaultFilterable
{
    /**
     * @return FilterCollection
     */
    function getDefaultFilters();
}