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

    /** @var bool */
    protected $withConnectedResources;

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

    public function setUp($withTestResources = true)
    {
        parent::setUp();
        if ($withTestResources) {
            DB::connection('testing')->enableQueryLog();

            Schema::connection('testing')->create('testable_resources', function (Blueprint $table) {
                $table->string('id')->index();
                $table->string('testable_connected_first_level_belongs_to_resource_id');
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

            if ($this->withConnectedResources) {
                Schema::connection('testing')->create('testable_connected_first_level_has_one_resources', function (Blueprint $table) {
                    $table->string('id')->index();
                    $table->string('testable_resource_id');
                    $table->string('address', 100);
                    $table->string('city', 20);
                    $table->char('state', 2);
                });

                Schema::connection('testing')->create('testable_connected_second_level_resources', function (Blueprint $table) {
                    $table->string('id')->index();
                    $table->string('testable_connected_first_level_has_one_resource_id');
                    $table->string('address', 100);
                    $table->string('city', 20);
                    $table->char('state', 2);
                });

                Schema::connection('testing')->create('testable_connected_first_level_has_many_resources', function (Blueprint $table) {
                    $table->string('id')->index();
                    $table->string('testable_resource_id');
                    $table->string('address', 100);
                    $table->string('city', 20);
                    $table->char('state', 2);
                });

                Schema::connection('testing')->create('testable_connected_first_level_belongs_to_resources', function (Blueprint $table) {
                    $table->string('id')->index();
                    $table->string('address', 100);
                    $table->string('city', 20);
                    $table->char('state', 2);
                });

                Schema::connection('testing')->create('testable_connected_first_level_belongs_to_many_resources', function (Blueprint $table) {
                    $table->string('id')->index();
                    $table->string('address', 100);
                    $table->string('city', 20);
                    $table->char('state', 2);
                });

                Schema::connection('testing')->create('testable_connected_first_level_belongs_to_many_resource_testable_resource', function(Blueprint $table) {
                    $table->increments('id');
                    $table->string('testable_connected_first_level_belongs_to_many_resource_id');
                    $table->string('testable_resource_id');
                    $table->string('pivot_field');
                    $table->timestamps();
                });
            }
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
