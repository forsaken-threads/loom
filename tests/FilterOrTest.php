<?php

namespace ForsakenThreads\Loom\Tests;

class FilterOrTest extends TestCase
{
    public function testSimpleFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople'
            ]
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => 'awesomePeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or ("level" > ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople' => []
            ]
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => 'awesomePeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or ("level" > ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 40]
            ]
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => 'awesomeishPeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or ("level" > ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 20]
            ],
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nick_name' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople'
            ],
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nick_name' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => 'awesomeishPeople',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or ("level" > ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.exactly') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.exactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.exactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.exactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nick_name' => trans('quality-control.filterable.presenting.is') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is') . '0',
            'level' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" in (?, ?) or "nick_name" = ? or cast(? as char) like ? or "rank" = ? or "level" in (?, ?, ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notExactly') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.notExactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.notExactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.notExactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nick_name' => trans('quality-control.filterable.presenting.is not') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is not') . '0',
            'level' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" not in (?, ?) or "nick_name" != ? or cast(? as char) like ? or "rank" != ? or "level" not in (?, ?, ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.not') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.not') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.not') => '0'],
            'level' => [trans('quality-control.filterable.instructions.not') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.not like') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nick_name' => trans('quality-control.filterable.presenting.not like') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.not like') . '0',
            'level' => trans('quality-control.filterable.presenting.not like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where (cast(? as char) not like ? and cast(? as char) not like ?) or cast(? as char) not like ? or cast(? as char) like ? or cast(? as char) not like ? or (cast(? as char) not like ? and cast(? as char) not like ? and cast(? as char) not like ?)',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '0']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.between', ['filter1-name', 'filter2-name']),
            'nick_name' => trans('quality-control.filterable.presenting.between', ['filter1-nickname', 'filter2-nickname']),
            'rank' => trans('quality-control.filterable.presenting.between', [0, 50]),
            'level' => trans('quality-control.filterable.presenting.between', [-50, 0]),
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" between ? and ? or "nick_name" between ? and ? or cast(? as char) like ? or "rank" between ? and ? or "level" between ? and ?',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', 'filter2-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '0']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.not between', ['filter1-name', 'filter2-name']),
            'nick_name' => trans('quality-control.filterable.presenting.not between', ['filter1-nickname', 'filter2-nickname']),
            'rank' => trans('quality-control.filterable.presenting.not between', [0, 50]),
            'level' => trans('quality-control.filterable.presenting.not between', [-50, 0]),
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" not between ? and ? or "nick_name" not between ? and ? or cast(? as char) like ? or "rank" not between ? and ? or "level" not between ? and ?',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', '']],
            'nick_name' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => '>= filter1-name',
            'nick_name' => '>= filter1-nickname',
            'rank' => '>= 0',
            'level' => '>= -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" >= ? or "nick_name" >= ? or cast(? as char) like ? or "rank" >= ? or "level" >= ?',
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
        $this->assertEquals($presented, $this->resource->filter($input, $q, true));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testNotGreaterThanFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', '']],
            'nick_name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => '< filter1-name',
            'nick_name' => '< filter1-nickname',
            'rank' => '< 0',
            'level' => '< -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" < ? or "nick_name" < ? or cast(? as char) like ? or "rank" < ? or "level" < ?',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['', '50']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => '<= filter1-name',
            'nick_name' => '<= filter1-nickname',
            'rank' => '<= 0',
            'level' => '<= 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" <= ? or "nick_name" <= ? or cast(? as char) like ? or "rank" <= ? or "level" <= ?',
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
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-name']],
            'nick_name' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['', '50']],
            'email' => 'filter-email',
        ]];

        $presented = [
            'name' => '> filter1-name',
            'nick_name' => '> filter1-nickname',
            'rank' => '> 0',
            'level' => '> 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" > ? or "nick_name" > ? or cast(? as char) like ? or "rank" > ? or "level" > ?',
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
