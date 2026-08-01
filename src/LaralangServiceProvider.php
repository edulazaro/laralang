<?php

namespace EduLazaro\Laralang;

use Illuminate\Support\ServiceProvider;
use EduLazaro\Laralang\Routing\UrlGenerator;
use EduLazaro\Laralang\Http\Middleware\ValidateLocalizedSignature;

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

        // Register signed_localized middleware alias
        $router = $this->app['router'];
        $router->aliasMiddleware('signed_localized', ValidateLocalizedSignature::class);
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\EduLazaro\Laralang\Laralang::class);

        $this->registerZiggy();
    }

    /**
     * Replace Ziggy's Blade route generator so the routes of the current
     * locale are also exposed under their unprefixed name.
     *
     * @return void
     */
    protected function registerZiggy()
    {
        if (class_exists(\Tighten\Ziggy\BladeRouteGenerator::class)) {
            $this->app->singleton(
                \Tighten\Ziggy\BladeRouteGenerator::class,
                \EduLazaro\Laralang\Routing\Ziggy2\LocalizedBladeRouteGenerator::class
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
