<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TestableConnectedFirstLevelHasManyResource extends Model
{
    use Weavable;

    public $connection = 'testing';

    /**
     * @return array
     */
    public function getConnectableResources()
    {
        return [];
    }

    /**
     * @return QualityControl
     */
    public function getQualityControl()
    {
        return new QualityControl([
            'address' => 'string|between:3,100',
            'city' => ['string' ,'between:2,20'],
            'state' => Rule::in(['FL', 'MI']),
        ]);
    }
}