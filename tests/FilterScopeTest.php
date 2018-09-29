<?php

namespace ForsakenThreads\Loom\Tests;

use App\Loom\FilterScope;

class FilterScopeTest extends TestCase
{
    public function testSimpleScope()
    {
        $query = [
            'query' => 'select * from "testable_resources" where "level" > ?',
            'bindings' => [
                '75',
            ],
        ];

        $q = $this->resource->newQuery();
        $filterScope = new FilterScope('awesomePeople');
        $filterScope->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testScopeWithArg()
    {
        $input = [
            'level' => 22,
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "level" > ?',
            'bindings' => [
                '22',
            ],
        ];

        $q = $this->resource->newQuery();
        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope
            ->withArguments('level')
            ->setValidationRules([
                'level' => 'required|digits_between:1,2',
            ]);

        $this->assertTrue($filterScope->validateAndSetInput($input));

        $filterScope->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testScopeWithMissingRequiredArg()
    {
        $input = [];

        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope
            ->withArguments('level')
            ->setValidationRules([
                'level' => 'required|numeric|between:0,100',
            ]);

        $this->assertFalse($filterScope->validateAndSetInput($input));
    }

    public function testScopeWithDefaultedMissingRequiredArg()
    {
        $input = [];

        $query = [
            'query' => 'select * from "testable_resources" where "level" > ?',
            'bindings' => [
                '18',
            ],
        ];

        $q = $this->resource->newQuery();
        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope
            ->withArguments('level')
            ->setArgumentDefault('level', '18')
            ->setValidationRules([
                'level' => 'numeric|between:0,100',
            ]);

        $this->assertTrue($filterScope->validateAndSetInput($input));

        $filterScope->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testScopeWithMultiArgs()
    {
        $input = [
            'level' => 22,
            'rank' => -10,
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "level" > ? and "rank" > ? and "role" = ?',
            'bindings' => [
                '22',
                '-10',
                'Admin'
            ],
        ];

        $q = $this->resource->newQuery();
        $filterScope = new FilterScope('awesomeishRankedPeople');
        $filterScope
            ->withArguments('level', 'rank')
            ->setValidationRules([
                'level' => 'numeric|between:0,100',
                'rank' => 'numeric|between:-100,100',
            ]);

        $this->assertTrue($filterScope->validateAndSetInput($input));

        $filterScope->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testScopeWithMultiArgsAndDefaultValue()
    {
        $input = [
            'level' => 22,
            'rank' => -10,
        ];

        $query = [
            'query' => 'select * from "testable_resources" where "level" > ? and "rank" > ? and "role" = ?',
            'bindings' => [
                '22',
                '-10',
                'User',
            ],
        ];

        $q = $this->resource->newQuery();
        $filterScope = new FilterScope('awesomeishRankedPeople');
        $filterScope
            ->withArguments('level', 'rank', 'role')
            ->setValidationRules([
                'level' => 'numeric|between:0,100',
                'rank' => 'numeric|between:-100,100',
            ])
            ->setArgumentDefault('role', 'User');

        $this->assertTrue($filterScope->validateAndSetInput($input));

        $filterScope->applyFilter($q);
        $q->get();
        $this->assertQueryEquals($query);
    }

    public function testDefaultPresentation()
    {
        $scopeName = 'awesomePeople';
        $filterScope = new FilterScope($scopeName);
        $this->assertEquals($scopeName, $filterScope->presentFilter());
    }

    public function testStringPresentation()
    {
        $presentation = 'only awesome people';
        $filterScope = new FilterScope('awesomePeople');
        $filterScope->setPresentation($presentation);
        $this->assertEquals($presentation, $filterScope->presentFilter());
    }

    public function testSimpleCallablePresentation()
    {
        $filterScope = new FilterScope('awesomePeople');
        $filterScope->setPresentation(function() {
            return 'just the awesome people';
        });
        $this->assertEquals('just the awesome people', $filterScope->presentFilter());
    }

    public function testCallablePresentationWithInput()
    {
        $input = [
            'level' => 33,
        ];

        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope
            ->withArguments('level')
            ->setValidationRules([
                'level' => 'required|digits_between:1,2',
            ])
            ->setPresentation(function($input) {
                return 'only awesome people with level higher than ' . $input['level'];
            })
            ->validateAndSetInput($input);
        $this->assertEquals('only awesome people with level higher than 33', $filterScope->presentFilter());
    }

    public function testCallablePresentationWithInputAndDefaultValue()
    {
        $input = [
            'level' => 33,
        ];

        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope
            ->withArguments('level', 'rank')
            ->setValidationRules([
                'level' => 'required|digits_between:1,2',
            ])
            ->setArgumentDefault('rank', 25)
            ->setPresentation(function($input) {
                return 'only awesome people with level higher than ' . $input['level'] . ' and at least rank ' . $input['rank'];
            })
            ->validateAndSetInput($input);
        $this->assertEquals('only awesome people with level higher than 33 and at least rank 25', $filterScope->presentFilter());
    }
}
