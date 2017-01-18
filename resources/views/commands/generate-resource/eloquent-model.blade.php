<{{ '?' }}php

namespace App\Resources{{ $group }};

@if ($group)
use App\Resources\Resourceable;
@endif
use Illuminate\Database\Eloquent\Model;

class {{ $name }} extends Model
{
    use Resourceable;

    public function getValidationRules()
    {
        return [
            'default' => [],
        ];
    }
}