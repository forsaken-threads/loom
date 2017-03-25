<?php

namespace App\Traits;

use App\Contracts\DefaultFilterable;
use App\Contracts\QualityControlContract;
use App\Exceptions\QualityControlException;
use App\Loom\FilterCollection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Filterable
 * @package App\Traits
 *
 * @method QualityControlContract getQualityControl()
 */
trait Filterable
{
    /**
     * @param $givenFilters
     * @param Builder $query
     * @return array
     * @throws QualityControlException
     */
    public function filter($givenFilters, Builder $query)
    {
        if (is_string($givenFilters)) {
            $givenFilters = json_decode($givenFilters, true);
        }

        if (!$givenFilters || !is_array($givenFilters)) {
            if (! $this instanceof DefaultFilterable) {
                return [];
            } else {
                $filters = $this->getDefaultFilters();
                if (! $filters instanceof FilterCollection) {
                    throw new QualityControlException(
                        trans('quality-control.filterable.get-default-filters-error', [
                            'class' => __CLASS__,
                            'got' => print_r($filters, true)
                            ]));
                }
            }
        } else {
            $filters = FilterCollection::make($givenFilters, $this->getQualityControl());
        }

        $filters->applyFilters($query);

        return $filters->presentFilters();
    }
}