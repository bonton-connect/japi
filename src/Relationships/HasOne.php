<?php

namespace Bonton\Japi\Relationships;

class HasOne {
    public $method;
    public $type;

    public function __construct($type, $method)
    {
        $this->type = $type;
        $this->method = $method;
    }
}
