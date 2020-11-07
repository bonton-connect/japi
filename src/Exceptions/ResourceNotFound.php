<?php

namespace Bonton\Japi\Exceptions;

use Exception;

class ResourceNotFound extends Exception
{
    public function __construct($name)
    {
        parent::__construct("Resource named '$name' not found.");
    }
}
