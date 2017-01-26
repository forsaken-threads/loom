<?php

namespace ForsakenThreads\Loom\Tests;

use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class FilterableCustomFilter extends TestCase
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

    public function testDefaultFilter()
    {
        $presented = [
            'rank' => trans('quality-control.filterable.presenting.between', [0, 100]),
            'level' => trans('quality-control.filterable.presenting.between', [50, 100]),
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "rank" between ? and ? and "level" between ? and ?',
            'bindings' => [
                '0',
                '100',
                '50',
                '100',
            ],
        ];
        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters([], $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

    }
}
