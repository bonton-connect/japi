<?php

namespace Bonton\Japi\Yieldables;

class Meta {
    public $meta = null;

    public $parent_type;
    public $parent_id;
    public $parent_relation_name;

    public function __construct($meta)
    {
        $this->meta = $meta;
    }

    public function for($id) {
        $this->parent_id = $id;
        return $this;
    }
}
