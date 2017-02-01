<?php

return [
    'filterable' => [
        '__scope' => '__scope',
        'applied-scope' => 'applied scope',
        'get-default-filters-error' => 'Method getDefaultFilters for :class should return an instance of App\\Loom\\FilterCollection',
        'expected-property' => 'Expected property name but received :property',
        'expected-filter' => 'Expected instance of App\\Loom\\Filter but received :got.',
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