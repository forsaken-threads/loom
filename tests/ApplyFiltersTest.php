<?php

namespace ForsakenThreads\Loom\Tests;

use App\Exceptions\LoomException;
use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResourceThree;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResourceTwo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class ApplyFiltersTest extends TestCase
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

        Schema::connection('testing')->create('testable_resource_threes', function (Blueprint $table) {
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

    public function testApplyFiltersGivenArray()
    {
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'nickname',
                '%filter1-nickname%',
                'nickname',
                '%filter2-nickname%',
                'email',
                '%filter-email%',
                'rank',
                '%0%',
                'level',
                '%10%',
                'level',
                '%-5%',
                'level',
                '%0%',
            ],
        ];

        $q = $this->resource->newQuery();
        $result = $this->resource->applyFilters($input, $q);

        $this->assertEquals($presented, $result);
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testApplyFiltersGivenJson()
    {
        $input = json_encode([
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'nickname',
                '%filter1-nickname%',
                'nickname',
                '%filter2-nickname%',
                'email',
                '%filter-email%',
                'rank',
                '%0%',
                'level',
                '%10%',
                'level',
                '%-5%',
                'level',
                '%0%',
            ],
        ];

        $q = $this->resource->newQuery();
        $result = $this->resource->applyFilters($input, $q);

        $this->assertEquals($presented, $result);
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testNoDefaultFilter()
    {
        $presented = [];

        $query = [
            'query' => 'select * from "testable_resource_threes"',
            'bindings' => [
            ],
        ];

        $resource = new TestableResourceThree();
        $q = $resource->newQuery();
        $this->assertEquals($presented, $resource->applyFilters([], $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

    }

    public function testValidDefaultFilter()
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

    public function testInvalidDefaultFilter()
    {
        $resource = new TestableResourceTwo();
        $q = app(Builder::class);
        try {
            $resource->applyFilters([], $q);
        } catch (LoomException $e) {
            $this->assertEquals(trans('quality-control.filterable.get-default-filters-error', ['class' => get_class($resource)]), $e->getMessage());
        }
    }
}
