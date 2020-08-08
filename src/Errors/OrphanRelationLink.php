<?php

namespace Bonton\Japi\Errors;

use Bonton\Japi\Yieldables\Link;

class OrphanRelationLink extends \Exception {
    public $title = "Orphan Relationship Link";

    public function __construct(Link $link)
    {
        $parent_type = $link->parent_type;
        $relation_name = $link->parent_relation_name;
        $name = $link->name;
        $href = $link->link;

        parent::__construct(
            "Relationship link for $parent_type.$relation_name '$name': '$href' does not include a parent id via ->for(\$id)"
        );
    }
}
