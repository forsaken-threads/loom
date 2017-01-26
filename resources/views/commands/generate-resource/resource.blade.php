<{{ '?' }}php

namespace App\Resources{{ $group }};

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Database\Eloquent\Model;

class {{ $name }} extends Model
{
    use Weavable;

    // Loom resources use UUIDs for their primary key
    public $incrementing = false;

    /**
     * Get the contextual validation rules for the Loom resource
     * @return QualityControl
     */
    public function getQualityControl()
    {
        $qc = new QualityControl([]);
        return $qc;
    }
}