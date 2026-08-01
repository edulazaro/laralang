<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedResource;
use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class LocalizedResourceTest extends TestCase
{
    protected function routes()
    {
        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        return $routes;
    }

    protected function uri(string $name): ?string
    {
        $route = $this->routes()->getByName($name);

        return $route ? $route->uri() : null;
    }

    public function test_it_registers_the_seven_routes_per_locale()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $method) {
            $this->assertNotNull($this->routes()->getByName("en.photos.{$method}"), "missing en.photos.{$method}");
            $this->assertNotNull($this->routes()->getByName("es.photos.{$method}"), "missing es.photos.{$method}");
        }
    }

    public function test_it_translates_the_resource_and_prefixes_the_locale()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        $this->assertEquals('photos', $this->uri('en.photos.index'));
        $this->assertEquals('photos/{photo}', $this->uri('en.photos.show'));

        $this->assertEquals('es/fotos', $this->uri('es.photos.index'));
        $this->assertEquals('es/fotos/{photo}', $this->uri('es.photos.show'));
    }

    public function test_the_route_parameter_is_the_same_in_every_locale()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        $this->assertEquals(['photo'], $this->routes()->getByName('es.photos.show')->parameterNames());
        $this->assertEquals(['photo'], $this->routes()->getByName('en.photos.show')->parameterNames());
    }

    public function test_it_translates_the_create_and_edit_segments()
    {
        LocalizedResource::make('photos', [
            'en',
            'es' => ['uri' => 'fotos', 'verbs' => ['create' => 'crear', 'edit' => 'editar']],
        ], 'PhotoController');

        $this->assertEquals('es/fotos/crear', $this->uri('es.photos.create'));
        $this->assertEquals('es/fotos/{photo}/editar', $this->uri('es.photos.edit'));

        $this->assertEquals('photos/create', $this->uri('en.photos.create'));
        $this->assertEquals('photos/{photo}/edit', $this->uri('en.photos.edit'));
    }

    public function test_the_translated_verbs_do_not_leak_into_other_locales_or_resources()
    {
        LocalizedResource::make('photos', [
            'en',
            'es' => ['uri' => 'fotos', 'verbs' => ['create' => 'crear', 'edit' => 'editar']],
        ], 'PhotoController');

        LocalizedResource::make('videos', ['en', 'es' => 'videos'], 'VideoController');

        $this->assertEquals('es/videos/create', $this->uri('es.videos.create'));
    }

    public function test_only_is_forwarded_to_every_locale()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController')
            ->only(['index', 'show']);

        $this->assertNotNull($this->routes()->getByName('es.photos.index'));
        $this->assertNotNull($this->routes()->getByName('es.photos.show'));
        $this->assertNull($this->routes()->getByName('es.photos.create'));
        $this->assertNull($this->routes()->getByName('en.photos.destroy'));
    }

    public function test_except_is_forwarded_to_every_locale()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController')
            ->except(['destroy']);

        $this->assertNotNull($this->routes()->getByName('es.photos.index'));
        $this->assertNull($this->routes()->getByName('es.photos.destroy'));
        $this->assertNull($this->routes()->getByName('en.photos.destroy'));
    }

    public function test_middleware_is_forwarded_to_every_locale()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController')
            ->middleware('auth');

        $this->assertContains('auth', $this->routes()->getByName('es.photos.index')->gatherMiddleware());
        $this->assertContains('auth', $this->routes()->getByName('en.photos.index')->gatherMiddleware());
    }

    public function test_every_route_carries_the_locale_middleware()
    {
        LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        $this->assertContains(SetRouteLocale::class, $this->routes()->getByName('es.photos.index')->gatherMiddleware());
        $this->assertContains(SetRouteLocale::class, $this->routes()->getByName('en.photos.index')->gatherMiddleware());
    }

    public function test_api_resources_have_no_create_or_edit()
    {
        LocalizedResource::api('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        $this->assertNotNull($this->routes()->getByName('es.photos.index'));
        $this->assertNull($this->routes()->getByName('es.photos.create'));
        $this->assertNull($this->routes()->getByName('es.photos.edit'));
    }

    public function test_the_locale_prefix_goes_before_the_group_prefix()
    {
        Route::prefix('admin')->group(function () {
            LocalizedResource::make('photos', ['en', 'es' => 'fotos'], 'PhotoController');
        });

        $this->assertEquals('admin/photos', $this->uri('en.photos.index'));
        $this->assertEquals('es/admin/fotos', $this->uri('es.photos.index'));
    }

    public function test_the_localized_route_bridge_does_the_same()
    {
        LocalizedRoute::resource('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        $this->assertEquals('es/fotos', $this->uri('es.photos.index'));
        $this->assertEquals('photos', $this->uri('en.photos.index'));
    }

    public function test_the_api_resource_bridge_does_the_same()
    {
        LocalizedRoute::apiResource('photos', ['en', 'es' => 'fotos'], 'PhotoController');

        $this->assertNotNull($this->routes()->getByName('es.photos.index'));
        $this->assertNull($this->routes()->getByName('es.photos.create'));
    }
}
