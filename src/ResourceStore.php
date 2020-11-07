<?php

namespace Bonton\Japi;

use Bonton\Japi\Exceptions\ResourceNotInStore;

class ResourceStore
{
    protected $resources = [];

    public function store(Resource $resource): void
    {
        if (!isset($this->resources[$resource->type])) {
            $this->resources[$resource->type] = [];
        }

        $this->resources[$resource->type][$resource->id] = $resource;
    }

    public function has(Linkage $link): bool
    {
        if (!isset($this->resources[$link->getType()])) {
            return false;
        }

        if (!isset($this->resources[$link->getType()][$link->getId()])) {
            return false;
        }

        return true;
    }

    public function get(Linkage $link): Resource
    {
        if (!$this->has($link)) {
            throw new ResourceNotInStore($link->getType(), $link->getId());
        }

        return $this->resources[$link->getType()][$link->getId()];
    }
}
