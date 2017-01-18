<{{ '?' }}php

namespace App\Http\Controllers\Resources{{ $group }};

use App\Http\Controllers\Controller;
@if ($group) use App\Http\Controllers\Resources\WeavesResources; @endif

class {{ $name }} extends Controller
{
    use WeavesResources;

    protected function getValidationRules()
    {
        return [
            'default' => [],
        ];
    }
}