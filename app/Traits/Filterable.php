<?php

namespace App\Traits;

use App\Contracts\DefaultFilterable;
use App\Contracts\Filter as FilterContract;
use App\Exceptions\LoomException;
use App\Loom\Filter;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{

    /**
     * @return array
     */
    abstract public function getFilterValidationRules();

    /**
     * @param string $scopeName
     * @return FilterScope
     */
    abstract public function getFilterScope($scopeName);

    /**
     * @param $givenFilters
     * @param Builder $query
     * @param bool $orTogether
     * @return array
     * @throws LoomException
     */
    public function applyFilters($givenFilters, Builder $query, $orTogether = false)
    {
        if (is_string($givenFilters)) {
            $givenFilters = json_decode($givenFilters, true);
        }

        if (!$givenFilters || !is_array($givenFilters)) {
            if (! $this instanceof DefaultFilterable) {
                return [];
            } else {
                $filters = $this->getDefaultFilters();
                if (!$filters instanceof FilterCollection) {
                    throw new LoomException(trans('quality-control.filterable.get-default-filters-error', ['class' => __CLASS__]));
                }
            }
        } else {
            $filters = $this->getValidFilters($this->getFilterValidationRules(), $givenFilters);
        }

        /**
         * @var FilterCollection $filters
         * @var FilterContract $filter
         */
        foreach ($filters as $property => $filter) {
            $filter->applyFilter($query, $orTogether);
        }

        return $this->presentFilters($filters);
    }

    /**
     * @param array $filterRules
     * @param array $givenFilters
     * @return FilterCollection
     */
    public function getValidFilters(array $filterRules, array $givenFilters)
    {
        $returnFilters = new FilterCollection();
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
        foreach ($validFilters as $property => $validFilter) {
//            if (in_array($property, $this->getActiveEagerLinks())) {
//                $returnFilters['__auto_eager_links'][$property] = $validFilters[$property];
//                continue;
//            }
            $returnFilters->addFilter($property, new Filter($validFilter, $property, isset($instructions[$property]) ? $instructions[$property] : null));
        }

        // Validate and include any valid scopes
        if (isset($givenFilters[trans('quality-control.filterable.__scope')])) {
            if ($validFilterScopes = $this->getValidFilterScopes($givenFilters[trans('quality-control.filterable.__scope')])) {
                $returnFilters->addCollection($validFilterScopes);
            }
        }

        return $returnFilters;
    }

    /**
     * @param array $givenScopes
     * @return FilterCollection
     */
    protected function getValidFilterScopes(array $givenScopes)
    {
        $returnFilterScopes = new FilterCollection();
        foreach ($givenScopes as $scope => $arguments) {
            if (ctype_digit($scope . '')) {
                $scope = $arguments;
                $arguments = [];
            }
            if ($filterScope = $this->getFilterScope($scope)) {
                if ($filterScope->validateAndSetInput($arguments)) {
                    $returnFilterScopes->addFilter(trans('quality-control.filterable.applied-scope'). ':' . $scope, $filterScope);
                }
            }
        }
        return $returnFilterScopes;
    }

    /**
     * @param FilterCollection $filtersApplied
     * @return array
     */
    protected function presentFilters(FilterCollection $filtersApplied)
    {
        $filters = [];
        /** @var Filter $filter */
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
            case trans('quality-control.filterable.instructions.applyScope'):
                // pass-through
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