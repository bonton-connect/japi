<?php

namespace BontonConnect\Japi;

use Bonton\Japi\Yieldables\Relationship;

class ManyRelated {
    public $data = [];

    public function addRelation(Relationship $rel): self {
        $this->data[] = $rel;
        return $this;
    }
}
