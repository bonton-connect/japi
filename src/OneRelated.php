<?php

namespace Bonton\Japi;

use Bonton\Japi\Yieldables\Relationship;

class OneRelated {
    public $data = null;

    public function addRelation(Relationship $rel): self {
        $this->data = $rel;
        return $this;
    }
}
