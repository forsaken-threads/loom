<?php

namespace ForsakenThreads\Webstuhl\Tests;

use App\Webstuhl\Filter;
use DB;
use ForsakenThreads\Webstuhl\Tests\TestHelpers\TestableResource;
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

        $output = [
            'name' => new Filter('filter-name', 'name'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname'),
            'rank' => new Filter('0', 'rank'),
            'level' => new Filter(['10', '-5', '0'], 'level'),
            'email' => new Filter('filter-email', 'email')
        ];

        $presented = [
            'name' => 'like filter-name',
            'nickname' => 'like any of: filter1-nickname, filter2-nickname',
            'rank' => 'like 0',
            'level' => 'like any of: 10, -5, 0',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input['nickname'][0] = 'f';
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testExactlyFilters()
    {
        $input = [
            'name' => ['exactly' => ['filter1-name', 'filter2-name']],
            'nickname' => ['exactly' => 'filter-nickname'],
            'rank' => ['exactly' => '0'],
            'level' => ['exactly' => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', 'exactly'),
            'nickname' => new Filter('filter-nickname', 'nickname', 'exactly'),
            'rank' => new Filter('0', 'rank', 'exactly'),
            'level' => new Filter(['10', '-5', '-0'], 'level', 'exactly'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => 'is any of: filter1-name, filter2-name',
            'nickname' => 'is filter-nickname',
            'rank' => 'is 0',
            'level' => 'is any of: 10, -5, 0',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input['nickname']['exactly'] = '1';
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testNotExactlyFilters()
    {
        $input = [
            'name' => ['notExactly' => ['filter1-name', 'filter2-name']],
            'nickname' => ['notExactly' => 'filter-nickname'],
            'rank' => ['notExactly' => '0'],
            'level' => ['notExactly' => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', 'notExactly'),
            'nickname' => new Filter('filter-nickname', 'nickname', 'notExactly'),
            'rank' => new Filter('0', 'rank', 'notExactly'),
            'level' => new Filter(['10', '-5', '-0'], 'level', 'notExactly'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => 'is not any of: filter1-name, filter2-name',
            'nickname' => 'is not filter-nickname',
            'rank' => 'is not 0',
            'level' => 'is not any of: 10, -5, 0',
            'email' => 'like filter-email',
        ];

        $result = $this->resource->getValidFilters($input);
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

        $input['nickname']['notExactly'] = '1';
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testNotFilters()
    {
        $input = [
            'name' => ['not' => ['filter1-name', 'filter2-name']],
            'nickname' => ['not' => 'filter-nickname'],
            'rank' => ['not' => '0'],
            'level' => ['not' => ['10', '-5', '0']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', 'not'),
            'nickname' => new Filter('filter-nickname', 'nickname', 'not'),
            'rank' => new Filter('0', 'rank', 'not'),
            'level' => new Filter(['10', '-5', '0'], 'level', 'not'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => 'not like any of: filter1-name, filter2-name',
            'nickname' => 'not like filter-nickname',
            'rank' => 'not like 0',
            'level' => 'not like any of: 10, -5, 0',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        $input['nickname']['not'] = '1';
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testBetweenFilters()
    {
        $input = [
            'name' => ['between' => ['filter1-name', 'filter2-name']],
            'nickname' => ['between' => ['filter1-nickname', 'filter2-nickname']],
            'rank' => ['between' => ['0', '50']],
            'level' => ['between' => ['-50', '0']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', 'between'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname', 'between'),
            'rank' => new Filter(['0', '50'], 'rank', 'between'),
            'level' => new Filter(['-50', '0'], 'level', 'between'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => 'between filter1-name and filter2-name',
            'nickname' => 'between filter1-nickname and filter2-nickname',
            'rank' => 'between 0 and 50',
            'level' => 'between -50 and 0',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name']['between'][0]);
        $input['nickname']['between'][0] = '1';
        unset($output['name']);
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testNotBetweenFilters()
    {
        $input = [
            'name' => ['notBetween' => ['filter1-name', 'filter2-name']],
            'nickname' => ['notBetween' => ['filter1-nickname', 'filter2-nickname']],
            'rank' => ['notBetween' => ['0', '50']],
            'level' => ['notBetween' => ['-50', '0']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', 'filter2-name'], 'name', 'notBetween'),
            'nickname' => new Filter(['filter1-nickname', 'filter2-nickname'], 'nickname', 'notBetween'),
            'rank' => new Filter(['0', '50'], 'rank', 'notBetween'),
            'level' => new Filter(['-50', '0'], 'level', 'notBetween'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => 'not between filter1-name and filter2-name',
            'nickname' => 'not between filter1-nickname and filter2-nickname',
            'rank' => 'not between 0 and 50',
            'level' => 'not between -50 and 0',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name']['notBetween'][0]);
        $input['nickname']['notBetween'][0] = '1';
        unset($output['name']);
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testGreaterThanFilters()
    {
        $input = [
            'name' => ['between' => ['filter1-name', '']],
            'nickname' => ['between' => ['filter1-nickname', '']],
            'rank' => ['between' => ['0', '']],
            'level' => ['between' => ['-50', '']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', ''], 'name', 'between'),
            'nickname' => new Filter(['filter1-nickname', ''], 'nickname', 'between'),
            'rank' => new Filter(['0', ''], 'rank', 'between'),
            'level' => new Filter(['-50', ''], 'level', 'between'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => '>= filter1-name',
            'nickname' => '>= filter1-nickname',
            'rank' => '>= 0',
            'level' => '>= -50',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name']['between'][0]);
        $input['nickname']['between'][0] = '1';
        unset($output['name']);
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testNotGreaterThanFilters()
    {
        $input = [
            'name' => ['notBetween' => ['filter1-name', '']],
            'nickname' => ['notBetween' => ['filter1-nickname', '']],
            'rank' => ['notBetween' => ['0', '']],
            'level' => ['notBetween' => ['-50', '']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['filter1-name', ''], 'name', 'notBetween'),
            'nickname' => new Filter(['filter1-nickname', ''], 'nickname', 'notBetween'),
            'rank' => new Filter(['0', ''], 'rank', 'notBetween'),
            'level' => new Filter(['-50', ''], 'level', 'notBetween'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => '< filter1-name',
            'nickname' => '< filter1-nickname',
            'rank' => '< 0',
            'level' => '< -50',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name']['notBetween'][0]);
        $input['nickname']['notBetween'][0] = '1';
        unset($output['name']);
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testLessThanFilters()
    {
        $input = [
            'name' => ['between' => ['', 'filter1-name']],
            'nickname' => ['between' => ['', 'filter1-nickname']],
            'rank' => ['between' => ['', '0']],
            'level' => ['between' => ['', '50']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['', 'filter1-name'], 'name', 'between'),
            'nickname' => new Filter(['', 'filter1-nickname'], 'nickname', 'between'),
            'rank' => new Filter(['', '0'], 'rank', 'between'),
            'level' => new Filter(['', '50'], 'level', 'between'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => '<= filter1-name',
            'nickname' => '<= filter1-nickname',
            'rank' => '<= 0',
            'level' => '<= 50',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name']['between'][1]);
        $input['nickname']['between'][1] = '1';
        unset($output['name']);
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }

    public function testNotLessThanFilters()
    {
        $input = [
            'name' => ['notBetween' => ['', 'filter1-name']],
            'nickname' => ['notBetween' => ['', 'filter1-nickname']],
            'rank' => ['notBetween' => ['', '0']],
            'level' => ['notBetween' => ['', '50']],
            'email' => 'filter-email',
        ];

        $output = [
            'name' => new Filter(['', 'filter1-name'], 'name', 'notBetween'),
            'nickname' => new Filter(['', 'filter1-nickname'], 'nickname', 'notBetween'),
            'rank' => new Filter(['', '0'], 'rank', 'notBetween'),
            'level' => new Filter(['', '50'], 'level', 'notBetween'),
            'email' => new Filter('filter-email', 'email'),
        ];

        $presented = [
            'name' => '> filter1-name',
            'nickname' => '> filter1-nickname',
            'rank' => '> 0',
            'level' => '> 50',
            'email' => 'like filter-email',
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

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));

        unset($input['name']['notBetween'][1]);
        $input['nickname']['notBetween'][1] = '1';
        unset($output['name']);
        unset($output['nickname']);

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);
    }
}
