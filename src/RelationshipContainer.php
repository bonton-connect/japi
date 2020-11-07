<?php

namespace Bonton\Japi;

class RelationshipContainer
{
    public $links = [];
    public $meta = [];
    public $resources = [];

    public function addResource(Linkage $res)
    {
        $this->resources[] = $res;
    }

    public function mergeMeta(array $meta)
    {
        $this->meta = array_merge($meta);
    }

    public function addLink(string $key, string $path)
    {
        $this->links[$key] = $path;
    }
}
