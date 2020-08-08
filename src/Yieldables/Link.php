<?php

namespace Bonton\Japi\Yieldables;

class Link {
    public $name = null;
    public $link = null;
    public $meta = false;

    public $parent_type;
    public $parent_id;
    public $parent_relation_name;

    public function __construct($name, $link, $meta)
    {
        $this->name = $name;
        $this->link = $link;
        $this->meta = $meta;
    }

    public function for($id) {
        $this->parent_id = $id;
        return $this;
    }
}
