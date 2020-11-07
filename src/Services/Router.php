<?php

namespace Bonton\Japi\Services;

use Illuminate\Routing\Router as LaravelRouter;

class Router
{
    protected $router;
    protected $main;

    public function __construct(LaravelRouter $router, Main $main)
    {
        $this->main = $main;
        $this->router = $router;
    }

    public function base()
    {
        $this->main->setBasePrefix($this->router->getLastGroupPrefix());
    }
}
