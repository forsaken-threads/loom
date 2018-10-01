<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use App\Contracts\QualityControlContract;
use App\Exceptions\LoomException;
use Illuminate\Database\Eloquent\Builder;

class FilterCollection
{
    /**
     * @var array
     */
    protected $collection = [];

    /**
     * @var FilterPivot
     */
    protected $filterPivot;

    /**
     * @var bool
     */
    protected $negated;

    /**
     * @var bool
     */
    protected $orTogether;

    /**
     * @param array $givenFilters
     * @param QualityControlContract $qualityControl
     * @param null|QualityControlContract $sourceQualityControl
     * @return FilterCollection
     */
    public static function make(array $givenFilters, QualityControlContract $qualityControl, $sourceQualityControl = null)
    {
        $negated = false;
        if (key_exists(trans('quality-control.filterable.__not'), $givenFilters)) {
            $negated = true;
            $givenFilters = $givenFilters[trans('quality-control.filterable.__not')];
        }

        $orTogether = false;
        if (key_exists(trans('quality-control.filterable.__or'), $givenFilters)) {
            $orTogether = true;
            $givenFilters = $givenFilters[trans('quality-control.filterable.__or')];
        }

        if (key_exists(trans('quality-control.filterable.__pivot'), $givenFilters) && $sourceQualityControl != null) {
            $filterPivot = FilterPivot::collect($givenFilters[trans('quality-control.filterable.__pivot')], $qualityControl, $sourceQualityControl);
        } else {
            $filterPivot = new FilterPivot();
        }
        unset($givenFilters[trans('quality-control.filterable.__pivot')]);

        $collection = new FilterCollection($orTogether, $negated, $filterPivot);

        // Collect resource filters
        FilterCriterion::collect($givenFilters, $collection, $qualityControl);

        // Collect resource scopes
        FilterScope::collect($givenFilters, $collection, $qualityControl);

        // Collect sorts
        FilterSort::collect($givenFilters, $collection, $qualityControl);

        return $collection;
    }

    /**
     * FilterCollection constructor.
     *
     * @param bool $orTogether
     * @param bool $negated
     * @param null|FilterPivot $filterPivot
     */
    public function __construct($orTogether = false, $negated = false, FilterPivot $filterPivot = null)
    {
        $this->orTogether = $orTogether;
        $this->negated = $negated;
        $this->filterPivot = $filterPivot;
    }

    /**
     * @param FilterCollection $filterCollection
     * @return $this
     */
    public function addCollection(FilterCollection $filterCollection)
    {
        $this->collection = array_merge($this->collection, $filterCollection->getCollection());
        return $this;
    }

    /**
     * @param string $property
     * @param FilterContract|FilterCollection $filter
     * @return $this
     * @throws LoomException
     */
    public function addFilter($property, $filter)
    {
        if (!is_string($property) || !ctype_alpha($property[0])) {
            throw new LoomException(trans('quality-control.filterable.expected-property', ['property' => @(string) $property]));
        }
        if (! $filter instanceof FilterContract && ! $filter instanceof FilterCollection) {
            throw new LoomException(trans('quality-control.filterable.expected-filter-or-collection', ['got' => print_r($filter, true)]));
        }
        $this->collection[$property] = $filter;
        return $this;
    }

    /**
     * @param $resource
     * @param FilterCollection $filters
     * @param Builder $query
     */
    public function applyConnectedResourceFilters($resource, FilterCollection $filters, Builder $query)
    {
        $method = camel_case(($this->orTogether ? 'Or' : '') . 'WhereHas');
        $query->$method($resource, function ($q) use ($filters) {
            /** @var Builder $q */
            if (!$filters->orTogether) {
                $filters->applyFilters($q);
            } else {
                $q->where(function ($subQuery) use ($filters) {
                    $filters->applyFilters($subQuery);
                });
            }
        });
    }

    /**
     * @param Builder $query
     */
    public function applyFilters(Builder $query)
    {
        /** @var FilterContract|FilterCollection $filter */
        foreach ($this->collection as $property => $filter) {
            if ($filter instanceof FilterCollection) {
                $this->applyConnectedResourceFilters($property, $filter, $query);
                continue;
            }
            $filter->applyFilter($query);
        }
    }

    /**
     * @return array
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->collection) == 0;
    }

    /**
     * @return bool
     */
    public function isOrTogether()
    {
        return $this->orTogether;
    }

    /**
     * @return array
     */
    public function presentFilters()
    {
        $filters = [];
        /** @var FilterContract|FilterCollection $filter */
        foreach ($this->collection as $property => $filter) {
            if ($filter instanceof FilterCollection) {
                $filters[$property] = $filter->presentFilters();
                continue;
            }
            $filters[$property] = $filter->presentFilter();
        }
        return $filters;
    }

    /**
     * @param $property
     * @return $this
     */
    public function remove($property)
    {
        unset($this->collection[$property]);
        return $this;
    }
}