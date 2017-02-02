<?php

namespace App\Traits;

use App\Contracts\DefaultFilterable;
use App\Exceptions\LoomException;
use App\Loom\FilterCriteria;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use App\Loom\FilterSort;
use App\Resources\LoomResource;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    use LoomConnectable;

    /**
     * @param string $scopeName
     * @return FilterScope
     */
    abstract public function getFilterScope($scopeName);

    /**
     * @return array
     */
    abstract public function getFilterCriteriaValidationRules();

    /**
     * @return array
     */
    abstract public function getSortableProperties();

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
                    throw new LoomException(trans('quality-control.filterable.get-default-filters-error', ['class' => __CLASS__, 'got' => print_r($filters, true)]));
                }
            }
        } else {
            $filters = $this->getValidFilters($this->getFilterCriteriaValidationRules(), $givenFilters);
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
                        if ($potentialFilter = $this->processFilterCriteriaInstructions($givenFilters[$property])) {
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
            $returnFilters->addFilter($property, new FilterCriteria($criteria, $property, $orTogether, isset($instructions[$property]) ? $instructions[$property] : null));
        }

        // Validate and include any connectable resource filters
        foreach ($this->getConnectableResources() as $resource) {
            if (!empty($givenFilters[$resource])) {
                $resourceClassName = get_class($this->$resource()->getRelated());
                /**
                 * This comment is simply for IDE autocompletion OCD. :-)
                 * The real resource will be something different.
                 *
                 * @var LoomResource $resourceInstance
                 */
                $resourceInstance = new $resourceClassName;
                if ($valid = $resourceInstance->getValidFilters($resourceInstance->getFilterCriteriaValidationRules(), $givenFilters[$resource])) {
                    $returnFilters->addFilter($resource, $valid);
                }
            }
        }

        // Validate and include any resource scopes
        if (isset($givenFilters[trans('quality-control.filterable.__scope')])) {
            if ($validFilterScopes = $this->getValidFilterScopes($givenFilters[trans('quality-control.filterable.__scope')], $orTogether)) {
                $returnFilters->addCollection($validFilterScopes);
            }
        }

        // Validate and include any sorts
        if (isset($givenFilters[trans('quality-control.filterable.__sort')])) {
            if ($validFilterSorts = $this->getValidFilterSorts($givenFilters[trans('quality-control.filterable.__sort')])) {
                $returnFilters->addCollection($validFilterSorts);
            }
        }

        return $returnFilters;
    }

    /**
     * @param array $givenScopes
     * @param $orTogether
     * @return FilterCollection|bool
     */
    protected function getValidFilterScopes(array $givenScopes, $orTogether)
    {
        $returnFilterScopes = new FilterCollection([], $orTogether);
        foreach ($givenScopes as $scope => $arguments) {
            if (ctype_digit($scope . '')) {
                $scope = $arguments;
                $arguments = [];
            }
            if ($filterScope = $this->getFilterScope($scope)) {
                if ($filterScope->validateAndSetInput($arguments)) {
                    $filterScope->setOrTogether($orTogether);
                    $returnFilterScopes->addFilter(trans('quality-control.filterable.applied-scope'). ':' . $scope, $filterScope);
                }
            }
        }
        return $returnFilterScopes->isEmpty() ? false : $returnFilterScopes;
    }

    /**
     * @param array $givenSorts
     * @return FilterCollection
     */
    public function getValidFilterSorts(array $givenSorts)
    {
        $sortableProperties = $this->getSortableProperties();
        $returnSorts = new FilterCollection();

        foreach ($givenSorts as $order => $givenSort) {
            $property = key($givenSort);
            $instructions = current($givenSort);

            // validate and include a local resource sort
            if (in_array($property, $sortableProperties)) {
                if ($instructions = $this->processFilterSortInstructions($instructions)) {
                    $returnSorts->addFilter($property, new FilterSort($property, $instructions['direction'], $instructions['instruction']));
                }

            // Validate and include a connectable resource sort
            } elseif (in_array($property, $this->getConnectableResources())) {
                $resourceClassName = get_class($this->$property()->getRelated());
                $resourceInstance = new $resourceClassName;
                /**
                 * This comment is simply for IDE autocompletion OCD. :-)
                 * The real resource will be something different.
                 *
                 * @var LoomResource $resourceInstance
                 */
                if ($validSorts = $resourceInstance->getValidFilterSorts($instructions)) {
                    $returnSorts->addFilter($property, $validSorts);
                }
            }
        }

        return $returnSorts;
    }

    /**
     * @param $givenInstructions
     * @return array|bool
     */
    protected function processFilterCriteriaInstructions($givenInstructions)
    {
        $instruction = key($givenInstructions);

        $potentialFilter = ['instruction' => $instruction];
        switch ($instruction) {
            case trans('quality-control.filterable.instructions.between'):
                // pass-through
            case trans('quality-control.filterable.instructions.notBetween'):
                if (count($givenInstructions[$instruction]) != 2 || !is_string(current($givenInstructions[$instruction])) || !is_string(next($givenInstructions[$instruction]))) {
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
     * @param $givenInstructions
     * @return string|bool
     */
    protected function processFilterSortInstructions($givenInstructions)
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
                $instructions['instruction'] =$instruction;
                break;
        }

        return isset($instructions['instruction']) ? $instructions : false;
    }
}