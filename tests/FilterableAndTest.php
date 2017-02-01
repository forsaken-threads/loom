<?php

namespace ForsakenThreads\Loom\Tests;

use App\Loom\Filter;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class FilterableAndTest extends TestCase
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
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email')
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input['nickname'][0] = 'f';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testSimpleFiltersAndSimpleScope()
    {
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople'
            ]
        ];

        $output = new FilterCollection([
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email'),
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => new FilterScope('awesomePeople'),
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndAlternativeSimpleScope()
    {
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomePeople' => [],
            ]
        ];

        $output = new FilterCollection([
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email'),
            trans('quality-control.filterable.applied-scope') . ':awesomePeople' => new FilterScope('awesomePeople'),
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndComplexScope()
    {
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 40],
            ]
        ];

        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope->withArguments('level')
            ->setArgumentDefault('level', 33)
            ->setValidationRules(['level' => 'numeric|between:30,100'])
            ->validateAndSetInput(['level' => 40]);

        $output = new FilterCollection([
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email'),
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => $filterScope,
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndInvalidScope()
    {
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople' => ['level' => 20],
            ]
        ];

        $output = new FilterCollection([
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email'),
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testSimpleFiltersAndComplexScopeWithDefaultValue()
    {
        $input = [
            'name' => 'filter-name',
            'nickname' => ['filter1-nickname', 'filter2-nickname'],
            'rank' => '0',
            'level' => ['10', '-5', '0'],
            'email' => 'filter-email',
            trans('quality-control.filterable.__scope') => [
                'awesomeishPeople',
            ]
        ];

        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope->withArguments('level')
            ->setArgumentDefault('level', 33)
            ->setValidationRules(['level' => 'numeric|between:30,100'])
            ->validateAndSetInput([]);

        $output = new FilterCollection([
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email'),
            trans('quality-control.filterable.applied-scope') . ':awesomeishPeople' => $filterScope,
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'nickname' => trans('quality-control.filterable.presenting.like') . trans('quality-control.filterable.presenting.any of') . 'filter1-nickname, filter2-nickname',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testExactlyFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.exactly') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.exactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.exactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.exactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', trans('quality-control.filterable.instructions.exactly')),
            'nickname' => new Filter('filter-nickname', 'nickname', trans('quality-control.filterable.instructions.exactly')),
            'rank' => new Filter('0', 'rank', trans('quality-control.filterable.instructions.exactly')),
            'level' => new Filter(['10', '-5', '-0'], 'level', trans('quality-control.filterable.instructions.exactly')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nickname' => trans('quality-control.filterable.presenting.is') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is') . '0',
            'level' => trans('quality-control.filterable.presenting.is') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" in (?, ?) and "nickname" = ? and cast(? as char) like ? and "rank" = ? and "level" in (?, ?, ?)',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input['nickname'][trans('quality-control.filterable.instructions.exactly')] = '1';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotExactlyFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notExactly') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.notExactly') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.notExactly') => '0'],
            'level' => [trans('quality-control.filterable.instructions.notExactly') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', trans('quality-control.filterable.instructions.notExactly')),
            'nickname' => new Filter('filter-nickname', 'nickname', trans('quality-control.filterable.instructions.notExactly')),
            'rank' => new Filter('0', 'rank', trans('quality-control.filterable.instructions.notExactly')),
            'level' => new Filter(['10', '-5', '-0'], 'level', trans('quality-control.filterable.instructions.notExactly')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nickname' => trans('quality-control.filterable.presenting.is not') . 'filter-nickname',
            'rank' => trans('quality-control.filterable.presenting.is not') . '0',
            'level' => trans('quality-control.filterable.presenting.is not') . trans('quality-control.filterable.presenting.any of') . '10, -5, 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $query = [
            'query' => 'select * from "testable_resources" where "name" not in (?, ?) and "nickname" != ? and cast(? as char) like ? and "rank" != ? and "level" not in (?, ?, ?)',
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

        $input['nickname'][trans('quality-control.filterable.instructions.notExactly')] = '1';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.not') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.not') => 'filter-nickname'],
            'rank' => [trans('quality-control.filterable.instructions.not') => '0'],
            'level' => [trans('quality-control.filterable.instructions.not') => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', trans('quality-control.filterable.instructions.not')),
            'nickname' => new Filter('filter-nickname', 'nickname', trans('quality-control.filterable.instructions.not')),
            'rank' => new Filter('0', 'rank', trans('quality-control.filterable.instructions.not')),
            'level' => new Filter(['10', '-5', '0'], 'level', trans('quality-control.filterable.instructions.not')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.not like') . trans('quality-control.filterable.presenting.any of') . 'filter1-name, filter2-name',
            'nickname' => trans('quality-control.filterable.presenting.not like') . 'filter-nickname',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input['nickname'][trans('quality-control.filterable.instructions.not')] = '1';
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testBetweenFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '0']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', trans('quality-control.filterable.instructions.between')),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname', trans('quality-control.filterable.instructions.between')),
            'rank' => new Filter(['0', '50'], 'rank', trans('quality-control.filterable.instructions.between')),
            'level' => new Filter(['-50', '0'], 'level', trans('quality-control.filterable.instructions.between')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => 'between filter1-name and filter2-name',
            'nickname' => 'between filter1-nickname and filter2-nickname',
            'rank' => 'between 0 and 50',
            'level' => 'between -50 and 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" between ? and ? and "nickname" between ? and ? and cast(? as char) like ? and "rank" between ? and ? and "level" between ? and ?',
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

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name'][trans('quality-control.filterable.instructions.between')][0]);
        $input['nickname'][trans('quality-control.filterable.instructions.between')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotBetweenFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', 'filter2-name']],
            'nickname' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', 'filter2-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '50']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '0']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', trans('quality-control.filterable.instructions.notBetween')),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname', trans('quality-control.filterable.instructions.notBetween')),
            'rank' => new Filter(['0', '50'], 'rank', trans('quality-control.filterable.instructions.notBetween')),
            'level' => new Filter(['-50', '0'], 'level', trans('quality-control.filterable.instructions.notBetween')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => 'not between filter1-name and filter2-name',
            'nickname' => 'not between filter1-nickname and filter2-nickname',
            'rank' => 'not between 0 and 50',
            'level' => 'not between -50 and 0',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" not between ? and ? and "nickname" not between ? and ? and cast(? as char) like ? and "rank" not between ? and ? and "level" not between ? and ?',
            'bindings' => [
                'filter1-name',
                'filter2-name',
                'filter1-nickname',
                'filter2-nickname',
                'email',
                '%filter-email%',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name'][trans('quality-control.filterable.instructions.notBetween')][0]);
        $input['nickname'][trans('quality-control.filterable.instructions.notBetween')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testGreaterThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.between') => ['filter1-name', '']],
            'nickname' => [trans('quality-control.filterable.instructions.between') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['-50', '']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', ''], 'name', trans('quality-control.filterable.instructions.between')),
            'nickname' => new Filter(['filter1-nickname', ''], 'nickname', trans('quality-control.filterable.instructions.between')),
            'rank' => new Filter(['0', ''], 'rank', trans('quality-control.filterable.instructions.between')),
            'level' => new Filter(['-50', ''], 'level', trans('quality-control.filterable.instructions.between')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => '>= filter1-name',
            'nickname' => '>= filter1-nickname',
            'rank' => '>= 0',
            'level' => '>= -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" >= ? and "nickname" >= ? and cast(? as char) like ? and "rank" >= ? and "level" >= ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '-50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name'][trans('quality-control.filterable.instructions.between')][0]);
        $input['nickname'][trans('quality-control.filterable.instructions.between')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotGreaterThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-name', '']],
            'nickname' => [trans('quality-control.filterable.instructions.notBetween') => ['filter1-nickname', '']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['0', '']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['-50', '']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['filter1-name', ''], 'name', trans('quality-control.filterable.instructions.notBetween')),
            'nickname' => new Filter(['filter1-nickname', ''], 'nickname', trans('quality-control.filterable.instructions.notBetween')),
            'rank' => new Filter(['0', ''], 'rank', trans('quality-control.filterable.instructions.notBetween')),
            'level' => new Filter(['-50', ''], 'level', trans('quality-control.filterable.instructions.notBetween')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => '< filter1-name',
            'nickname' => '< filter1-nickname',
            'rank' => '< 0',
            'level' => '< -50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" < ? and "nickname" < ? and cast(? as char) like ? and "rank" < ? and "level" < ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '-50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name'][trans('quality-control.filterable.instructions.notBetween')][0]);
        $input['nickname'][trans('quality-control.filterable.instructions.notBetween')][0] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testLessThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-name']],
            'nickname' => [trans('quality-control.filterable.instructions.between') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.between') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.between') => ['', '50']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['', 'filter1-name'], 'name', trans('quality-control.filterable.instructions.between')),
            'nickname' => new Filter(['', 'filter1-nickname'], 'nickname', trans('quality-control.filterable.instructions.between')),
            'rank' => new Filter(['', '0'], 'rank', trans('quality-control.filterable.instructions.between')),
            'level' => new Filter(['', '50'], 'level', trans('quality-control.filterable.instructions.between')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => '<= filter1-name',
            'nickname' => '<= filter1-nickname',
            'rank' => '<= 0',
            'level' => '<= 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" <= ? and "nickname" <= ? and cast(? as char) like ? and "rank" <= ? and "level" <= ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name'][trans('quality-control.filterable.instructions.between')][1]);
        $input['nickname'][trans('quality-control.filterable.instructions.between')][1] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

    public function testNotLessThanFilters()
    {
        $input = [
            'name' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-name']],
            'nickname' => [trans('quality-control.filterable.instructions.notBetween') => ['', 'filter1-nickname']],
            'rank' => [trans('quality-control.filterable.instructions.notBetween') => ['', '0']],
            'level' => [trans('quality-control.filterable.instructions.notBetween') => ['', '50']],
            'email' => 'filter-email',
        ];

        $output = new FilterCollection([
            'name' => new Filter(['', 'filter1-name'], 'name', trans('quality-control.filterable.instructions.notBetween')),
            'nickname' => new Filter(['', 'filter1-nickname'], 'nickname', trans('quality-control.filterable.instructions.notBetween')),
            'rank' => new Filter(['', '0'], 'rank', trans('quality-control.filterable.instructions.notBetween')),
            'level' => new Filter(['', '50'], 'level', trans('quality-control.filterable.instructions.notBetween')),
            'email' => new Filter('filter-email', 'email'),
        ]);

        $presented = [
            'name' => '> filter1-name',
            'nickname' => '> filter1-nickname',
            'rank' => '> 0',
            'level' => '> 50',
            'email' => trans('quality-control.filterable.presenting.like') . 'filter-email',
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "name" > ? and "nickname" > ? and cast(? as char) like ? and "rank" > ? and "level" > ?',
            'bindings' => [
                'filter1-name',
                'filter1-nickname',
                'email',
                '%filter-email%',
                '0',
                '50',
            ],
        ];

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name'][trans('quality-control.filterable.instructions.notBetween')][1]);
        $input['nickname'][trans('quality-control.filterable.instructions.notBetween')][1] = '1';
        $output->remove('name');
        $output->remove('nickname');

        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
        $this->assertEquals($output, $result);
    }

//    public function testApplyScopeFilters()
//    {
//        $input = [
//            'level' => ['applyScope' => 'awesomePeople'],
//        ];
//
//        $output = new FilterCollection([
//            'level' => new Filter('awesomePeople', 'level', trans('quality-control.filterable.instructions.applyScope')),
//        ]);
//
//        $presented = [
//            'level' => 'scope applied',
//        ];
//
//        $query = [
//            'query' => 'select * from "testable_resources" where "level" >= ?',
//            'bindings' => [
//                '50',
//            ],
//        ];
//
//        $result = $this->resource->getValidFilters($this->resource->getFilterValidationRules(), $input);
//        $this->assertEquals($output, $result);
//
//        $q = $this->resource->newQuery();
//        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
//        $q->get();
//        $log = DB::connection('testing')->getQueryLog();
//        $this->assertArraySubset($query, array_pop($log));
//    }
}
