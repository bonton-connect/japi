<?php

namespace Bonton\Japi\Exceptions;

use Exception;

class OperationNotAvailable extends Exception
{
    public function __construct($operation)
    {
        parent::__construct("Operation '$operation' not available.");
    }
}
