<{{ '?' }}php

namespace App\Resources{{ $group }};

use App\Traits\Weavable;
use App\Webstuhl\QualityControl;
use Illuminate\Database\Eloquent\Model;

class {{ $name }} extends Model
{
    use Weavable;

    // Webstuhl resources use UUIDs for their primary key
    public $incrementing = false;

    /**
     * Get the contextual validation rules for the Webstuhl resource
     * @return QualityControl
     */
    public function getQualityControl()
    {
        $qc = new QualityControl([]);
        return $qc;
    }
}