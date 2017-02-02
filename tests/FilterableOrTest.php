<?php

namespace ForsakenThreads\Loom\Tests;

use App\Loom\FilterCriteria;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class FilterableOrTest extends TestCase
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

    public function testSimpleFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria('filter-name', 'name', true),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true),
            'rank' => new FilterCriteria('0', 'rank', true),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true),
            'email' => new FilterCriteria('filter-email', 'email', true)
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input[trans('quality-control.filterable.__or')]['nickname'][0] = 'f';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testSimpleFiltersAndSimpleScope()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople'
            ]
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria('filter-name', 'name', true),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true),
            'rank' => new FilterCriteria('0', 'rank', true),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true),
            'email' => new FilterCriteria('filter-email', 'email', true),
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => new FilterScope('awesomePeople', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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
                75,
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndAlternativeSimpleScope()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople' => []
            ]
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria('filter-name', 'name', true),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true),
            'rank' => new FilterCriteria('0', 'rank', true),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true),
            'email' => new FilterCriteria('filter-email', 'email', true),
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => new FilterScope('awesomePeople', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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
                75,
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndComplexScope()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 40]
            ]
        ]];

        $filterScope = new FilterScope('awesomeishPeople', true);
        $filterScope->withArguments('level')
            ->setArgumentDefault('level', 33)
            ->setValidationRules(['level' => 'numeric|between:30,100'])
            ->validateAndSetInput(['level' => 40]);

        $output = new FilterCollection([
            'name' => new FilterCriteria('filter-name', 'name', true),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true),
            'rank' => new FilterCriteria('0', 'rank', true),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true),
            'email' => new FilterCriteria('filter-email', 'email', true),
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => $filterScope,
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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
                40,
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndInvalidScope()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 20]
            ],
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria('filter-name', 'name', true),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true),
            'rank' => new FilterCriteria('0', 'rank', true),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
            'rank' => trans('quality-control.filterable.presenting.like') . '0',
            'level' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndComplexScopeWithDefaultValue()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople'
            ],
        ]];

        $filterScope = new FilterScope('awesomeishPeople', true);
        $filterScope->withArguments('level')
            ->setArgumentDefault('level', 33)
            ->setValidationRules(['level' => 'numeric|between:30,100'])
            ->validateAndSetInput([]);

        $output = new FilterCollection([
            'name' => new FilterCriteria('filter-name', 'name', true),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true),
            'rank' => new FilterCriteria('0', 'rank', true),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true),
            'email' => new FilterCriteria('filter-email', 'email', true),
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => $filterScope,
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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
                33,
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testExactlyFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.exactly') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.exactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.exactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.exactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', 'filter2-name'], 'name', true, trans('quality-control.filterable.instructions.exactly')),
            'nickname' => new FilterCriteria('filter-nickname', 'nickname', true, trans('quality-control.filterable.instructions.exactly')),
            'rank' => new FilterCriteria('0', 'rank', true, trans('quality-control.filterable.instructions.exactly')),
            'level' => new FilterCriteria(['10', '-5', '-0'], 'level', true, trans('quality-control.filterable.instructions.exactly')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nickname' => trans('quality-control.filterable.presenting.is') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is') . '0',
            'level' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" in (?, ?) or "nickname" = ? or cast(? as char) like ? or "rank" = ? or "level" in (?, ?, ?)',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.exactly')] = '1';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotExactlyFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notExactly') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.notExactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.notExactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.notExactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', 'filter2-name'], 'name', true, trans('quality-control.filterable.instructions.notExactly')),
            'nickname' => new FilterCriteria('filter-nickname', 'nickname', true, trans('quality-control.filterable.instructions.notExactly')),
            'rank' => new FilterCriteria('0', 'rank', true, trans('quality-control.filterable.instructions.notExactly')),
            'level' => new FilterCriteria(['10', '-5', '-0'], 'level', true, trans('quality-control.filterable.instructions.notExactly')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nickname' => trans('quality-control.filterable.presenting.is not') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is not') . '0',
            'level' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $query = [
            'query' => 'select * from "testable_resources" where "name" not in (?, ?) or "nickname" != ? or cast(? as char) like ? or "rank" != ? or "level" not in (?, ?, ?)',
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
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.notExactly')] = '1';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.not') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.not') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.not') => '0'],
            'level' => [trans('quality-control.filterable.instructions.not') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', 'filter2-name'], 'name', true, trans('quality-control.filterable.instructions.not')),
            'nickname' => new FilterCriteria('filter-nickname', 'nickname', true, trans('quality-control.filterable.instructions.not')),
            'rank' => new FilterCriteria('0', 'rank', true, trans('quality-control.filterable.instructions.not')),
            'level' => new FilterCriteria(['10', '-5', '0'], 'level', true, trans('quality-control.filterable.instructions.not')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.not like') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nickname' => trans('quality-control.filterable.presenting.not like') . 'filter-nickname',
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
                'nickname',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.not')] = '1';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testBetweenFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '0']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', 'filter2-name'], 'name', true, trans('quality-control.filterable.instructions.between')),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true, trans('quality-control.filterable.instructions.between')),
            'rank' => new FilterCriteria(['0', '50'], 'rank', true, trans('quality-control.filterable.instructions.between')),
            'level' => new FilterCriteria(['-50', '0'], 'level', true, trans('quality-control.filterable.instructions.between')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.between', ['filter1-name', 'filter2-name']),
            'nickname' => trans('quality-control.filterable.presenting.between', ['filter1-nickname', 'filter2-nickname']),
            'rank' => trans('quality-control.filterable.presenting.between', [0, 50]),
            'level' => trans('quality-control.filterable.presenting.between', [-50, 0]),
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" between ? and ? or "nickname" between ? and ? or cast(? as char) like ? or "rank" between ? and ? or "level" between ? and ?',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input[trans('quality-control.filterable.__or')]['name'][trans('quality-control.filterable.instructions.between')][0]);
        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.between')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotBetweenFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '0']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', 'filter2-name'], 'name', true, trans('quality-control.filterable.instructions.notBetween')),
            'nickname' => new FilterCriteria(['filter1-nickname', 'filter2-nickname'], 'nickname', true, trans('quality-control.filterable.instructions.notBetween')),
            'rank' => new FilterCriteria(['0', '50'], 'rank', true, trans('quality-control.filterable.instructions.notBetween')),
            'level' => new FilterCriteria(['-50', '0'], 'level', true, trans('quality-control.filterable.instructions.notBetween')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.not between', ['filter1-name', 'filter2-name']),
            'nickname' => trans('quality-control.filterable.presenting.not between', ['filter1-nickname', 'filter2-nickname']),
            'rank' => trans('quality-control.filterable.presenting.not between', [0, 50]),
            'level' => trans('quality-control.filterable.presenting.not between', [-50, 0]),
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" not between ? and ? or "nickname" not between ? and ? or cast(? as char) like ? or "rank" not between ? and ? or "level" not between ? and ?',
            'bindings' => [
                'filter1-name',
                'filter2-name',
                'filter1-nickname',
                'filter2-nickname',
                'email',
                '%filter-email%',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input[trans('quality-control.filterable.__or')]['name'][trans('quality-control.filterable.instructions.notBetween')][0]);
        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.notBetween')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testGreaterThanFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', '']],
            'nickname' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', ''], 'name', true, trans('quality-control.filterable.instructions.between')),
            'nickname' => new FilterCriteria(['filter1-nickname', ''], 'nickname', true, trans('quality-control.filterable.instructions.between')),
            'rank' => new FilterCriteria(['0', ''], 'rank', true, trans('quality-control.filterable.instructions.between')),
            'level' => new FilterCriteria(['-50', ''], 'level', true, trans('quality-control.filterable.instructions.between')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => '>= filter1-name',
            'nickname' => '>= filter1-nickname',
            'rank' => '>= 0',
            'level' => '>= -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" >= ? or "nickname" >= ? or cast(? as char) like ? or "rank" >= ? or "level" >= ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '-50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q, true));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input[trans('quality-control.filterable.__or')]['name'][trans('quality-control.filterable.instructions.between')][0]);
        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.between')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotGreaterThanFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', '']],
            'nickname' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['filter1-name', ''], 'name', true, trans('quality-control.filterable.instructions.notBetween')),
            'nickname' => new FilterCriteria(['filter1-nickname', ''], 'nickname', true, trans('quality-control.filterable.instructions.notBetween')),
            'rank' => new FilterCriteria(['0', ''], 'rank', true, trans('quality-control.filterable.instructions.notBetween')),
            'level' => new FilterCriteria(['-50', ''], 'level', true, trans('quality-control.filterable.instructions.notBetween')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => '< filter1-name',
            'nickname' => '< filter1-nickname',
            'rank' => '< 0',
            'level' => '< -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" < ? or "nickname" < ? or cast(? as char) like ? or "rank" < ? or "level" < ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '-50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input[trans('quality-control.filterable.__or')]['name'][trans('quality-control.filterable.instructions.notBetween')][0]);
        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.notBetween')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testLessThanFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-name']],
            'nickname' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['', '50']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['', 'filter1-name'], 'name', true, trans('quality-control.filterable.instructions.between')),
            'nickname' => new FilterCriteria(['', 'filter1-nickname'], 'nickname', true, trans('quality-control.filterable.instructions.between')),
            'rank' => new FilterCriteria(['', '0'], 'rank', true, trans('quality-control.filterable.instructions.between')),
            'level' => new FilterCriteria(['', '50'], 'level', true, trans('quality-control.filterable.instructions.between')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => '<= filter1-name',
            'nickname' => '<= filter1-nickname',
            'rank' => '<= 0',
            'level' => '<= 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" <= ? or "nickname" <= ? or cast(? as char) like ? or "rank" <= ? or "level" <= ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input[trans('quality-control.filterable.__or')]['name'][trans('quality-control.filterable.instructions.between')][1]);
        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.between')][1] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotLessThanFilters()
    {
        $input = [trans('quality-control.filterable.__or') => [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-name']],
            'nickname' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['', '50']],
            'email' => 'filter-email',
        ]];

        $output = new FilterCollection([
            'name' => new FilterCriteria(['', 'filter1-name'], 'name', true, trans('quality-control.filterable.instructions.notBetween')),
            'nickname' => new FilterCriteria(['', 'filter1-nickname'], 'nickname', true, trans('quality-control.filterable.instructions.notBetween')),
            'rank' => new FilterCriteria(['', '0'], 'rank', true, trans('quality-control.filterable.instructions.notBetween')),
            'level' => new FilterCriteria(['', '50'], 'level', true, trans('quality-control.filterable.instructions.notBetween')),
            'email' => new FilterCriteria('filter-email', 'email', true),
        ], true);

        $presented = [
            'name' => '> filter1-name',
            'nickname' => '> filter1-nickname',
            'rank' => '> 0',
            'level' => '> 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" > ? or "nickname" > ? or cast(? as char) like ? or "rank" > ? or "level" > ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input[trans('quality-control.filterable.__or')]['name'][trans('quality-control.filterable.instructions.notBetween')][1]);
        $input[trans('quality-control.filterable.__or')]['nickname'][trans('quality-control.filterable.instructions.notBetween')][1] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterCriteriaValidationRules(), $input);
        $this->assertEquals($output, $result);
    }
}
