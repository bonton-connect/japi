<?php

namespace Bonton\Japi\Exceptions;

use Exception;

class UnrecognizedRelationship extends Exception
{
    public function __construct(string $type, string $relation)
    {
        parent::__construct("Relation ship '$relation' on type '$type' is not recognized.");
    }
}
