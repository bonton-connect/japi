<?php

namespace Bonton\Japi\Exceptions;

use Exception;

class ResourceNotInStore extends Exception
{
    public function __construct(string $type, string $id)
    {
        parent::__construct("Resource is [type: $type, id: $id] not stored.");
    }
}
