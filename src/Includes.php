<?php

namespace Bonton\Japi;

class Includes
{
    protected $includes = [];

    public function include(string $type, string $relation): void
    {
        if (!isset($this->includes[$type])) {
            $this->includes[$type] = [];
        }

        $this->includes[$type][$relation] = true;
    }


    public function isIncluded(string $type, string $relation): bool
    {
        return isset($this->includes[$type][$relation]) && $this->includes[$type][$relation] === true;
    }

    public function hasIncludesFor(string $type): bool
    {
        return isset($this->includes[$type]);
    }

    public function getIncludesFor(string $type): array
    {
        return $this->hasIncludesFor($type) ? $this->includes[$type] : [];
    }
}
