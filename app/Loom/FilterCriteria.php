<?php

namespace App\Loom;

use App\Contracts\FilterContract;
use Illuminate\Database\Eloquent\Builder;

class FilterCriteria implements FilterContract
{

    /**
     * @var string
     */
    protected $comparator;

    /**
     * @var string|array
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $orTogether;

    /**
     * @var string
     */
    protected $property;

    /**
     * Filter constructor.
     *
     * @param $criteria
     * @param $property
     * @param bool $orTogether
     * @param null $instruction
     */
    public function __construct($criteria, $property, $orTogether = false, $instruction = null)
    {
        $this->criteria = $criteria;
        $this->property = $property;
        if (is_null($instruction) && !is_bool($orTogether)) {
            $this->orTogether = false;
            $this->comparator = $this->determineComparator($property, $orTogether);
        } else {
            $this->orTogether = $orTogether;
            $this->comparator = $this->determineComparator($property, $instruction);
        }
    }

    /**
     * @param Builder $query
     */
    public function applyFilter(Builder $query)
    {
        $or = $this->orTogether ? 'Or' : '';
        switch ($this->comparator) {
            case 'equals':
                $method = camel_case($or . (is_array($this->criteria) ? 'WhereIn' : 'Where'));
                $query->$method($this->property, $this->criteria);
                break;
            case 'not equals':
                if (is_array($this->criteria)) {
                    $method = camel_case($or . 'WhereNotIn');
                    $query->$method($this->property, $this->criteria);
                } else {
                    $method = camel_case($or . 'Where');
                    $query->$method($this->property, '!=', $this->criteria);
                }
                break;
            case 'between':
                $method = camel_case($or . 'WhereBetween');
                $query->$method($this->property, $this->criteria);
                break;
            case 'not between':
                $method = camel_case($or . 'WhereNotBetween');
                $query->$method($this->property, $this->criteria);
                break;
            case '<':
                // pass-through
            case '>=':
                $method = camel_case($or . 'Where');
                $query->$method($this->property, $this->comparator, $this->criteria[0]);
                break;
            case '>':
                // pass-through
            case '<=':
                $method = camel_case($or . 'Where');
                $query->$method($this->property, $this->comparator, $this->criteria[1]);
                break;
            case 'not like':
                if (!is_array($this->criteria)) {
                    $method = camel_case($or . 'WhereRaw');
                    $query->$method('cast(? as char) not like ?', [$this->property, '%' . $this->criteria . '%']);
                } else {
                    if (!$or) {
                        foreach ($this->criteria as $pattern) {
                            $query->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                        }
                    } else {
                        $query->orWhere(function ($q) {
                            /** @var Builder $q */
                            foreach ($this->criteria as $pattern) {
                                $q->whereRaw('cast(? as char) not like ?', [$this->property, '%' . $pattern . '%']);
                            }
                        });
                    }
                }
                break;
            case 'like':
            default:
                if (!is_array($this->criteria)) {
                    $method = camel_case($or . 'WhereRaw');
                    $query->$method('cast(? as char) like ?', [$this->property, '%' . $this->criteria . '%']);
                } else {
                    if ($or) {
                        foreach ($this->criteria as $pattern) {
                            $query->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                        }
                    } else {
                        $query->where(function ($q) {
                            /** @var Builder $q */
                            foreach ($this->criteria as $pattern) {
                                $q->orWhereRaw('cast(? as char) like ?', [$this->property, '%' . $pattern . '%']);
                            }
                        });
                    }
                }
        }
    }

    /**
     * @return string
     */
    public function presentFilter()
    {
        switch ($this->comparator) {
            case 'between':
                // pass-through
            case 'not between':
                return trans('quality-control.filterable.presenting.' . $this->comparator, $this->criteria);
            case '<':
                // pass-through
            case '>=':
                return $this->comparator . ' ' . $this->criteria[0];
                break;
            case '>':
                // pass-through
            case '<=':
                return $this->comparator . ' ' . $this->criteria[1];
                break;

            case 'not like':
                return trans('quality-control.filterable.presenting.not like') . (is_array($this->criteria) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criteria) : $this->criteria);
            case 'like':
                return trans('quality-control.filterable.presenting.like') . (is_array($this->criteria) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criteria) : $this->criteria);

            case 'equals':
                return trans('quality-control.filterable.presenting.is') . (is_array($this->criteria) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criteria) : $this->criteria);
            case 'not equals':
                return trans('quality-control.filterable.presenting.is not') . (is_array($this->criteria) ? trans('quality-control.filterable.presenting.any of') . implode(', ', $this->criteria) : $this->criteria);

            default:
                return $this->criteria;
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
        if (!nonZeroEmpty($this->criteria[0]) && !nonZeroEmpty($this->criteria[1])) {
            return $not . 'between';
        } elseif (!nonZeroEmpty($this->criteria[0])) {
            return $not ? '<' : '>=';
        } else {
            return $not ? '>' : '<=';
        }
    }
}