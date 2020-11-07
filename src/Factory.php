<?php

namespace Bonton\Japi;

use Bonton\Japi\Exceptions\ResourceNotFound;
use Illuminate\Contracts\Foundation\Application;

class Factory
{
    protected $resources = [];
    protected $companion_cache = [];
    protected $resolver_cache = [];
    protected $app;

    public function __construct(array $resources, Application $app)
    {
        $this->app = $app;
        $this->resources = $resources;
    }

    public function resourceList(): array
    {
        return array_keys($this->resources);
    }

    public function resourceExists($name)
    {
        if (!isset($this->config['resources'][$name])) {
            throw new ResourceNotFound($name);
        }
    }

    public function hasResource($name): bool
    {
        return isset($this->config['resources'][$name]);
    }

    public function getCompanion($name): Companion
    {
        $this->resourceExists($name);

        if (!isset($this->companion_cache[$name])) {
            $companion_class_name = $this->config['resources'][$name][0];
            $this->companion_cache[$name] = $this->app->make($companion_class_name);
        }

        return $this->companion_cache[$name];
    }

    public function getResolver($name): Resolver
    {
        $this->resourceExists($name);

        if (!isset($this->resolver_cache[$name])) {
            $resolver_class_name = $this->config['resources'][$name][1];
            $this->resolver_cache[$name] = $this->app->make($resolver_class_name);
        }

        return $this->resolver_cache[$name];
    }
}
