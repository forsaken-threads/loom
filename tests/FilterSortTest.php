<?php

namespace ForsakenThreads\Loom\Tests;

use App\Loom\FilterSort;
use DB;

class FilterSortTest extends TestCase
{
    public function testSimpleSortAscending()
    {
        $query = [
            'query' => 'select * from "testable_resources" order by "rank" asc',
            'bindings' => [],
        ];

        $presented = trans('quality-control.filterable.presenting.order by') . 'rank' . trans('quality-control.filterable.presenting.asc');

        $q = $this->resource->newQuery();
        $filterSort = new FilterSort('rank', 'asc');
        $this->assertEquals($presented, $filterSort->presentFilter());

        $filterSort->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testSimpleSortDescending()
    {
        $query = [
            'query' => 'select * from "testable_resources" order by "rank" desc',
            'bindings' => [],
        ];

        $presented = trans('quality-control.filterable.presenting.order by') . 'rank' . trans('quality-control.filterable.presenting.desc');

        $q = $this->resource->newQuery();
        $filterSort = new FilterSort('rank', 'desc');
        $this->assertEquals($presented, $filterSort->presentFilter());

        $filterSort->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testAsCharSortAscending()
    {
        $query = [
            'query' => 'select * from "testable_resources" order by cast(? as char) asc',
            'bindings' => ['rank'],
        ];

        $presented =  trans('quality-control.filterable.presenting.order by') . 'rank' . trans('quality-control.filterable.presenting.as a string') . trans('quality-control.filterable.presenting.asc');

        $q = $this->resource->newQuery();
        $filterSort = new FilterSort('rank', 'asc', trans('quality-control.filterable.instructions.asString'));
        $this->assertEquals($presented, $filterSort->presentFilter());

        $filterSort->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testAsCharSortDescending()
    {
        $query = [
            'query' => 'select * from "testable_resources" order by cast(? as char) desc',
            'bindings' => ['rank'],
        ];

        $presented =  trans('quality-control.filterable.presenting.order by') . 'rank' . trans('quality-control.filterable.presenting.as a string') . trans('quality-control.filterable.presenting.desc');

        $q = $this->resource->newQuery();
        $filterSort = new FilterSort('rank', 'desc', trans('quality-control.filterable.instructions.asString'));
        $this->assertEquals($presented, $filterSort->presentFilter());

        $filterSort->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

}
