<?php

namespace Bonton\Japi\Errors;

class RelatedResourceNotFound extends ResourceNotFound {
    public $title = "Related Resource Not Found";

    public function __construct($ref)
    {
        $type = $ref['type'];
        $id = $ref['id'];

        parent::__construct(
            "A resource could not be loaded for type = $type, id = $id"
        );
    }
}
