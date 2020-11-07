<?php

namespace Bonton\Japi;

class RecordPacket
{
    public $record;

    public function __construct($record)
    {
        $this->record = $record;
    }
}
