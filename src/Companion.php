<?php

namespace Bonton\Japi;

class Companion
{
    protected $relationships = [];

    public function __construct()
    {
        $this->relationships();
    }

    protected function one(string $name, string $target): void
    {
        $rel = Relationship::one($target);
        $this->relationships[$name] = $rel;
    }

    protected function many(string $name, string $target): void
    {
        $rel = Relationship::many($target);
        $this->relationships[$name] = $rel;
    }

    public function relationships(): void
    {
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function isRelationship(string $name): bool
    {
        return isset($this->relationships[$name]);
    }

    public function getRelationship(string $name): Relationship
    {
        return $this->relationships[$name];
    }

    public function attributes($record): array
    {
        return $record->toArray();
    }

    public function key($record): string
    {
        return $record['id'];
    }

    public function defaultIncludes(): array
    {
        return [];
    }

    public function defaultAttributes()
    {
        return [];
    }

    public function defaultRelationships()
    {
        return [];
    }
}
