<?php

namespace App\Traits;

class Sortable
{
    public function applySorts($givenSorts, &$query)
    {
        $givenSorts = new Fluent($givenSorts);
//        list($sorts, $belongs_to_many_sorts) = $this->applySorts($input, $query);

        $filters = $givenSorts->get('filters');
        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }
        if (!$filters || !is_array($filters)) {
            if (empty($this->defaultFilter) && !method_exists($this, 'setDefaultFilter')) {
                return [
                    'filters' => null,
//                'sorts' => $sorts,
//                'belongs_to_many_sorts' => $belongs_to_many_sorts
                ];
            }
            if (!empty($this->defaultFilter)) {
                $filters = $this->defaultFilter;
            } elseif (method_exists($this, 'setDefaultFilter')) {
                $filters = $this->setDefaultFilter();
            }
        } else {
            $filters = $this->getValidFilters($filters);
        }
        foreach ($filters as $property => $filter) {
            $this->applyFilter($filter, $property, $query);
        }
        return [
            'filters' => $this->presentFilters($filters),
//            'sorts' => $sorts,
//            'belongs_to_many_sorts' => $belongs_to_many_sorts
        ];
    }
}