<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use App\Contracts\QualityControlContract;
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
     * @param array $givenSorts
     * @param FilterCollection $collection
     * @param QualityControlContract $qualityControl
     */
    public static function collect(array $givenSorts, FilterCollection $collection, QualityControlContract $qualityControl)
    {
        $sortableProperties = array_keys($qualityControl->getRules(Inspections::SORT));
        foreach ($givenSorts as $order => $givenSort) {
            $property = key($givenSort);
            $instructions = current($givenSort);

            // validate and include a local resource sort
            if (in_array($property, $sortableProperties)) {
                if ($instructions = self::processFilterSortInstructions($instructions)) {
                    $collection->addFilter($property, new FilterSort($property, $instructions['direction'], $instructions['instruction']));
                }
            }
        }

    }

    /**
     * @param $givenInstructions
     * @return string|bool
     */
    protected static function processFilterSortInstructions($givenInstructions)
    {
        $instructions = [];
        if (!is_array($givenInstructions)) {
            $instructions['direction'] = in_array($givenInstructions, ['asc', 'desc']) ? $givenInstructions : 'asc';
            $instructions['instruction'] = null;
            return $instructions;
        }

        $instructions['direction'] = in_array(current($givenInstructions), ['asc', 'desc']) ? current($givenInstructions) : 'asc';
        $instruction = key($givenInstructions);
        switch ($instruction) {
            case trans('quality-control.filterable.instructions.asString'):
                $instructions['instruction'] = $instruction;
                break;
        }

        return isset($instructions['instruction']) ? $instructions : false;
    }

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
            case trans('quality-control.filterable.instructions.asString'):
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
            case trans('quality-control.filterable.instructions.asString'):
                return trans('quality-control.filterable.presenting.order by') . $this->property . trans('quality-control.filterable.presenting.as a string') . trans('quality-control.filterable.presenting.' . $this->direction);
            default:
                return trans('quality-control.filterable.presenting.order by') . $this->property . trans('quality-control.filterable.presenting.' . $this->direction);
        }
    }
}