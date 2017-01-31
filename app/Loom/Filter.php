<?php

namespace App\Loom;

use Illuminate\Database\Eloquent\Builder;

class Filter
{
    protected $comparator;

    protected $filter;

    protected $property;

    /**
     * Filter constructor.
     * @param $validFilter
     * @param $property
     * @param null $instruction
     */
    public function __construct($validFilter, $property, $instruction = null)
    {
        $this->filter = $validFilter;
        $this->property = $property;
        $this->comparator = $this->determineComparator($property, $instruction);
    }

    /**
     * @param Builder $query
     * @param bool $orTogether
     */
    public function applyFilter($query, $orTogether)
    {
        $or = $orTogether ? 'Or' : '';
//        if ($property == '__auto_eager_links') {
//            foreach ($filter as $eager_model => $eager_filters) {
//                $query->whereHas($eager_model, function($q) use ($eager_filters) {
//                    foreach ($eager_filters as $eager_property => $eager_filter) {
//                        $this->applyFilter($eager_filter, $eager_property, $q);
//                    }
//                });
//            }
//            return;
//        }
//        // This is for when custom filters are used
//        if (is_array($this->comparator)) {
//            foreach ($this->comparator as $key => $args) {
//                switch ($key) {
//                    case 'equals':
//                        $query->where($this->property, $args);
//                        break;
//                    case 'in':
//                        $query->whereIn($this->property, $args);
//                        break;
//                    case 'where':
//                        $query->where($args[0]);
//                        break;
//                    case 'whereHas':
//                        $query->whereHas($args[0], $args[1]);
//                        break;
//                }
//            }
//            return;
//        }
        switch ($this->comparator) {
            case 'apply scope':
                $method = camel_case($or . 'Where');
                $query->$method($this->filter);
                break;
            case 'equals':
                $method = camel_case($or . (is_array($this->filter) ? 'WhereIn' : 'Where'));
                $query->$method($this->property, $this->filter);
                break;
            case 'not equals':
                if (is_array($this->filter)) {
                    $method = camel_case($or . 'WhereNotIn');
                    $query->$method($this->property, $this->filter);
                } else {
                    $method = camel_case($or . 'Where');
                    $query->$method($this->property, '!=', $this->filter);
                }
                break;
            case 'between':
                $method = camel_case($or . 'WhereBetween');
                $query->$method($this->property, $this->filter);
                break;
            case 'not between':
                $method = camel_case($or . 'WhereNotBetween');
                $query->$method($this->property, $this->filter);
                break;
            case '<':
                // pass-through
            case '>=':
                $method = camel_case($or . 'Where');
                $query->$method($this->property, $this->comparator, $this->filter[0]);
                break;
            case '>':
                // pass-through
            case '<=':
                $method = camel_case($or . 'Where');
                $query->$method($this->property, $this->comparator, $this->filter[1]);
                break;
            case 'not like':
                if (!is_array($this->filter)) {
                    $method = camel_case($or . 'WhereRaw');
                    $query->$method('cast(? as char) not like ?', [$this->property, '%' . $this->filter . '%']);
                } else {
                    if (!$or) {
                        foreach ($this->filter as $pattern) {
                            $query->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                        }
                    } else {
                        $query->orWhere(function ($q) {
                            /** @var Builder $q */
                            foreach ($this->filter as $pattern) {
                                $q->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                            }
                        });
                    }
                }
                break;
            case 'like':
            default:
                if (!is_array($this->filter)) {
                    $method = camel_case($or . 'WhereRaw');
                    $query->$method('cast(? as char) like ?', [$this->property, '%' . $this->filter . '%']);
                } else {
                    if ($or) {
                        foreach ($this->filter as $pattern) {
                            $query->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                        }
                    } else {
                        $query->where(function ($q) {
                            /** @var Builder $q */
                            foreach ($this->filter as $pattern) {
                                $q->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                            }
                        });
                    }
                }
        }
    }

    public function presentFilter()
    {
        switch ($this->comparator) {
            case 'apply scope':
                return 'scope applied';
            case 'between':
                // pass-through
            case 'not between':
                return trans('quality-control.filterable.presenting.' . $this->comparator, $this->filter);
            case '<':
                // pass-through
            case '>=':
                return $this->comparator . ' ' . $this->filter[0];
                break;
            case '>':
                // pass-through
            case '<=':
                return $this->comparator . ' ' . $this->filter[1];
                break;

            case 'not like':
                return trans('quality-control.filterable.presenting.not like') . (is_array($this->filter) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->filter) : $this->filter);
            case 'like':
                return trans('quality-control.filterable.presenting.like') . (is_array($this->filter) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->filter) : $this->filter);

            case 'equals':
                return trans('quality-control.filterable.presenting.is') . (is_array($this->filter) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->filter) : $this->filter);
            case 'not equals':
                return trans('quality-control.filterable.presenting.is not') . (is_array($this->filter) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->filter) : $this->filter);

            default:
                return $this->filter;
        }
    }

    /**
     * @param $property
     * @param $instruction
     * @return mixed|string
     */
    protected function determineComparator($property, $instruction)
    {
        if ($instruction != 'between' && $instruction != 'notBetween') {
            if ($instruction == 'applyScope') {
                return 'apply scope';
            }
            if ($instruction == 'exactly') {
                return 'equals';
            }
            if ($instruction == 'notExactly') {
                return 'not equals';
            }
            return $instruction == 'not' ? 'not like' : 'like';
//            return preg_match('/id_exists/', implode('|', $rules)) ? $negated . 'equals' : $negated . 'like';
        }

        $not = $instruction == 'notBetween' ? 'not ' : '';
        if (!nonZeroEmpty($this->filter[0]) && !nonZeroEmpty($this->filter[1])) {
            return $not . 'between';
        } elseif (!nonZeroEmpty($this->filter[0])) {
            return $not ? '<' : '>=';
        } else {
            return $not ? '>' : '<=';
        }
    }
}