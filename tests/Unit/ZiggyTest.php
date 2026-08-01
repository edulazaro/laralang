<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Routing\Ziggy2\LocalizedBladeRouteGenerator;
use EduLazaro\Laralang\Routing\Ziggy2\LocalizedZiggy;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\BladeRouteGenerator;
use Tighten\Ziggy\Ziggy;

class ZiggyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Ziggy::clearRoutes();
        BladeRouteGenerator::$generated = false;
    }

    protected function tearDown(): void
    {
        Ziggy::clearRoutes();
        BladeRouteGenerator::$generated = false;

        parent::tearDown();
    }

    protected function registerRoutes(): void
    {
        LocalizedRoute::get('dashboard', [
            'en',
            'es' => 'panel',
        ], fn () => 'ok')->name('dashboard');

        Route::get('plain', fn () => 'ok')->name('plain');

        Ziggy::clearRoutes();
    }

    public function test_the_generator_is_resolved_from_the_container()
    {
        $this->assertInstanceOf(
            LocalizedBladeRouteGenerator::class,
            app(BladeRouteGenerator::class)
        );
    }

    public function test_it_exposes_current_locale_routes_without_the_prefix()
    {
        $this->registerRoutes();

        app()->setLocale('es');

        $routes = (new LocalizedZiggy)->toArray()['routes'];

        $this->assertArrayHasKey('es.dashboard', $routes);
        $this->assertArrayHasKey('dashboard', $routes);
        $this->assertSame('es/panel', $routes['dashboard']['uri']);
        $this->assertSame($routes['es.dashboard'], $routes['dashboard']);
    }

    public function test_the_alias_follows_the_active_locale()
    {
        $this->registerRoutes();

        app()->setLocale('en');

        $routes = (new LocalizedZiggy)->toArray()['routes'];

        $this->assertSame('dashboard', $routes['dashboard']['uri']);
    }

    public function test_it_does_not_overwrite_an_existing_unprefixed_route()
    {
        LocalizedRoute::get('plain', [
            'en',
            'es' => 'llano',
        ], fn () => 'ok')->name('plain');

        Route::get('plain-original', fn () => 'ok')->name('plain');

        Ziggy::clearRoutes();

        app()->setLocale('es');

        $routes = (new LocalizedZiggy)->toArray()['routes'];

        $this->assertSame('plain-original', $routes['plain']['uri']);
    }

    public function test_the_generated_script_contains_the_aliased_name()
    {
        $this->registerRoutes();

        app()->setLocale('es');

        $script = app(BladeRouteGenerator::class)->generate();

        $this->assertStringContainsString('"es.dashboard"', $script);
        $this->assertStringContainsString('"dashboard"', $script);
        $this->assertStringContainsString('const Ziggy', $script);
    }

    public function test_json_output_still_works()
    {
        $this->registerRoutes();

        app()->setLocale('es');

        $output = app(BladeRouteGenerator::class)->generate(null, null, true);

        $this->assertStringContainsString('id="ziggy-routes-json"', $output);

        preg_match('/<script id="ziggy-routes-json" type="application\/json">(.*)<\/script>/s', $output, $matches);

        $payload = json_decode($matches[1], true);

        $this->assertArrayHasKey('dashboard', $payload['routes']);
        $this->assertArrayHasKey('es.dashboard', $payload['routes']);
    }
}
