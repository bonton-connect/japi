<?php

namespace Bonton\Japi\Yieldables;

class Attribute {
    public $value;
    public $name;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}
