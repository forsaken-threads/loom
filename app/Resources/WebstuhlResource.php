<?php

namespace App\Resources;

use App\Traits\Weavable;
use Illuminate\Database\Eloquent\Model;

class WebstuhlResource extends Model
{
    use Weavable;

    // Webstuhl resources use UUIDs for their primary key
    public $incrementing = false;

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