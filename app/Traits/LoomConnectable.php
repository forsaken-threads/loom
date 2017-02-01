<?php

namespace App\Traits;

trait LoomConnectable
{
    /**
     * Get the Loom resources that this resource is connected to and
     * that will be publicly exposed by Loom
     *
     * @return array
     */
    abstract public function getConnectableResources();
}