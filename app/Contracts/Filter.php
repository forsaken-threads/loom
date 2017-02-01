<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    /**
     * @param Builder $query
     * @param $orTogether
     */
    public function applyFilter(Builder $query, $orTogether);

    /**
     * @return string
     */
    public function presentFilter();
}