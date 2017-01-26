<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Contracts\DefaultFilterable;
use App\Traits\Weavable;
use App\Loom\Filter;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TestableResource extends Model implements DefaultFilterable
{
    use Weavable;

    public $connection = 'testing';

    /**
     * @return Filter[]
     */
    public function getDefaultFilters()
    {
        return [
            'rank' => new Filter(['0', '100'], 'rank', 'between'),
            'level'=> new Filter(['50', '100'], 'level', 'between')
        ];
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