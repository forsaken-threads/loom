<?php

namespace ForsakenThreads\Loom\Tests;

use App\Loom\FilterCriterion;
use App\Loom\FilterCollection;
use DB;
use ForsakenThreads\Loom\Tests\TestHelpers\TestableResource;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class FilterAndConnectableResourcesTest extends TestCase
{
    /** @var TestableResource */
    protected $resource;

    public function setUp()
    {
        parent::setUp();

        Schema::connection('testing')->create('testable_resources', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('testable_connected_first_level_belongs_to_resource_id');
            $table->string('name', 100);
            $table->string('nickname', 20)->unique();
            $table->string('email')->unique();
            $table->tinyInteger('rank');
            $table->tinyInteger('level');
            $table->string('password');
            $table->timestamps();
        });

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
            $table->string('divot');
            $table->timestamps();
        });

        DB::connection('testing')->enableQueryLog();
        $this->resource = new TestableResource();
    }

    public function testConnectableFirstLevelHasOneResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ],
        ];

        $output = new FilterCollection([
            'name' => new FilterCriterion('filter-name', 'name'),
            'TestableConnectedFirstLevelHasOneResource' => new FilterCollection([
                'address' => new FilterCriterion('filter-address', 'address'),
                'city' => new FilterCriterion('filter-city', 'city'),
                'state' => new FilterCriterion('MI', 'state'),
            ]),
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ],
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_one_resources" where "testable_connected_first_level_has_one_resources"."testable_resource_id" = "testable_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'address',
                '%filter-address%',
                'city',
                '%filter-city%',
                'state',
                '%MI%',
            ],
        ];

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testConnectableFirstLevelMultiResources()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ],
            'TestableConnectedFirstLevelHasManyResource' => [trans('quality-control.filterable.__or') => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ]],
        ];

        $output = new FilterCollection([
            'name' => new FilterCriterion('filter-name', 'name'),
            'TestableConnectedFirstLevelHasOneResource' => new FilterCollection([
                'address' => new FilterCriterion('filter-address', 'address'),
                'city' => new FilterCriterion('filter-city', 'city'),
                'state' => new FilterCriterion('MI', 'state'),
            ]),
            'TestableConnectedFirstLevelHasManyResource' => new FilterCollection([
                'address' => new FilterCriterion('filter-address', 'address', true),
                'city' => new FilterCriterion('filter-city', 'city', true),
                'state' => new FilterCriterion('MI', 'state', true),
            ], true)
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ],
            'TestableConnectedFirstLevelHasManyResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_many_resources" where "testable_connected_first_level_has_many_resources"."testable_resource_id" = "testable_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?)) and exists (select * from "testable_connected_first_level_has_one_resources" where "testable_connected_first_level_has_one_resources"."testable_resource_id" = "testable_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'address',
                '%filter-address%',
                'city',
                '%filter-city%',
                'state',
                '%MI%',
                'address',
                '%filter-address%',
                'city',
                '%filter-city%',
                'state',
                '%MI%',
            ],
        ];

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testConnectableSecondLevelResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => 'filter1-address',
                'city' => 'filter1-city',
                'state' => 'MI',
                'TestableConnectedSecondLevelResource' => [
                    'address' => 'filter2-address',
                    'city' => 'filter2-city',
                    'state' => 'FL',
                ],
            ],
        ];

        $output = new FilterCollection([
            'name' => new FilterCriterion('filter-name', 'name'),
            'TestableConnectedFirstLevelHasOneResource' => new FilterCollection([
                'address' => new FilterCriterion('filter1-address', 'address'),
                'city' => new FilterCriterion('filter1-city', 'city'),
                'state' => new FilterCriterion('MI', 'state'),
                'TestableConnectedSecondLevelResource' => new FilterCollection([
                    'address' => new FilterCriterion('filter2-address', 'address'),
                    'city' => new FilterCriterion('filter2-city', 'city'),
                    'state' => new FilterCriterion('FL', 'state'),
                ]),
            ])
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter1-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter1-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
                'TestableConnectedSecondLevelResource' => [
                    'address' => trans('quality-control.filterable.presenting.like') . 'filter2-address',
                    'city' => trans('quality-control.filterable.presenting.like') . 'filter2-city',
                    'state' => trans('quality-control.filterable.presenting.like') . 'FL',
                ],
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_one_resources" where "testable_connected_first_level_has_one_resources"."testable_resource_id" = "testable_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ? and exists (select * from "testable_connected_second_level_resources" where "testable_connected_second_level_resources"."testable_connected_first_level_has_one_resource_id" = "testable_connected_first_level_has_one_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?))',
            'bindings' => [
                'name',
                '%filter-name%',
                'address',
                '%filter1-address%',
                'city',
                '%filter1-city%',
                'state',
                '%MI%',
                'address',
                '%filter2-address%',
                'city',
                '%filter2-city%',
                'state',
                '%FL%',
            ],
        ];

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testConnectableFirstLevelHasManyResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasManyResource' => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ],
        ];

        $output = new FilterCollection([
            'name' => new FilterCriterion('filter-name', 'name'),
            'TestableConnectedFirstLevelHasManyResource' => new FilterCollection([
                'address' => new FilterCriterion('filter-address', 'address'),
                'city' => new FilterCriterion('filter-city', 'city'),
                'state' => new FilterCriterion('MI', 'state'),
            ])
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelHasManyResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_many_resources" where "testable_connected_first_level_has_many_resources"."testable_resource_id" = "testable_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'address',
                '%filter-address%',
                'city',
                '%filter-city%',
                'state',
                '%MI%',
            ],
        ];

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testConnectableFirstLevelBelongsToResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelBelongsToResource' => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ],
        ];

        $output = new FilterCollection([
            'name' => new FilterCriterion('filter-name', 'name'),
            'TestableConnectedFirstLevelBelongsToResource' => new FilterCollection([
                'address' => new FilterCriterion('filter-address', 'address'),
                'city' => new FilterCriterion('filter-city', 'city'),
                'state' => new FilterCriterion('MI', 'state'),
            ])
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelBelongsToResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_belongs_to_resources" where "testable_resources"."testable_connected_first_level_belongs_to_resource_id" = "testable_connected_first_level_belongs_to_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'address',
                '%filter-address%',
                'city',
                '%filter-city%',
                'state',
                '%MI%',
            ],
        ];

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }

    public function testConnectableFirstLevelBelongsToManyResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelBelongsToManyResource' => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
//                '__pivot' => [
//                    'divot' => 'filter-divot'
//                ]
            ],
        ];

        $output = new FilterCollection([
            'name' => new FilterCriterion('filter-name', 'name'),
            'TestableConnectedFirstLevelBelongsToManyResource' => new FilterCollection([
                'address' => new FilterCriterion('filter-address', 'address'),
                'city' => new FilterCriterion('filter-city', 'city'),
                'state' => new FilterCriterion('MI', 'state'),
//                'divot' => new FilterCriterion('filter-divot', 'testable_connected_first_level_belongs_to_many_resource_testable_resource.divot')
            ])
        ]);

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelBelongsToManyResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_belongs_to_many_resources" inner join "testable_connected_first_level_belongs_to_many_resource_testable_resource" on "testable_connected_first_level_belongs_to_many_resources"."id" = "testable_connected_first_level_belongs_to_many_resource_testable_resource"."testable_connected_first_level_belongs_to_many_resource_id" where "testable_connected_first_level_belongs_to_many_resource_testable_resource"."testable_resource_id" = "testable_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?)',
            'bindings' => [
                'name',
                '%filter-name%',
                'address',
                '%filter-address%',
                'city',
                '%filter-city%',
                'state',
                '%MI%',
            ],
        ];

        $result = $this->resource->getValidFilters($input);
        $this->assertEquals($output, $result);

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->applyFilters($input, $q));
        $q->get();
        $log = DB::connection('testing')->getQueryLog();
        $this->assertArraySubset($query, array_pop($log));
    }
}
