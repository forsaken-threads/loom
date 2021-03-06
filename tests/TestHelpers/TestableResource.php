<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Contracts\DefaultFilterable;
use App\Loom\FilterCollection;
use App\Loom\FilterScope;
use App\Traits\Weavable;
use App\Loom\FilterCriterion;
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
            ->addFilter('rank', new FilterCriterion(['0', '100'], 'rank', 'between'))
            ->addFilter('level', new FilterCriterion(['50', '100'], 'level', 'between'));
    }

    /**
     * @return QualityControl
     */
    public function getQualityControl()
    {
        $qc = new QualityControl([
            'name' => 'string|between:3,100',
            'nick_name' => ['string' ,'between:2,20'],
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
            ->setConnectableResources($this, [
                'TestableConnectedFirstLevelBelongsToResource',
                'TestableConnectedFirstLevelBelongsToManyResource',
                'TestableConnectedFirstLevelHasManyResource',
                'TestableConnectedFirstLevelHasOneResource'
            ])
            ->withPivot('TestableConnectedFirstLevelBelongsToManyResource', ['divot' => 'string|min:3'])
            ->forContext('create')
                ->requireAll()
                ->append(['nick_name', 'email'], Rule::unique('testable_resources'))
            ->forContext('update')
                ->append(['nick_name', 'email'], Rule::unique('testable_resources')->whereNot('id', $this->id))
            ->forContext('filter')
                ->replace(['name', 'email'], 'string')
                ->replace('divot', 'string', 'TestableConnectedFirstLevelBelongsToManyResources')
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