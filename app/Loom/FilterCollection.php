<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use App\Exceptions\LoomException;
use Illuminate\Database\Eloquent\Builder;

class FilterCollection
{

    /**
     * @var array
     */
    protected $collection = [];

    /**
     * @var bool
     */
    protected $orTogether;

    /**
     * FilterCollection constructor.
     *
     * @param array $filters
     * @param bool $orTogether
     * @throws LoomException
     */
    public function __construct(array $filters = [], $orTogether = false)
    {
        foreach ($filters as $property => $filter) {
            if (!ctype_alpha($property[0])) {
                throw new LoomException(trans('quality-control.filterable.expected-property', ['property' => $property]));
            }
            $this->addFilter($property, $filter);
        }
        $this->orTogether = $orTogether;
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
        if (!ctype_alpha($property[0])) {
            throw new LoomException(trans('quality-control.filterable.expected-property', ['property' => $property]));
        }
        if (! $filter instanceof FilterContract && ! $filter instanceof FilterCollection) {
            throw new LoomException(trans('quality-control.filterable.expected-filter-or-collection', ['got' => print_r($filter, true)]));
        }
        $this->collection[$property] = $filter;
        return $this;
    }

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