<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Contracts\DefaultFilterable;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
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
     * @return FilterCollection
     */
    public function getDefaultFilters()
    {
        $filters = new FilterCollection();
        return $filters
            ->addFilter('rank', new Filter(['0', '100'], 'rank', 'between'))
            ->addFilter('level', new Filter(['50', '100'], 'level', 'between'));
    }

    /**
     * @return array
     */
    public function getConnectableResources()
    {
        return [
            'TestableConnectedFirstLevelBelongsToResource',
            'TestableConnectedFirstLevelBelongsToManyResource',
            'TestableConnectedFirstLevelHasManyResource',
            'TestableConnectedFirstLevelHasOneResource'
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
        $filterScope = new FilterScope('awesomeishPeople');
        $filterScope->withArguments('level')
            ->setArgumentDefault('level', 33)
            ->setValidationRules(['level' => 'numeric|between:30,100']);
        return $qc
            ->forContext('create')
                ->requireAll()
                ->append(['nickname', 'email'], Rule::unique('testable_resources'))
            ->forContext('update')
                ->append(['nickname', 'email'], Rule::unique('testable_resources')->whereNot('id', $this->id))
            ->forContext('filter')
                ->replace(['name', 'email'], 'string')
            ->withScope('awesomePeople')
            ->withScope($filterScope);
    }

    public function scopeAwesomePeople($query)
    {
        return $query->where('level', '>', 75);
    }

    public function scopeAwesomeishPeople($query, $level)
    {
        return $query->where('level', '>', $level);
    }

    public function scopeAwesomeishRankedPeople($query, $level, $rank, $role = 'Admin')
    {
        return $query->where('level', '>', $level)->where('rank', '>', $rank)->where('role', $role);
    }

    public function TestableConnectedFirstLevelBelongsToResource()
    {
        return $this->belongsTo(TestableConnectedFirstLevelBelongsToResource::class);
    }

    public function TestableConnectedFirstLevelBelongsToManyResource()
    {
        return $this->belongsToMany(TestableConnectedFirstLevelBelongsToManyResource::class);
    }

    public function TestableConnectedFirstLevelHasManyResource()
    {
        return $this->hasMany(TestableConnectedFirstLevelHasManyResource::class);
    }

    public function TestableConnectedFirstLevelHasOneResource()
    {
        return $this->hasOne(TestableConnectedFirstLevelHasOneResource::class);
    }
}