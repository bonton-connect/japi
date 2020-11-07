<?php

namespace Bonton\Japi\Services;

use Bonton\Japi\Factory;
use Bonton\Japi\Operators\Retreiver;
use Illuminate\Contracts\Foundation\Application;

class Main
{
    protected $basePrefix;
    protected $config;
    protected $app;
    protected $factory;
    protected $retreiver;

    public function __construct(Application $app, $config = [])
    {
        $this->config = array_merge([], $config);
        $this->app = $app;

        $this->factory = new Factory($config['resources'], $app);
        $this->retreiver = new Retreiver($this->factory);
    }

    public function setBasePrefix(string $prefix)
    {
        $this->basePrefix = $prefix;
    }

    public function getBasePrefix()
    {
        return $this->basePrefix;
    }
}
