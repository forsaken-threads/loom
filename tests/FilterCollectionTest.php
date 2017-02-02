<?php

namespace ForsakenThreads\Loom\Tests;

use App\Exceptions\LoomException;
use App\Loom\FilterCriteria;
use App\Loom\FilterCollection;

class FilterCollectionTest extends TestCase
{
    public function testValidConstructors()
    {
        $collection = new FilterCollection([
            'property' => new FilterCriteria('filter-name', 'name'),
        ]);
        foreach ($collection as $property => $filter) {
            $this->assertEquals('property', $property);
            $this->assertEquals(new FilterCriteria('filter-name', 'name'), $filter);
        }
    }

    public function testInvalidConstructors()
    {
        try {
            $collection = new FilterCollection([new FilterCriteria('filter-name', 'name')]);
        } catch (LoomException $e) {
            $this->assertEquals(trans('quality-control.filterable.expected-property', ['property' => 0]), $e->getMessage());
        }
        try {
            $collection = new FilterCollection(['name' => 'not a filter']);
        } catch (LoomException $e) {
            $this->assertEquals(trans('quality-control.filterable.expected-filter-or-collection', ['got' => 'not a filter']), $e->getMessage());
        }
    }

    public function testValidAddFilter()
    {
        $collection = new FilterCollection();
        $collection->addFilter('property', new FilterCriteria('filter-name', 'name'));
        foreach ($collection as $property => $filter) {
            $this->assertEquals('property', $property);
            $this->assertEquals(new FilterCriteria('filter-name', 'name'), $filter);
        }
    }

    public function testInvalidAddFilter()
    {
        $collection = new FilterCollection();
        try {
            $collection->addFilter('0', new FilterCriteria('filter-name', 'name'));
        } catch (LoomException $e) {
            $this->assertEquals(trans('quality-control.filterable.expected-property', ['property' => 0]), $e->getMessage());
        }
    }
}
