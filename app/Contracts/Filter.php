<?php

namespace App\Contracts;

interface Filter
{

    /**
     * Filter constructor.
     * @param $filter
     * @param $property
     * @param null $instruction
     */
    function __construct($filter, $property, $instruction = null);

    /**
     * @param $query
     * @param $orTogether
     * @return void
     */
    function applyFilter($query, $orTogether);

    /**
     * @return string
     */
    function presentFilter();
}