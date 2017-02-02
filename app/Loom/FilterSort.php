<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use Illuminate\Database\Eloquent\Builder;

class FilterSort implements FilterContract
{

    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $direction;

    public function __construct($property, $direction)
    {
        $this->property = $property;
        $this->direction = $direction;
    }

    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query)
    {
//        $query->orderByRaw('cast(? as char) ' . $this->sort, [$this->property]);
        $query->orderBy($this->property, $this->direction);
    }

    /**
     * @return string
     */
    public function presentFilter()
    {
        return trans('quality-control.filterable.presenting.order by') . $this->property . trans('quality-control.filterable.presenting.' . $this->direction);
    }
}