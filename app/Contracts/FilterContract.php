<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterContract
{
    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query);

    /**
     * @return string
     */
    public function presentFilter();
}