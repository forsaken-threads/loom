<?php

namespace App\Resources;

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class LoomResource extends Model
{
    use Weavable;

    // Loom resources use UUIDs for their primary key
    public $incrementing = false;

    protected $guarded = ['id'];

    /**
     * @return QualityControl
     */
    public function getQualityControl()
    {
        $qc = new QualityControl([
            'name' => 'string|max:100|',
            'nickname' => ['regex:/^[A-Za-z0-9]{3,}$/'],
            'email' => 'email',
            'role' => Rule::in(['Admin', 'User', 'Guest'])
        ]);
        return $qc
            ->forContext('create')
                ->requireAll()
                ->append(['nickname', 'email'], Rule::unique('users'))
            ->forContext('update')
                ->append(['nickname', 'email'], Rule::unique('users')->whereNot('id', $this->id));
    }
}