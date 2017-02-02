<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use Illuminate\Database\Eloquent\Builder;

class FilterSort implements FilterContract
{
    /**
     * @var string
     */
    protected $direction;

    /**
     * @var string
     */
    protected $instruction;

    /**
     * @var string
     */
    protected $property;

    /**
     * FilterSort constructor.
     *
     * @param $property
     * @param $direction
     * @param null $instruction
     */
    public function __construct($property, $direction, $instruction = null)
    {
        $this->property = $property;
        $this->direction = $direction;
        $this->instruction = $instruction;
    }

    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query)
    {
        switch ($this->instruction) {
            case trans('quality-control.filterable.instructions.asChar'):
                $query->orderByRaw('cast(? as char) ' . $this->direction, [$this->property]);
                break;
            default:
                $query->orderBy($this->property, $this->direction);
        }
    }

    /**
     * @return string
     */
    public function presentFilter()
    {
        switch ($this->instruction) {
            case trans('quality-control.filterable.instructions.asChar'):
                return trans('quality-control.filterable.presenting.order by') . $this->property . trans('quality-control.filterable.presenting.as a string') . trans('quality-control.filterable.presenting.' . $this->direction);
            default:
                return trans('quality-control.filterable.presenting.order by') . $this->property . trans('quality-control.filterable.presenting.' . $this->direction);
        }
    }
}