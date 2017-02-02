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

    public function testSimpleSort()
    {
        $query = [
            'query' => 'select * from "testable_resources" order by "rank" asc',
            'bindings' => [],
        ];

        $q = $this->resource->newQuery();
        $filterSort = new FilterSort('rank', 'asc');
        $filterSort->applyFilter($q, false);
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

}
