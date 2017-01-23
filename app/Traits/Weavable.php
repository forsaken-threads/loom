<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
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
}