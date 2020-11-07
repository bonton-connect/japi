<?php

namespace Bonton\Japi\Facades;

use Illuminate\Support\Facades\Facade;
use Bonton\Japi\Services\Router as RouterService;

class Router extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RouterService::class;
    }
}
