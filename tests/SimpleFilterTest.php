<?php

namespace ForsakenThreads\Loom\Tests;

use App\Exceptions\QualityControlException;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResourceThree;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResourceTwo;
use Illuminate\Database\Eloquent\Builder;

class SimpleFilterTest extends TestCase
{
    public function testFilterGivenArray()
    {
        $input = [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'nick_name',
                '%filter1-nickname%',
                'nick_name',
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
        $result = $this->resource->filter($input, $q);

        $this->assertEquals($presented, $result);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testFilterGivenJson()
    {
        $input = json_encode([
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'nick_name',
                '%filter1-nickname%',
                'nick_name',
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
        $result = $this->resource->filter($input, $q);

        $this->assertEquals($presented, $result);
        $q->get();
        $this->assertQueryEquals($query);
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
        $this->assertEquals($presented, $resource->filter([], $q));
        $q->get();
        $this->assertQueryEquals($query);
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
        $this->assertEquals($presented, $this->resource->filter([], $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testInvalidDefaultFilter()
    {
        $resource = new TestableResourceTwo();
        $q = app(Builder::class);
        try {
            $resource->filter([], $q);
        } catch (QualityControlException $e) {
            $this->assertEquals(trans('quality-control.filterable.get-default-filters-error', ['class' => get_class($resource), 'got' => print_r('bad juju', true)]), $e->getMessage());
        }
    }
}
