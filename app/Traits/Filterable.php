<?php

namespace App\Traits;

use App\Contracts\DefaultFilterable;
use App\Loom\Filter;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{

    /**
     * @var array
     */
    protected $defaultFilter;

    /**
     * @return array
     */
    abstract public function getFilterValidationRules();

    /**
     * @param $givenFilters
     * @param Builder $query
     * @param bool $orTogether
     * @return array|null
     */
    public function applyFilters($givenFilters, Builder $query, $orTogether = false)
    {
        $filters = [];
        if (is_string($givenFilters)) {
            $givenFilters = json_decode($givenFilters, true);
        }
        if (!$givenFilters || !is_array($givenFilters)) {
            if (empty($this->defaultFilter) && ! $this instanceof DefaultFilterable) {
                return null;
            }
            if (!empty($this->defaultFilter)) {
                $filters = $this->defaultFilter;
            } elseif ($this instanceof DefaultFilterable) {
                $filters = $this->getDefaultFilters();
                if (!is_array($filters)) {
                    $filters = (array) $filters;
                }
            }
        } else {
            $filters = $this->getValidFilters($this->getFilterValidationRules(), $givenFilters);
        }
        /** @var Filter[] $filters */
        foreach ($filters as $property => $filter) {
            $filter->applyFilter($query, $orTogether);
        }
        return $this->presentFilters($filters);
    }

    /**
     * @param array $filterRules
     * @param array $givenFilters
     * @return array
     */
    public function getValidFilters(array $filterRules, array $givenFilters)
    {
        $potentialFilters = [];
        $instructions = [];
        $rules = [];
        foreach ($filterRules as $property => $ruleSet) {
            if (key_exists($property, $givenFilters) && !nonZeroEmpty($givenFilters[$property]) && !shallow($givenFilters[$property])) {
                $potentialFilters[$property] = $givenFilters[$property];
                if (!is_array($givenFilters[$property])) {
                    $rules[$property] = $ruleSet;
                } else {
                    if (!ctype_alpha(key($givenFilters[$property]))) {
                        $rules[$property . '.*'] = $ruleSet;
                    } else {
                        if ($potentialFilter = $this->processFilterInstructions($givenFilters[$property])) {
                            $potentialFilters[$property] = $potentialFilter['filter'];
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
//        foreach ($this->getActiveEagerLinks() as $eager_link) {
//            if (!empty($inputFilters[snake_case($eager_link)])) {
//                $eager_model_name = get_class($this->$eager_link()->getRelated());
//                $eager_model = new $eager_model_name;
//                $validFilters[$eager_link] = $eager_model->getFiltersViaDefaultValidationRules($inputFilters[snake_case($eager_link)]);
//            }
//        }
//        $returnFilters = $returnFilters['__auto_eager_links'] = [];
        $returnFilters = [];
        foreach ($validFilters as $property => $validFilter) {
//            if (in_array($property, $this->getActiveEagerLinks())) {
//                $returnFilters['__auto_eager_links'][$property] = $validFilters[$property];
//                continue;
//            }
            $returnFilters[$property] = new Filter($validFilter, $property, isset($instructions[$property]) ? $instructions[$property] : null);
        }
        return $returnFilters;
    }

    /**
     * @param array $filtersApplied
     * @return array
     */
    protected function presentFilters(array $filtersApplied)
    {
        $filters = [];
        /** @var Filter[] $filtersApplied */
        foreach ($filtersApplied as $property => $filter) {
//            if ($property == '__auto_eager_links') {
//                foreach ($filter as $eager_model => $eager_filters) {
//                    $filters[$eager_model] = $this->presentFilters($eager_filters);
//                }
//                continue;
//            }
            $filters[$property] = $filter->presentFilter();
        }
        return $filters;
    }

    /**
     * @param $givenFilter
     * @return array|bool
     */
    protected function processFilterInstructions($givenFilter)
    {
        $instruction = key($givenFilter);

        $potentialFilter = ['instruction' => $instruction];
        switch ($instruction) {
            case trans('quality-control.filterable.instructions.between'):
                // pass-through
            case trans('quality-control.filterable.instructions.notBetween'):
                if (count($givenFilter[$instruction]) != 2 || !is_string(current($givenFilter[$instruction])) || !is_string(next($givenFilter[$instruction]))) {
                    break;
                }
                $potentialFilter['filter'] = $givenFilter[$instruction];
                break;
            case trans('quality-control.filterable.instructions.exactly'):
                // pass-through
            case trans('quality-control.filterable.instructions.not'):
                // pass-through
            case trans('quality-control.filterable.instructions.notExactly'):
                $potentialFilter['filter'] = $givenFilter[$instruction];
                break;
        }

        return isset($potentialFilter['filter']) ? $potentialFilter : false;
    }
}