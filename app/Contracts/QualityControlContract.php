<?php

namespace App\Contracts;

use App\Loom\FilterScope;
use App\Loom\Inspections;
use App\Traits\QualityControllable;

interface QualityControlContract
{
    /**
     * @param $resource
     * @return QualityControllable|bool
     */
    public function getConnectableResource($resource);

    /**
     * @return array
     */
    public function getConnectableResources();

    /**
     * @param $scopeName
     * @return FilterScope|bool
     */
    public function getFilterScope($scopeName);

    /**
     * @return array
     */
    public function getMessages();

    /**
     * @param null $context
     * @return Inspections
     */
    public function getRules($context = null);
}