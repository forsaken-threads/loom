<?php

namespace ForsakenThreads\Loom\Tests;

class FilterAndTest extends TestCase
{
    public function testSimpleFilters()
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
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testSimpleFiltersAndSimpleScope()
    {
        $input = [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople'
            ]
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => 'awesomePeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?) and "level" > ?',
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
                75,
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testSimpleFiltersAndAlternativeSimpleScope()
    {
        $input = [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople' => [],
            ]
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => 'awesomePeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?) and "level" > ?',
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
                75,
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testSimpleFiltersAndComplexScope()
    {
        $input = [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 40],
            ]
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => 'awesomeishPeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?) and "level" > ?',
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
                40,
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testSimpleFiltersAndInvalidScope()
    {
        $input = [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 20],
            ]
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
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testSimpleFiltersAndComplexScopeWithDefaultValue()
    {
        $input = [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople',
            ]
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => 'awesomeishPeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ?) and cast(? as char) like ? and cast(? as char) like ? and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?) and "level" > ?',
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
                33,
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testExactlyFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.exactly') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.exactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.exactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.exactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nick_name' => trans('quality-control.filterable.presenting.is') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is') . '0',
            'level' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" in (?, ?) and "nick_name" = ? and cast(? as char) like ? and "rank" = ? and "level" in (?, ?, ?)',
            'bindings' => [
                'filter1-name',
                'filter2-name',
                'filter-nickname',
                'email',
                '%filter-email%',
                '0',
                '10',
                '-5',
                '0'
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testNotExactlyFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notExactly') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.notExactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.notExactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.notExactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nick_name' => trans('quality-control.filterable.presenting.is not') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is not') . '0',
            'level' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" not in (?, ?) and "nick_name" != ? and cast(? as char) like ? and "rank" != ? and "level" not in (?, ?, ?)',
            'bindings' => [
                'filter1-name',
                'filter2-name',
                'filter-nickname',
                'email',
                '%filter-email%',
                '0',
                '10',
                '-5',
                '0'
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testNotFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.not') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.not') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.not') => '0'],
            'level' => [trans('quality-control.filterable.instructions.not') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.not like') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nick_name' => trans('quality-control.filterable.presenting.not like') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.not like') . '0',
            'level' => trans('quality-control.filterable.presenting.not like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) not like ? and cast(? as char) not like ? and cast(? as char) not like ? and cast(? as char) like ? and cast(? as char) not like ? and cast(? as char) not like ? and cast(? as char) not like ? and cast(? as char) not like ?',
            'bindings' => [
                'name',
                '%filter1-name%',
                'name',
                '%filter2-name%',
                'nick_name',
                '%filter-nickname%',
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
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testBetweenFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '0']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => 'between filter1-name and filter2-name',
            'nick_name' => 'between filter1-nickname and filter2-nickname',
            'rank' => 'between 0 and 50',
            'level' => 'between -50 and 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" between ? and ? and "nick_name" between ? and ? and cast(? as char) like ? and "rank" between ? and ? and "level" between ? and ?',
            'bindings' => [
                'filter1-name',
                'filter2-name',
                'filter1-nickname',
                'filter2-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
                '-50',
                '0',
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testNotBetweenFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '0']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => 'not between filter1-name and filter2-name',
            'nick_name' => 'not between filter1-nickname and filter2-nickname',
            'rank' => 'not between 0 and 50',
            'level' => 'not between -50 and 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" not between ? and ? and "nick_name" not between ? and ? and cast(? as char) like ? and "rank" not between ? and ? and "level" not between ? and ?',
            'bindings' => [
                'filter1-name',
                'filter2-name',
                'filter1-nickname',
                'filter2-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
                '-50',
                '0'
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testGreaterThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', '']],
            'nick_name' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => '>= filter1-name',
            'nick_name' => '>= filter1-nickname',
            'rank' => '>= 0',
            'level' => '>= -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" >= ? and "nick_name" >= ? and cast(? as char) like ? and "rank" >= ? and "level" >= ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '-50',
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testNotGreaterThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', '']],
            'nick_name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => '< filter1-name',
            'nick_name' => '< filter1-nickname',
            'rank' => '< 0',
            'level' => '< -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" < ? and "nick_name" < ? and cast(? as char) like ? and "rank" < ? and "level" < ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '-50',
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testLessThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['', '50']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => '<= filter1-name',
            'nick_name' => '<= filter1-nickname',
            'rank' => '<= 0',
            'level' => '<= 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" <= ? and "nick_name" <= ? and cast(? as char) like ? and "rank" <= ? and "level" <= ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testNotLessThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['', '50']],
            'email' => 'filter-email',
        ];

        $presented = [
            'name' => '> filter1-name',
            'nick_name' => '> filter1-nickname',
            'rank' => '> 0',
            'level' => '> 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" > ? and "nick_name" > ? and cast(? as char) like ? and "rank" > ? and "level" > ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
            ],
        ];

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }
}
