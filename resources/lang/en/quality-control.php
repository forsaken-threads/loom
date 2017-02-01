<?php

return [
    'filterable' => [
        '__or' => '__or',
        '__scope' => '__scope',
        'applied-scope' => 'applied scope',
        'get-default-filters-error' => 'Method getDefaultFilters for :class should return an instance of App\\Loom\\FilterCollection but got :got',
        'expected-property' => 'Expected property name but received :property',
        'expected-filter-or-collection' => 'Expected instance of App\\Loom\\Filter or App\\Loom\\FilterCollection but received :got.',
        'instructions' => [
            'between' => 'between',
            'notBetween' => 'notBetween',
            'exactly' => 'exactly',
            'notExactly' => 'notExactly',
            'not' => 'not',
        ],
        'presenting' => [
            'like' => 'like ',
            'not like' => 'not like ',
            'between' => 'between :0 and :1',
            'not between' => 'not between :0 and :1',
            'any of' => 'any of: ',
            'is' => 'is ',
            'is not' => 'is not ',
        ]
    ],
];