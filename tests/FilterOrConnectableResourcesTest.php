<?php

namespace ForsakenThreads\Loom\Tests;

class FilterOrConnectableResourcesTest extends TestCase
{
    protected $withConnectedResources = true;

    public function testConnectableFirstLevelHasOneResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [trans('quality-control.filterable.__or') => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ]],
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_one_resources" where "testable_connected_first_level_has_one_resources"."testable_resource_id" = "testable_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?))',
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

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testConnectableFirstLevelMultiResources()
    {
        $input = [trans('quality-control.filterable.__or') => [
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
        ]];

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
            'query' => 'select * from "testable_resources" where cast(? as char) like ? or exists (select * from "testable_connected_first_level_has_many_resources" where "testable_connected_first_level_has_many_resources"."testable_resource_id" = "testable_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?)) or exists (select * from "testable_connected_first_level_has_one_resources" where "testable_connected_first_level_has_one_resources"."testable_resource_id" = "testable_resources"."id" and cast(? as char) like ? and cast(? as char) like ? and cast(? as char) like ?)',
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

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testConnectableSecondLevelResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasOneResource' => [trans('quality-control.filterable.__or') => [
                'address' => 'filter1-address',
                'city' => 'filter1-city',
                'state' => 'MI',
                'TestableConnectedSecondLevelResource' => [trans('quality-control.filterable.__or') => [
                    'address' => 'filter2-address',
                    'city' => 'filter2-city',
                    'state' => 'FL',
                ]],
            ]],
        ];

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
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_one_resources" where "testable_connected_first_level_has_one_resources"."testable_resource_id" = "testable_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ? or exists (select * from "testable_connected_second_level_resources" where "testable_connected_second_level_resources"."testable_connected_first_level_has_one_resource_id" = "testable_connected_first_level_has_one_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?))))',
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

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testConnectableFirstLevelHasManyResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelHasManyResource' => [trans('quality-control.filterable.__or') => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ]],
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelHasManyResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_has_many_resources" where "testable_connected_first_level_has_many_resources"."testable_resource_id" = "testable_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?))',
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

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testConnectableFirstLevelBelongsToResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelBelongsToResource' => [trans('quality-control.filterable.__or') => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ]],
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelBelongsToResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_belongs_to_resources" where "testable_resources"."testable_connected_first_level_belongs_to_resource_id" = "testable_connected_first_level_belongs_to_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?))',
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

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testConnectableFirstLevelBelongsToManyResource()
    {
        $input = [
            'name' => 'filter-name',
            'TestableConnectedFirstLevelBelongsToManyResource' => [trans('quality-control.filterable.__or') => [
                'address' => 'filter-address',
                'city' => 'filter-city',
                'state' => 'MI',
            ]],
        ];

        $presented = [
            'name' => trans('quality-control.filterable.presenting.like') . 'filter-name',
            'TestableConnectedFirstLevelBelongsToManyResource' => [
                'address' => trans('quality-control.filterable.presenting.like') . 'filter-address',
                'city' => trans('quality-control.filterable.presenting.like') . 'filter-city',
                'state' => trans('quality-control.filterable.presenting.like') . 'MI',
            ]
        ];

        $query = [
            'query' => 'select * from "testable_resources" where cast(? as char) like ? and exists (select * from "testable_connected_first_level_belongs_to_many_resources" inner join "testable_connected_first_level_belongs_to_many_resource_testable_resource" on "testable_connected_first_level_belongs_to_many_resources"."id" = "testable_connected_first_level_belongs_to_many_resource_testable_resource"."testable_connected_first_level_belongs_to_many_resource_id" where "testable_connected_first_level_belongs_to_many_resource_testable_resource"."testable_resource_id" = "testable_resources"."id" and (cast(? as char) like ? or cast(? as char) like ? or cast(? as char) like ?))',
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

        $q = $this->resource->newQuery();
        $this->assertEquals($presented, $this->resource->filter($input, $q));
        $q->get();
        $this->assertQueryEquals($query);
    }
}
