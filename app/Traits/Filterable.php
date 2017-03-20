<?php

namespace App\Traits;

use App\Contracts\DefaultFilterable;
use App\Contracts\QualityControlContract;
use App\Exceptions\QualityControlException;
use App\Loom\FilterCriterion;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use App\Loom\FilterSort;
use App\Resources\LoomResource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Filterable
 * @package App\Traits
 *
 * @method QualityControlContract getQualityControl()
 */
trait Filterable
{
    /**
     * @param $givenFilters
     * @param Builder $query
     * @return array
     * @throws QualityControlException
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
                    throw new QualityControlException(trans('quality-control.filterable.get-default-filters-error', ['class' => __CLASS__, 'got' => print_r($filters, true)]));
                }
            }
        } else {
            $filters = $this->getValidFilters($givenFilters);
        }

        $filters->applyFilters($query);

        return $filters->presentFilters();
    }

    /**
     * @param array $givenFilters
     * @return FilterCollection
     */
    public function getValidFilters(array $givenFilters)
    {
        $collection = FilterCollection::make($givenFilters);

        // Collect resource filters
        FilterCriterion::collect($givenFilters, $collection, $this->getQualityControl());

        // Validate and include any connectable resource filters
        foreach ($this->getQualityControl()->getConnectableResources() as $resource) {
            if (!empty($givenFilters[$resource])) {
                $resourceClassName = get_class($this->$resource()->getRelated());
                /**
                 * This comment is simply for IDE autocompletion OCD. :-)
                 * The real resource will be something different.
                 *
                 * @var LoomResource $resourceInstance
                 */
                $resourceInstance = new $resourceClassName;
                if ($valid = $resourceInstance->getValidFilters($givenFilters[$resource])) {
                    $collection->addFilter($resource, $valid);
                }
            }
        }

        // Collect any resource scopes
        if (isset($givenFilters[trans('quality-control.filterable.__scope')])) {
            FilterScope::collect($givenFilters[trans('quality-control.filterable.__scope')], $collection, $this->getQualityControl());
        }

        // Collect any sorts
        if (isset($givenFilters[trans('quality-control.filterable.__sort')])) {
            // resource sorts
            FilterSort::collect($givenFilters[trans('quality-control.filterable.__sort')], $collection, $this->getQualityControl());

            // connectable resource sorts
            foreach ($givenFilters[trans('quality-control.filterable.__sort')] as $order => $givenSort) {
                $property = key($givenSort);
                $instructions = current($givenSort);

                if (in_array($property, $this->getQualityControl()->getConnectableResources())) {
                    $resourceClassName = get_class($this->$property()->getRelated());
                    $resourceInstance = new $resourceClassName;
                    /**
                     * This comment is simply for IDE autocompletion OCD. :-)
                     * The real resource will be something different.
                     *
                     * @var LoomResource $resourceInstance
                     */
                    FilterSort::collect($instructions, $collection, $resourceInstance->getQualityControl());
                }
            }
        }

        return $collection;
    }
}