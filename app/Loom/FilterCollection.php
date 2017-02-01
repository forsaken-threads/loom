<?php

namespace App\Loom;

use App\Contracts\Filter as FilterContract;
use App\Exceptions\LoomException;
use Iterator;

class FilterCollection implements Iterator
{
    protected $collection = [];

    /**
     * FilterCollection constructor.
     * @param array $filters
     * @throws LoomException
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $property => $filter) {
            if (!ctype_alpha($property[0])) {
                throw new LoomException(trans('quality-control.filterable.expected-property', ['property' => $property]));
            }
            if (! $filter instanceof FilterContract) {
                throw new LoomException(trans('quality-control.filterable.expected-filter', ['got' => print_r($filter, true)]));
            }
            $this->addFilter($property, $filter);
        }
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
     * @param FilterContract $filter
     * @return $this
     * @throws LoomException
     */
    public function addFilter($property, FilterContract $filter)
    {
        if (!ctype_alpha($property[0])) {
            throw new LoomException(trans('quality-control.filterable.expected-property', ['property' => $property]));
        }
        $this->collection[$property] = $filter;
        return $this;
    }

    /**
     * @return array
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->collection);
    }

    public function remove($property)
    {
        unset($this->collection[$property]);
        return $this;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->collection);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return current($this->collection) instanceof FilterContract;
    }
}