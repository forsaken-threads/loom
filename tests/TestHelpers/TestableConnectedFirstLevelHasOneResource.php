<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TestableConnectedFirstLevelHasOneResource extends Model
{
    use Weavable;

    public $connection = 'testing';

    /**
     * @return QualityControl
     */
    public function getQualityControl()
    {
        $qc = new QualityControl([
            'address' => 'string|between:3,100',
            'city' => ['string' ,'between:2,20'],
            'state' => Rule::in(['FL', 'MI']),
        ]);
        return $qc->setConnectableResources(['TestableConnectedSecondLevelResource']);
    }

    public function TestableConnectedSecondLevelResource()
    {
        return $this->hasOne(TestableConnectedSecondLevelResource::class);
    }
}