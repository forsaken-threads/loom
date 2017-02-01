<?php

namespace App\Traits;

use App\Contracts\DefaultFilterable;
use App\Exceptions\LoomException;
use App\Loom\Filter;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use App\Resources\LoomResource;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    use LoomConnectable;

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
     * @return array
     * @throws LoomException
     */
    public function applyFilters($givenFilters, Builder $query)
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
                    throw new LoomException(trans('quality-control.filterable.get-default-filters-error', ['class' => __CLASS__, 'got' => get_class($filters)]));
                }
            }
        } else {
            $filters = $this->getValidFilters($this->getFilterValidationRules(), $givenFilters);
        }

        $filters->applyFilters($query);

        return $filters->presentFilters();
    }

    /**
     * @param array $filterRules
     * @param array $givenFilters
     * @return FilterCollection
     */
    public function getValidFilters(array $filterRules, array $givenFilters)
    {
        $orTogether = key($givenFilters) . '' == trans('quality-control.filterable.__or');
        $givenFilters = !$orTogether  ? $givenFilters : $givenFilters[trans('quality-control.filterable.__or')];
        $returnFilters = new FilterCollection([], $orTogether);
        $potentialFilters = [];
        $instructions = [];
        $rules = [];

        // Validate and include any resource level filters
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
        foreach ($validFilters as $property => $validFilter) {
            $returnFilters->addFilter($property, new Filter($validFilter, $property, isset($instructions[$property]) ? $instructions[$property] : null));
        }

        // Validate and include any connectable resource filters
        foreach ($this->getConnectableResources() as $resource) {
            if (!empty($givenFilters[$resource])) {
                $resourceName = get_class($this->$resource()->getRelated());
                /**
                 * This comment is simply for IDE autocompletion OCD. :-)
                 * The real resource will be something different.
                 *
                 * @var LoomResource $resourceInstance
                 */
                $resourceInstance = new $resourceName;
                $resourceFilters = key($givenFilters[$resource]) != trans('quality-control.filterable.__or') ? $givenFilters[$resource] : $givenFilters[$resource][trans('quality-control.filterable.__or')];
                if ($valid = $resourceInstance->getValidFilters($resourceInstance->getFilterValidationRules(), $resourceFilters)) {
                    $returnFilters->addFilter($resource, $valid);
                }
            }
        }

        // Validate and include any valid resource scopes
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
        $orTogether = key($givenScopes) . '' == trans('quality-control.filterable.__or');
        $givenScopes = !$orTogether ? $givenScopes : $givenScopes[trans('quality-control.filterable.__or')];
        $returnFilterScopes = new FilterCollection([], $orTogether);
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