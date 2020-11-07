<?php

namespace Bonton\Japi;

use Bonton\Japi\Exceptions\UnrecognizedRelationship;

class Graph
{
    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function getIncludes(string $name, array $url_includes): Includes
    {
        $includes = new Includes();
        $this->factory->resourceExists($name);

        foreach ($url_includes as $url_include) {
            list($type, $relation) = explode('.', $url_include);
            $companion = $this->factory->getCompanion($type);

            if (!$companion->isRelationship($relation)) {
                throw new UnrecognizedRelationship($type, $relation);
            }

            $includes->include($type, $relation);
        }

        $recursed = [];
        $this->recurseIncludes($includes, $name, $recursed);

        return $includes;
    }

    protected function recurseIncludes(Includes $includes, string $type, array &$recursed): void
    {
        if (isset($recursed[$type])) {
            return;
        }

        $recursed[$type] = true;

        $companion = $this->factory->getCompanion($type);

        $recursion_list = $includes->hasIncludesFor($type)
            ? $includes->getIncludesFor($type)
            : $companion->defaultIncludes();

        foreach ($recursion_list as $relation) {
            if (!$companion->isRelationship($relation)) {
                throw new UnrecognizedRelationship($type, $relation);
            }

            $this->includes->include($type, $relation);

            $target = $companion->getRelationship($relation)->getTarget();
            $this->factory->resourceExists($target);
            $this->recurseIncludes($includes, $target, $recursed);
        }
    }

    public function getFields(string $name, array $url_includes, array $url_fields): Fields
    {
        $fields = new Fields();

        foreach ($url_fields as $url_res_type => $url_res_fields) {
            $companion = $this->factory->getCompanion($url_res_type);

            foreach ($url_res_fields as $url_res_field) {
                if ($companion->isRelationship($url_res_field)) {
                    $fields->relationship($url_res_type, $url_res_field);
                } else {
                    $fields->attribute($url_res_type, $url_res_field);
                }
            }
        }

        $recursed = [];
        $this->recurseFields($fields, $name, $recursed);

        return $fields;
    }

    protected function recurseFields(Fields $fields, string $type, array &$recursed): void
    {
        if (isset($recursed[$type])) {
            return;
        }

        $recursed[$type] = true;

        $companion = $this->factory->getCompanion($type);

        list($attr_list, $relationship_list) = $fields->hasFieldsFor($type)
            ? [$fields->getAttributesFor($type), $fields->getRelationshipsFor($type)]
            : [$companion->defaultAttributes(), $companion->defaultRelationships()];

        foreach ($attr_list as $attr) {
            $fields->attribute($type, $attr);
        }

        foreach ($relationship_list as $rel) {
            $fields->relationship($type, $rel);

            $target = $companion->getRelationship($rel)->getTarget();
            $this->factory->resourceExists($target);
            $this->recurseFields($fields, $target, $recursed);
        }
    }
}
