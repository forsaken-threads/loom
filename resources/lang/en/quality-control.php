<?php

return [
    'filterable' => [
        '__or' => '__or',
        '__scope' => '__scope',
        '__sort' => '__sort',
        'applied-scope' => 'applied scope',
        'get-default-filters-error' => 'Method getDefaultFilters for :class should return an instance of App\\Loom\\FilterCollection but got :got',
        'expected-property' => 'Expected property name but received :property',
        'expected-filter-or-collection' => 'Expected instance of App\\Loom\\Filter or App\\Loom\\FilterCollection but received :got.',
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