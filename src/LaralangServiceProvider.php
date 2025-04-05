<?php

namespace EduLazaro\Laralang;

use Illuminate\Support\ServiceProvider;
use EduLazaro\Laralang\Routing\UrlGenerator;

class LaralangServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/locales.php',
            'locales'
        );
        $this->publishes([
            __DIR__.'/../config/locales.php' => config_path('locales.php'),
        ], 'locales');

        $this->registerUrlGenerator();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        if (class_exists(\Tightenco\Ziggy\BladeRouteGenerator::class)) {
            $this->app->singleton(
                \Tightenco\Ziggy\BladeRouteGenerator::class,
                \EduLazaro\Laralang\Routing\LocalizedBladeRouteGenerator::class
            );
        }
    }

    /**
     * Register the custom URL generator, replacing the default URL generator.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app->singleton('url', function ($app) {
            $generator = new UrlGenerator(
                $app['router']->getRoutes(),
                $app['request'],
                $app['config']['app.asset_url']
            );

            $app->rebinding('request', function ($app, $request) use ($generator) {
                $generator->setRequest($request);
            });

            return $generator;
        });
    }
}





