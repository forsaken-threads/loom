<?php

namespace App\Loom;

use App\Exceptions\QualityControlException;
use ArrayAccess;
use Iterator;

class Inspections implements ArrayAccess, Iterator
{
    const CREATE = 'create';
    const FILTER = 'filter';
    const SORT = 'sort';
    const UPDATE = 'update';

    protected $rules      = [];
    protected $position   = 0;
    protected $properties = [];

    public function __construct(array $inspections)
    {
        foreach ($inspections as $property => $assurances) {
            $this->offsetSet($property, $assurances);
        }
    }

    /**
     * Whether an offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $property <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to bool if non-bool was returned.
     * @since 5.0.0
     */
    public function offsetExists($property)
    {
        return in_array($property, $this->properties);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $property
     * @return mixed Can return all value types.
     * The offset to retrieve.
     * </p>
     * @since 5.0.0
     */
    public function offsetGet($property)
    {
        $key = array_search($property, $this->properties);
        if ($key === false) {
            trigger_error(trans('quality-control.filterable.expected-property', ['property' => $property]));
            return false;
        }
        return $this->rules[$key];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $property <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $assurances <p>
     * The value to set.
     * </p>
     * @throws QualityControlException
     * @since 5.0.0
     */
    public function offsetSet($property, $assurances)
    {
        if (!is_string($property) || !ctype_alpha($property[0])) {
            throw new QualityControlException(trans('quality-control.filterable.expected-property', ['property' => @(string) $property]));
        }
        $key = array_search($property, $this->properties);
        $key = $key !== false ? $key : count($this->properties);
        $this->properties[$key] = $property;
        $this->rules[$key] = $assurances;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $property <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($property)
    {
        $key = array_search($property, $this->properties);
        if ($key !== false) {
            if ($this->position >= $key) {
                $this->position--;
            }
            array_slice($this->properties, $key, 1);
            array_slice($this->rules, $key, 1);
        }
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->rules[$this->position];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->properties[$this->position];
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to bool and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->position < count($this->properties);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->position = 0;
    }
}