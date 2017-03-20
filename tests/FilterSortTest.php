<?php

namespace ForsakenThreads\Loom\Tests;

use App\Loom\FilterSort;
use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class FilterSortTest extends TestCase
{
    /** @var TestableResource */
    protected $resource;

    public function setUp()
    {
        parent::setUp();

        Schema::connection('testing')->create('testable_resources', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('name', 100);
            $table->string('nickname', 20)->unique();
            $table->string('email')->unique();
            $table->tinyInteger('rank');
            $table->tinyInteger('level');
            $table->string('password');
            $table->timestamps();
        });

        DB::connection('testing')->enableQueryLog();
        $this->resource = new TestableResource();
    }

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
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
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
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
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
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
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
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

}
