<?php

namespace EduLazaro\Laralang\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use EduLazaro\Laralang\LaralangServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaralangServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('locales.locales', ['en', 'es', 'fr']);
        $app['config']->set('locales.prefixes', [
            'es' => 'es',
        ]);
    }
}
