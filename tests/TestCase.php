<?php

namespace ForsakenThreads\Loom\Tests;

use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Schema;

abstract class TestCase extends LaravelTestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /** @var TestableResource */
    protected $resource;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp($withDatabase = true)
    {
        parent::setUp();
        if ($withDatabase) {
            DB::connection('testing')->enableQueryLog();

            Schema::connection('testing')->create('testable_resources', function (Blueprint $table) {
                $table->string('id')->index();
                $table->string('name', 100);
                $table->string('nick_name', 20)->unique();
                $table->string('email')->unique();
                $table->tinyInteger('rank');
                $table->tinyInteger('level');
                $table->string('password');
                $table->timestamps();
            });

            Schema::connection('testing')->create('testable_resource_threes', function (Blueprint $table) {
                $table->string('id')->index();
                $table->string('name', 100);
                $table->string('nick_name', 20)->unique();
                $table->string('email')->unique();
                $table->tinyInteger('rank');
                $table->tinyInteger('level');
                $table->string('password');
                $table->timestamps();
            });

            $this->resource = new TestableResource();
        }
    }

    protected function assertQueryEquals($query)
    {
        $log = DB::connection('testing')->getQueryLog();
        $log = array_pop($log);
        unset($log['time']);
        $this->assertEquals($query, $log);
    }
}
