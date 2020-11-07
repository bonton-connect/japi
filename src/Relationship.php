<?php

namespace Bonton\Japi;

class Relationship
{
    public const MANY = "MANY";
    public const ONE = "ONE";

    protected $target;
    protected $type;
    protected $index;
    protected $replace;
    protected $remove;

    public function __construct(string $type, string $target)
    {
        $this->target = $target;
        $this->type = $type;
    }

    public static function many(string $target)
    {
        return new static(static::MANY, $target);
    }

    public static function one(string $target)
    {
        return new static(static::ONE, $target);
    }

    public function index($name)
    {
        $this->index = $name;
        return $this;
    }

    public function replace($name)
    {
        $this->replace = $name;
        return $this;
    }

    public function remove($name)
    {
        $this->remove = $name;
        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getReplace()
    {
        return $this->replace;
    }

    public function getRemove()
    {
        return $this->remove;
    }

    public function supportsIndex()
    {
        return !!$this->index;
    }

    public function supportsReplace()
    {
        return !!$this->replace;
    }

    public function supportsRemove()
    {
        return !!$this->remove;
    }
}
