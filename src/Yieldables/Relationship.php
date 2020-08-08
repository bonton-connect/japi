<?php

namespace Bonton\Japi\Yieldables;

class Relationship {
    public $child_id;
    public $child_type;

    public $parent_type;
    public $parent_relation_name;
    public $parent_id;

    public function __construct($parent_type, $child_id)
    {
        $this->parent_type = $parent_type;
        $this->child_id = $child_id;
    }

    public function for(string $id): self {
        $this->parent_id = $id;
        return $this;
    }
}
