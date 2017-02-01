<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TestableResourceThree extends Model
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
        $qc = new QualityControl([
            'name' => 'string|between:3,100',
            'nickname' => ['string' ,'between:2,20'],
            'email' => 'email',
            'rank' => 'integer|between:-100,100',
            'level' => 'integer|max:100',
            'role' => Rule::in(['Admin', 'User', 'Guest']),
        ]);
        return $qc
            ->forContext('create')
                ->requireAll()
                ->append(['nickname', 'email'], Rule::unique('testable_resources'))
            ->forContext('update')
                ->append(['nickname', 'email'], Rule::unique('testable_resources')->whereNot('id', $this->id))
            ->forContext('filter')
                ->replace(['name', 'email'], 'string');
    }
}