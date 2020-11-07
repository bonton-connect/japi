<?php

namespace Bonton\Japi;

class Linkage
{
    protected $type;
    protected $id;

    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'type' => $this->type
        ];
    }
}
