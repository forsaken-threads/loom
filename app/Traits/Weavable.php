<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * Class Weavable
 * @package App\Resources
 *
 * @method static creating(callable $callback)
 */
trait Weavable
{
    use QualityControllable;

    /**
     *
     */
    public static function bootWeavable()
    {
        // Loom resources use UUIDs for primary keys
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