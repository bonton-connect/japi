<?php

namespace Bonton\Japi;

class Fields
{
    protected $attributes = [];
    protected $relationships = [];

    public function attribute(string $type, string $attr_name): void
    {
        if (!isset($this->attributes[$type])) {
            $this->attributes[$type] = [];
        }

        $this->attributes[$type][$attr_name] = true;
    }

    public function relationship(string $type, string $rel_name): void
    {
        if (!isset($this->relationships[$type])) {
            $this->relationships[$type] = [];
        }

        $this->relationships[$type][$rel_name] = true;
    }


    public function isRelationshipIncluded(string $type, string $relation): bool
    {
        return isset($this->relationships[$type][$relation]) && $this->relationships[$type][$relation] === true;
    }

    public function isAttributeIncluded(string $type, string $attr): bool
    {
        return isset($this->attributes[$type][$attr]) && $this->attributes[$type][$attr] === true;
    }

    public function isFieldIncluded(string $type, string $name): bool
    {
        return $this->isAttributeIncluded($type, $name) || $this->isRelationshipIncluded($type, $name);
    }

    public function hasFieldsFor(string $type): bool
    {
        return isset($this->includes[$type]);
    }

    public function hasAttributesFor(string $type): bool
    {
        return isset($this->includes[$type]);
    }

    public function hasRelationshipsFor(string $type): bool
    {
        return isset($this->includes[$type]);
    }

    public function getAttributesFor(string $type): array
    {
        return $this->hasAttributesFor($type) ? $this->attributes[$type] : [];
    }

    public function getRelationshipsFor(string $type): array
    {
        return $this->hasRelationshipsFor($type) ? $this->relationships[$type] : [];
    }

    public function getFieldsFor(string $type): array
    {
        return array_merge(
            $this->getAttributesFor($type),
            $this->getRelationshipsFor($type)
        );
    }
}
