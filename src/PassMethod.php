<?php

namespace Bonton\Japi;

class PassMethod
{
    protected $ref;

    public static function from($ref)
    {
        return new static($ref);
    }

    public function __construct($ref)
    {
        $this->ref = $ref;
    }

    public function method($name)
    {
        $ref = $this->ref;

        return function () use ($name, $ref) {
            $args = func_get_args();
            call_user_func_array([$ref, $name], $args);
        };
    }
}
