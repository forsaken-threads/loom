<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    use Connectable;

    /** @var array */
    protected $defaultSort;

    /**
     * @return array
     */
    abstract public function getSortableProperties();

    public function applySorts($givenSorts, Builder $query)
    {
        $belongsToManySorts = [];
        if (is_string($givenSorts)) {
            $givenSorts = json_decode($givenSorts, true);
        }
        if (!$givenSorts || !is_array($givenSorts)) {
            if (empty($this->defaultSort)) {
                return [];
            }
            $givenSorts = $this->defaultSort;
        }
        $orderedSorts = $this->getValidSorts($this->getSortableProperties(), $givenSorts);
        foreach ($orderedSorts as $order => $orderedSort) {
            foreach ($orderedSort as $property => $sort) {
                $result = $this->applySort($sort, $property, $query);
                foreach ($result as $belongsToMany => $belongsToManies) {
                    if (empty($belongsToManySorts[$belongsToMany])) {
                        $belongsToManySorts[$belongsToMany] = array();
                    }
                    foreach ($belongsToManies as $belongsToManySort) {
                        $belongsToManySorts[$belongsToMany][] = $belongsToManySort;
                    }
                }
            }
        }
        return [$this->presentSorts($orderedSorts), $belongsToManySorts];
    }

    public function getValidSorts(array $properties, array $givenSorts)
    {
        $returnSorts = [];
        foreach ($givenSorts as $order => $sortsGiven) {
            $sorts = [];
            $returnSorts[$order] = [];
            foreach ($properties as $property => $ruleset) {
                if (isset($sortsGiven[$property])) {
                    $sort = in_array($sortsGiven[$property], ['asc', 'desc']) ? $sortsGiven[$property] : 'asc';
                    $sorts[$property] = $sort;
                }
            }
//            foreach ($this->getActiveEagerLinks() as $eager_link) {
//                if (!empty($sortsGiven[snake_case($eager_link)])) {
//                    $eager_model_name = get_class($this->$eager_link()->getRelated());
//                    $eager_model = new $eager_model_name;
//                    $sorts[$eager_link] = $eager_model->getSortsViaDefaultValidationRules([$sortsGiven[snake_case($eager_link)]]);
//                }
//            }
            foreach ($sorts as $property => $sort) {
//                if (in_array($property, $this->getActiveEagerLinks())) {
//                    $returnSorts[$order]['__auto_eager_link'][$property] = $sorts[$property];
//                    continue;
//                }
                $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'asc';
//                $returnSorts[$order][$this->isPlaceHolderProperty($property) || in_array($property, $this->appends) ? $property : $this->property($property)] = $sort;
                $returnSorts[$order][$property] = $sort;
            }
        }
        return $returnSorts;
    }

    protected function applySort($sort, $property, Builder &$query)
    {
//        static $eager_joins = [];

        $belongsToManySorts = [];
//        if ($property == '__auto_eager_link') {
//            foreach ($sort as $eager_model => $eager_ordered_sorts) {
//                $eager = studly_case($eager_model);
//                if (!in_array($eager, $eager_joins)) {
//                    $eager_joins[] = $eager;
//                    switch (class_basename($this->$eager())) {
//                        case 'BelongsTo':
//                            $query->leftJoin($this->$eager()->getRelated()->getTableAlias(), $this->$eager()->getRelated()->property('id'), '=', $this->property($this->$eager()->getForeignKey()));
//                            break;
//                        case 'BelongsToMany':
//                            $query->join($this->$eager()->getTable(), $this->property('id'), '=', $this->$eager()->getForeignKey());
//                            $query->leftJoin($this->$eager()->getRelated()->getTableAlias(), $this->$eager()->getRelated()->property('id'), '=', $this->$eager()->getOtherKey());
//                            if (!$query->getQuery()->groups) {
//                                $query->groupBy($query->getModel()->property('id'));
//                            }
//                            break;
//                        case 'HasMany':
//                        case 'HasOne':
//                            $query->leftJoin($this->$eager()->getRelated()->getTableAlias(), $this->property('id'), '=', $this->$eager()->getForeignKey());
//                            break;
//                        default:
//                            throw new Exception('Sort handler missing for ' . class_basename($this->$eager()) . ' related resources');
//                    }
//                }
//                foreach ($eager_ordered_sorts as $eager_order => $eager_sort) {
//                    foreach ($eager_sort as $property => $sort) {
//                        if (class_basename($this->$eager()) == 'BelongsToMany') {
//                            if (empty($belongsToManySorts[$eager])) {
//                                $belongsToManySorts[$eager] = [];
//                            }
//                            $query->orderByRaw("group_concat($property order by $property $sort) $sort");
//                            $belongsToManySorts[$eager][] = [$property, $sort];
//                        } else {
//                            $this->applySort($sort, $property, $query);
//                        }
//                    }
//                }
//            }
//        }
        if (method_exists($this, 'orderBy' . studly_case($property))) {
            call_user_func_array([$this, 'orderBy' . studly_case($property)], [$query, $sort]);
        } else {
            $query->orderByRaw('cast(' . $property . ' as char) ' . $sort);
        }
        return $belongsToManySorts;
    }

    protected function presentSorts($orderedSortsApplied)
    {
        $sorts = [];
        foreach ($orderedSortsApplied as $order => $sortsApplied) {
            foreach ($sortsApplied as $property => $sort) {
//                if ($property == '__auto_eager_link') {
//                    foreach ($sort as $eager_model => $eager_sorts) {
//                        $sorts[$order] = empty($this->presentSorts($eager_sorts)) ? 'asc' : $this->presentSorts($eager_sorts)[0];
//                    }
//                    continue;
//                }
                $sorts[$order][$property] = $sort;
            }
        }
        return $sorts;
    }
}