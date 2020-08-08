<?php

namespace Bonton\Japi\Yieldables;

class Record {
    public $record;
    public $type;
    public $relations = [];
    public $relation_metas = [];
    public $relation_links = [];
    public $meta = [];

    public function __construct($type, $record)
    {
        $this->type = $type;
        $this->record = $record;
    }
}
