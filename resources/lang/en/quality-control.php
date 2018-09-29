<?php

return [
    'filterable' => [
        '__not' => '__not',
        '__or' => '__or',
        '__pivot' => '__pivot',
        '__scope' => '__scope',
        '__sort' => '__sort',

        'applied-scope' => 'applied scope',

        'expected-filter-or-collection' => 'Expected instance of App\\Loom\\Filter or App\\Loom\\FilterCollection but received :got.',
        'expected-property' => 'Expected property name but received :property',
        'get-default-filters-error' => 'Method getDefaultFilters for :class should return an instance of App\\Loom\\FilterCollection but got :got.',
        'primary-not-found' => 'While collecting filters for a pivot table, the primary resource could not be determined. The secondary resource is :secondary.',
        'property-connectable-resource-collision' => 'The following collisions between properties and connectable resources have been detected: :collisions.',

        'instructions' => [
            'asString' => 'asString',
            'between' => 'between',
            'notBetween' => 'notBetween',
            'exactly' => 'exactly',
            'notExactly' => 'notExactly',
            'not' => 'not',
        ],
        'presenting' => [
            'any of' => 'any of: ',
            'as a string' => ' as a string',
            'asc' => ' ascending',
            'between' => 'between :0 and :1',
            'desc' => ' descending',
            'is' => 'is ',
            'is not' => 'is not ',
            'like' => 'like ',
            'not like' => 'not like ',
            'not between' => 'not between :0 and :1',
            'order by' => 'order by ',
        ]
    ],
];