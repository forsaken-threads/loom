<{{ '?' }}php

namespace App\Resources{{ $group }};

use App\Traits\Weavable;
use Illuminate\Database\Eloquent\Model;

class {{ $name }} extends Model
{
    use Weavable;

    // Webstuhl resources use UUIDs for their primary key
    public $incrementing = false;

    /**
     * Get the contextual validation rules for the Webstuhl resource
     */
    public function getValidationRules()
    {
        return [
            'default' => [],
        ];
    }
}