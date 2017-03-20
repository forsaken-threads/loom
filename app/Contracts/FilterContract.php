<?php

namespace App\Contracts;

use App\Loom\FilterCollection;
use Illuminate\Database\Eloquent\Builder;

interface FilterContract
{

    /**
     * @param array $givenFilters
     * @param FilterCollection $collection
     * @param QualityControlContract $qualityControl
     */
    public static function collect(array $givenFilters, FilterCollection $collection, QualityControlContract $qualityControl);

    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query);

    /**
     * @return string
     */
    public function presentFilter();
}