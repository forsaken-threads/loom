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
     * @param $resource
     * @param null|string $context
     * @return Inspections|bool
     */
    public function getFilterPivot($resource, $context = null);

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
     * @return QualityControllable|bool
     */
    public function getResource();

    /**
     * @param null $context
     * @return Inspections
     */
    public function getRules($context = null);
}