<{{ '?' }}php

namespace App\Http\Controllers\Resources{{ $group }};

use App\Http\Controllers\Controller;
@if ($group)
use App\Http\Controllers\Resources\WeavesResources;
@endif

class {{ $name }}Controller extends Controller
{
    use WeavesResources;
}