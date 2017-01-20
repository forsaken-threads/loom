<?php

namespace App\Resources;

use App\Traits\Weavable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WebstuhlResource extends Model
{
    use Weavable;

    protected $guarded = ['id'];

    /**
     * @return array
     */
    public function getValidationRules()
    {
        return [
            'default' => [],
        ];
    }
}