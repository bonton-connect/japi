<?php

namespace Bonton\Japi\Errors;

use Bonton\Japi\Yieldables\Meta;

class OrphanRelationMeta extends \Exception {
    public $title = "Orphan Relationship Metadata";

    public function __construct(Meta $meta)
    {
        $parent_type = $meta->parent_type;
        $relation_name = $meta->parent_relation_name;
        $meta = json_encode($meta->meta);

        parent::__construct(
            "Relationship metadata for $parent_type.$relation_name $meta does not include a parent id via ->for(\$id)"
        );
    }
}
