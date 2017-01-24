<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;
use Ramsey\Uuid\Uuid;

/**
 * Class Weavable
 * @package App\Resources
 *
 * @method static creating(callable $callback)
 */
trait Weavable
{
    use ValidatesContextually;

    protected $defaultFilter;

    public function applyFiltersAndSorts(array $input, &$query)
    {
        $input = new Fluent($input);
//        list($sorts, $belongs_to_many_sorts) = $this->applySorts($input, $query);

        $filters = $input->get('filters');
        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }
        if ((!$filters || !is_array($filters)) && !empty($this->defaultFilter) && !method_exists($this, 'setDefaultFilter')) {
            return [
                'filters' => null,
//                'sorts' => $sorts,
//                'belongs_to_many_sorts' => $belongs_to_many_sorts
            ];
        } elseif (!$filters || !is_array($filters) || ! $this->getValidFilters($filters)) {
            if (!empty($this->defaultFilter)) {
                $filters = $this->defaultFilter;
            } elseif (method_exists($this, 'setDefaultFilter')) {
                $filters = $this->setDefaultFilter();
            }
        }
        $filters = $this->getValidFilters($filters);
        foreach ($filters as $property => $filter) {
            $this->applyFilter($filter, $property, $query);
        }
        return [
            'filters' => $this->presentFilters($filters),
//            'sorts' => $sorts,
//            'belongs_to_many_sorts' => $belongs_to_many_sorts
        ];
    }

    /**
     *
     */
    public static function bootWeavable()
    {
        // Webstuhl resources use UUIDs for primary keys
        static::creating(function ($model) {
            /** @var Model $model */
            $model->{$model->getKeyName()} = (string) Uuid::uuid4();
        });
    }

    /**
     * Use the input array to build an index response
     * @param array $input
     */
    public static function weave(array $input)
    {

    }

    /**
     * @param $filter
     * @param $property
     * @param Builder $query
     */
    protected function applyFilter($filter, $property, &$query)
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
        if (is_array($filter['comparator'])) {
            foreach ($filter['comparator'] as $key => $args) {
                switch ($key) {
                    case 'equals':
                        $query->where($property, $args);
                        break;
                    case 'in':
                        $query->whereIn($property, $args);
                        break;
                    case 'where':
                        $query->where($args[0]);
                        break;
                    case 'whereHas':
                        $query->whereHas($args[0], $args[1]);
                        break;
                }
            }
        } elseif (!is_array($filter['filter'])) {
            switch ($filter['comparator']) {
                case 'equals':
                    $query->where($property, $filter['filter']);
                    break;
                case 'not equals':
                    $query->where($property, '!=', $filter['filter']);
                    break;
                default:
                    $query->whereRaw('cast(? as char) like ?', [$property, '%'. $filter['filter'] . '%']);
            }
        } else {
            switch ($filter['comparator']) {
                case 'equals':
                    $query->whereIn($property, $filter['filter']);
                    break;
                case 'not equals':
                    $query->whereNotIn($property, $filter['filter']);
                    break;
                case 'between':
                    $query->whereRaw($property . ' between ? and ? ', $filter['filter']);
                    break;
                case '>=':
                case '<=':
                    $query->where($property, $filter['comparator'], $filter['filter'][0]);
                    break;
                case 'not like':
                    foreach ($filter['filter'] as $pattern) {
                        $query->where('cast(? as char) not like ?', [$property, '%' . $pattern . '%']);
                    }
                    break;
                case 'like':
                default:
                    $query->where(function($q) use ($filter, $property) {
                        /** @var Builder $q */
                        foreach ($filter['filter'] as $pattern) {
                            $q->orWhereRaw('cast(? as char) like ?', [$property, '%' . $pattern . '%']);
                        }
                    });
            }
        }
    }

    protected function presentFilters($filtersApplied)
    {
        $filters = [];
        foreach ($filtersApplied as $property => $filter) {
//            if ($property == '__auto_eager_links') {
//                foreach ($filter as $eager_model => $eager_filters) {
//                    $filters[$eager_model] = $this->presentFilters($eager_filters);
//                }
//                continue;
//            }
            switch ($filter['comparator']) {
                case 'between':
                    $filters[$filter['property']] = 'between ' . implode(' and ', $filter['filter']);
                    break;
                case '<=':
                case '>=':
                    $filters[$filter['property']] = $filter['comparator'] . ' ' . $filter['filter'][0];
                    break;
                case 'like':
                case 'equals':
                default:
                    $filters[$filter['property']] = $filter['filter'];
                    break;
            }
        }
        return $filters;
    }
}