<?php

namespace App\Resources;

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;

class LoomResource extends Model
{
    use Weavable;

    /**
     * Loom resources use UUIDs for their primary key
     *
     * @var bool
     */
    public $incrementing = false;

    protected $guarded = ['id'];

    /**
     * Get the Loom resources that this resource is connected to and
     * that will be publicly exposed by Loom
     *
     * @return array
     */
    public function getConnectableResources()
    {
        return [];
    }

    /**
     * Get the Quality Control object for the Loom resource
     *
     * @return QualityControl
     */
    public function getQualityControl()
    {
        return new QualityControl([
        ]);
    }
}