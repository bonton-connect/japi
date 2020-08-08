<?php

namespace Bonton\Japi\Errors;

class ResourceNotFound extends \Exception {
    public $title = "Resource Not Found";

    public function __construct($ref)
    {
        $type = $ref['type'];
        $id = $ref['id'];

        parent::__construct(
            "A resource could not be loaded for type = $type, id = $id"
        );
    }
}
