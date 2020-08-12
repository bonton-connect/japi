<?php

namespace Bonton\Japi;

use Bonton\Japi\Relationships\HasMany;
use Bonton\Japi\Relationships\HasOne;
use Bonton\Japi\Yieldables\Attribute;
use Bonton\Japi\Yieldables\Link;
use Bonton\Japi\Yieldables\Meta;
use Bonton\Japi\Yieldables\Record;
use Bonton\Japi\Yieldables\Relationship;
use Illuminate\Http\Request;


abstract class ResourceController {
    protected $type = false;

    public function type($type = false): string
    {
        if ($type && !$this->type) {
            $this->type = $type;
        }

        if (!$this->type) {
            $this->type = strtolower(get_class($this));
        }

        return $this->type;
    }

    public function getIdentifier($obj): string {
        return "".$obj->id;
    }

    abstract public function relationships(): array;

    abstract public function defaultFields(): array;
    abstract public function defaultIncludes(): array;

    abstract public function getAttributes($obj, $fields = []);

    abstract public function index($req, $page = null);
    abstract public function single($req, string $id);
    abstract public function batch(array $ids);

    abstract public function create(Request $req, $attributes, $relationships): string;
    abstract public function addRelations($record, $relationships);

    abstract public function update(Request $req, $record, $attributes, $relationships);
    abstract public function updateRelations($record, $relationships);

    abstract public function delete(Request $req, $record);
    abstract public function deleteRelations($record, $relationships);


    protected function record($value, $meta = []) {
        $r = new Record($this->type(), $value);
        $r->meta = $meta;

        return $r;
    }

    protected function meta($meta) {
        return new Meta($meta);
    }

    protected function attribute($name, $value) {
        return new Attribute($name, $value);
    }

    protected function relation($id) {
        return new Relationship($this->type(), $id);
    }

    protected function link($name, $link, $meta = false) {
        return new Link($name, $link, $meta);
    }

    protected function next($link, $meta = false) {
        return new Link('next', $link, $meta);
    }

    protected function prev($link, $meta = false) {
        return new Link('prev', $link, $meta);
    }

    protected function first($link, $meta = false) {
        return new Link('first', $link, $meta);
    }

    protected function last($link, $meta = false) {
        return new Link('last', $link, $meta);
    }

    protected function include($value, $meta = []) {
        $r = new Record(null, $value);
        $r->meta = $meta;

        return $r;
    }

    protected function hasOne($type, $method) {
        return new HasOne($type, $method);
    }

    protected function hasMany($type, $method) {
        return new HasMany($type, $method);
    }
}
