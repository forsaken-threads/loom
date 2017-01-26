<?php

namespace ForsakenThreads\Webstuhl\Tests;

use DB;
use ForsakenThreads\Webstuhl\Tests\TestHelpers\TestableResource;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class SortableTest extends TestCase
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

    public function testExample()
    {
        $input = [

        ]
        $result = $this->resource->getValidFilters();
    }
}
