<?php

namespace Bonton\Japi;

class Resource
{
    public $type;
    public $id;
    public $record;
    public $relationships = [];
    public $links = [];
    public $meta = [];

    protected $main = false;

    public function __construct(string $type, string $id, string $record = null)
    {
        $this->type = $type;
        $this->id = $id;
        $this->record = $record;
    }

    public function addRelated(string $name, Resource $resource)
    {
        if (!isset($this->relationships[$name])) {
            $this->relationships[$name] = new RelationshipContainer();
        }

        $this->relationships[$name][] = $resource;
    }

    public function linkage()
    {
        return new Linkage($this->type, $this->id);
    }

    public function mergeMeta(array $meta)
    {
        $this->meta = array_merge($meta);
    }

    public function addLink(string $key, string $path)
    {
        $this->links[$key] = $path;
    }

    public function main(): void
    {
        $this->main = true;
    }

    public function isMain(): bool {
        return $this->main;
    }
}
