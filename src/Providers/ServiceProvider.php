<?php

namespace Bonton\Japi\Providers;

use Bonton\Japi\Services\Main;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Bonton\Japi\Services\Router;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->app->singleton(Router::class, function ($app) {
            return new Router(
                $app->get('router'),
                $app->get(Main::class)
            );
        });

        $this->app->singleton(Main::class, function ($app) {
            return new Main($app, $app->get('config')['japi']);
        });
    }
}
