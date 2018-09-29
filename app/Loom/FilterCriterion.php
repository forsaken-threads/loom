<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use App\Contracts\QualityControlContract;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;

class FilterCriterion implements FilterContract
{

    /**
     * @var string
     */
    protected $comparator;

    /**
     * @var string|array
     */
    protected $criterion;

    /**
     * @var bool
     */
    protected $orTogether;

    /**
     * @var string
     */
    protected $property;

    /**
     * @param array $givenFilters
     * @param FilterCollection $collection
     * @param QualityControlContract $qualityControl
     * @return void
     */
    public static function collect(array $givenFilters, FilterCollection $collection, QualityControlContract $qualityControl)
    {
        $potentialFilters = [];
        $instructions = [];
        $rules = [];

        // Validate and include any resource level criteria
        foreach ($qualityControl->getRules(Inspections::FILTER) as $property => $ruleSet) {
            if (key_exists($property, $givenFilters) && !nonZeroEmpty($givenFilters[$property]) && !shallow($givenFilters[$property])) {
                $potentialFilters[$property] = $givenFilters[$property];
                if (!is_array($givenFilters[$property])) {
                    $rules[$property] = $ruleSet;
                } else {
                    if (!ctype_alpha(key($givenFilters[$property])[0])) {
                        $rules[$property . '.*'] = $ruleSet;
                    } else {
                        if ($potentialFilter = self::processFilterCriteriaInstructions($givenFilters[$property])) {
                            $potentialFilters[$property] = $potentialFilter['criteria'];
                            $instructions[$property] = $potentialFilter['instruction'];
                        } else {
                            unset($potentialFilters[$property]);
                            continue;
                        }
                        if (is_array(current($givenFilters[$property]))) {
                            $rules[$property . '.*'] = $ruleSet;
                        } else {
                            $rules[$property] = $ruleSet;
                        }
                    }
                }
            }
        }
        $validFilters = validator($potentialFilters, $rules)->valid();
        foreach ($validFilters as $property => $criteria) {
            $collection->addFilter($property,
                new FilterCriterion($criteria, $property, $collection->isOrTogether(), isset($instructions[$property]) ? $instructions[$property] : null));
        }

        // Validate and include any connectable resource filters
        foreach ($qualityControl->getConnectableResources() as $resource) {
            if (!empty($givenFilters[$resource])) {
                if ($resourceClassName = $qualityControl->getConnectableResource($resource)) {
                    $filters = FilterCollection::make($givenFilters[$resource], with(new $resourceClassName)->getQualityControl());
                    if (!$filters->isEmpty()) {
                        $collection->addFilter($resource, $filters);
                    }
                }
            }
        }
    }

    /**
     * @param $givenInstructions
     * @return array|bool
     */
    protected static function processFilterCriteriaInstructions($givenInstructions)
    {
        $instruction = key($givenInstructions);

        $potentialFilter = ['instruction' => $instruction];
        switch ($instruction) {
            case trans('quality-control.filterable.instructions.between'):
                // pass-through
            case trans('quality-control.filterable.instructions.notBetween'):
                if (count($givenInstructions[$instruction]) != 2
                    || !is_string(current($givenInstructions[$instruction]))
                    || !is_string(next($givenInstructions[$instruction]))
                ) {
                    break;
                }
                $potentialFilter['criteria'] = $givenInstructions[$instruction];
                break;
            case trans('quality-control.filterable.instructions.applyScope'):
                // pass-through
            case trans('quality-control.filterable.instructions.exactly'):
                // pass-through
            case trans('quality-control.filterable.instructions.not'):
                // pass-through
            case trans('quality-control.filterable.instructions.notExactly'):
                $potentialFilter['criteria'] = $givenInstructions[$instruction];
                break;
        }

        return isset($potentialFilter['criteria']) ? $potentialFilter : false;
    }

    /**
     * Filter constructor.
     *
     * @param $criterion
     * @param $property
     * @param bool $orTogether
     * @param null $instruction
     */
    public function __construct($criterion, $property, $orTogether = false, $instruction = null)
    {
        $this->criterion = $criterion;
        $this->property = $property;
        if (is_null($instruction) && !is_bool($orTogether)) {
            $this->orTogether = false;
            $this->comparator = $this->determineComparator($orTogether);
        } else {
            $this->orTogether = $orTogether;
            $this->comparator = $this->determineComparator($instruction);
        }
    }

    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query)
    {
        $or = $this->orTogether ? 'Or' : '';
        switch ($this->comparator) {
            case 'equals':
                $method = camel_case($or . (is_array($this->criterion) ? 'WhereIn' : 'Where'));
                $query->$method($this->property, $this->criterion);
                break;
            case 'not equals':
                if (is_array($this->criterion)) {
                    $method = camel_case($or . 'WhereNotIn');
                    $query->$method($this->property, $this->criterion);
                } else {
                    $method = camel_case($or . 'Where');
                    $query->$method($this->property, '!=', $this->criterion);
                }
                break;
            case 'between':
                $method = camel_case($or . 'WhereBetween');
                $query->$method($this->property, $this->criterion);
                break;
            case 'not between':
                $method = camel_case($or . 'WhereNotBetween');
                $query->$method($this->property, $this->criterion);
                break;
            case '<':
                // pass-through
            case '>=':
                $method = camel_case($or . 'Where');
                $query->$method($this->property, $this->comparator, $this->criterion[0]);
                break;
            case '>':
                // pass-through
            case '<=':
                $method = camel_case($or . 'Where');
                $query->$method($this->property, $this->comparator, $this->criterion[1]);
                break;
            case 'not like':
                if (!is_array($this->criterion)) {
                    $method = camel_case($or . 'WhereRaw');
                    $query->$method('cast(? as char) not like ?', [$this->property, '%' . $this->criterion . '%']);
                } else {
                    if (!$or) {
                        foreach ($this->criterion as $pattern) {
                            $query->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                        }
                    } else {
                        $query->orWhere(function ($q) {
                            /** @var Builder $q */
                            foreach ($this->criterion as $pattern) {
                                $q->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                            }
                        });
                    }
                }
                break;
            case 'like':
            default:
                if (!is_array($this->criterion)) {
                    $method = camel_case($or . 'WhereRaw');
                    $query->$method('cast(? as char) like ?', [$this->property, '%' . $this->criterion . '%']);
                } else {
                    if ($or) {
                        foreach ($this->criterion as $pattern) {
                            $query->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                        }
                    } else {
                        $query->where(function ($q) {
                            /** @var Builder $q */
                            foreach ($this->criterion as $pattern) {
                                $q->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                            }
                        });
                    }
                }
        }
    }

    /**
     * @return string
     */
    public function presentFilter()
    {
        switch ($this->comparator) {
            case 'between':
                // pass-through
            case 'not between':
                return trans('quality-control.filterable.presenting.' . $this->comparator, $this->criterion);
            case '<':
                // pass-through
            case '>=':
                return $this->comparator . ' ' . $this->criterion[0];
                break;
            case '>':
                // pass-through
            case '<=':
                return $this->comparator . ' ' . $this->criterion[1];
                break;

            case 'not like':
                return trans('quality-control.filterable.presenting.not like') . (is_array($this->criterion) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criterion) : $this->criterion);
            case 'like':
                return trans('quality-control.filterable.presenting.like') . (is_array($this->criterion) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criterion) : $this->criterion);

            case 'equals':
                return trans('quality-control.filterable.presenting.is') . (is_array($this->criterion) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criterion) : $this->criterion);
            case 'not equals':
                return trans('quality-control.filterable.presenting.is not') . (is_array($this->criterion) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criterion) : $this->criterion);

            default:
                return $this->criterion;
        }
    }

    /**
     * @param $instruction
     * @return mixed|string
     */
    protected function determineComparator($instruction)
    {
        if ($instruction != 'between' && $instruction != 'notBetween') {
            if ($instruction == 'exactly') {
                return 'equals';
            }
            if ($instruction == 'notExactly') {
                return 'not equals';
            }
            return $instruction == 'not' ? 'not like' : 'like';
//            return preg_match('/id_exists/', implode('|', $rules)) ? $negated . 'equals' : $negated . 'like';
        }

        $not = $instruction == 'notBetween' ? 'not ' : '';
        if (!nonZeroEmpty($this->criterion[0]) && !nonZeroEmpty($this->criterion[1])) {
            return $not . 'between';
        } elseif (!nonZeroEmpty($this->criterion[0])) {
            return $not ? '<' : '>=';
        } else {
            return $not ? '>' : '<=';
        }
    }
}