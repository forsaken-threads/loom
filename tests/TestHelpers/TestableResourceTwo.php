<?php

namespace ForsakenThreads\Loom\Tests\TestHelpers;

use App\Contracts\DefaultFilterable;
use App\Loom\QualityControl;
use App\Traits\Weavable;

class TestableResourceTwo implements DefaultFilterable
{
    use Weavable;

    public function getDefaultFilters()
    {
        return false;
    }

    /**
     * @return QualityControl
     */
    public function getQualityControl()
    {
        return new QualityControl();
    }
}