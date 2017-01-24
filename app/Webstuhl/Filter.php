<?php

namespace App\Webstuhl;

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
     * @param $instruction
     */
    public function __construct($validFilter, $property, $instruction = null)
    {
        $this->filter = $validFilter;
        $this->property = $property;
        $this->comparator = $this->determineComparator($property, $instruction);
    }

    /**
     * @param Builder $query
     */
    public function applyFilter($query)
    {
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
        if (is_array($this->comparator)) {
            foreach ($this->comparator as $key => $args) {
                switch ($key) {
                    case 'equals':
                        $query->where($this->property, $args);
                        break;
                    case 'in':
                        $query->whereIn($this->property, $args);
                        break;
                    case 'where':
                        $query->where($args[0]);
                        break;
                    case 'whereHas':
                        $query->whereHas($args[0], $args[1]);
                        break;
                }
            }
            return;
        }
        switch ($this->comparator) {
            case 'equals':
                $method = is(array($this->filter)) ? 'whereIn' : 'where';
                $query->$method($this->property, $this->filter);
                break;
            case 'not equals':
                if (is(array($this->filter))) {
                    $query->whereNotIn($this->property, $this->filter);
                } else {
                    $query->where($this->property, '!=', $this->filter);
                }
                break;
            case 'between':
                $query->whereBetween($this->property, $this->filter);
                break;
            case 'not between':
                $query->whereNotBetween($this->property, $this->filter);
                break;
            case '<':
                // pass-through
            case '>=':
                $query->where($this->property, $this->comparator, $this->filter[0]);
                break;
            case '>':
                // pass-through
            case '<=':
                $query->where($this->property, $this->comparator, $this->filter[1]);
                break;
            case 'not like':
                $patterns = is_array($this->filter) ? $this->filter : (array) $this->filter;
                foreach ($patterns as $pattern) {
                    $query->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                }
                break;
            case 'like':
            default:
                $patterns = is_array($this->filter) ? $this->filter : (array) $this->filter;
                $query->where(function($q) use ($patterns) {
                    /** @var Builder $q */
                    foreach ($patterns as $pattern) {
                        $q->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                    }
                });
        }
    }

    public function presentFilter()
    {
        switch ($this->comparator) {
            case 'between':
                // pass-through
            case 'not between':
                return $this->comparator . ' ' . implode(' and ', $this->filter);
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
                return 'not like ' . (is_array($this->filter) ? 'any of: ' . implode(', ', $this->filter) : $this->filter);
            case 'like':
                return 'like ' . (is_array($this->filter) ? 'any of: ' . implode(', ', $this->filter) : $this->filter);

            case 'equals':
                return 'is ' . (is_array($this->filter) ? 'any of: ' . implode(', ', $this->filter) : $this->filter);
            case 'not equals':
                return 'is not ' . (is_array($this->filter) ? 'any of: ' . implode(', ', $this->filter) : $this->filter);

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
        if (method_exists($this, 'filter' . studly_case($property))) {
            return call_user_func_array([$this, 'filter' . studly_case($property)], [$this->filter]);
        }

        if ($instruction != 'between' && $instruction != 'notBetween') {
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