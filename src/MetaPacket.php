<?php

namespace Bonton\Japi;

class MetaPacket
{
    public $meta = [];

    public function __construct($meta)
    {
        $this->meta = $meta;
    }
}
