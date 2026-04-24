<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\Facades\Laralang;
use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class AlternatesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('locales.locales', ['en', 'es', 'ca']);
        $app['config']->set('locales.prefixes', [
            'en' => '',
            'es' => 'es',
            'ca' => 'ca',
        ]);
    }

    protected function registerLocalizedRoute(LocalizedRoute $localizedRoute): void
    {
        foreach ($localizedRoute->getRoutes() as $route) {
            Route::getRoutes()->add($route);
        }
    }

    protected function bindCurrentRequestToRoute(string $name, array $params = []): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route '{$name}' was not registered");

        $uri = $route->uri();
        foreach ($params as $key => $value) {
            $uri = str_replace('{'.$key.'}', (string) $value, $uri);
        }

        $request = \Illuminate\Http\Request::create('/'.ltrim($uri, '/'), 'GET');
        $route->bind($request);

        foreach ($params as $key => $value) {
            $route->setParameter($key, $value);
        }

        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);
    }

    /** @test */
    public function it_returns_urls_keyed_by_every_configured_locale()
    {
        $services = LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
            'ca' => 'serveis',
        ], fn () => 'ok')->name('services');
        $this->registerLocalizedRoute($services);

        $this->bindCurrentRequestToRoute('es.services');

        $result = Laralang::alternates();

        $this->assertSame(['en', 'es', 'ca'], array_keys($result));
        $this->assertStringEndsWith('/services', $result['en']);
        $this->assertStringEndsWith('/es/servicios', $result['es']);
        $this->assertStringEndsWith('/ca/serveis', $result['ca']);
    }

    /** @test */
    public function it_honours_the_order_of_configured_locales()
    {
        config()->set('locales.locales', ['ca', 'en', 'es']);

        $services = LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
            'ca' => 'serveis',
        ], fn () => 'ok')->name('services');
        $this->registerLocalizedRoute($services);

        $this->bindCurrentRequestToRoute('ca.services');

        $result = Laralang::alternates();

        $this->assertSame(['ca', 'en', 'es'], array_keys($result));
    }

    /** @test */
    public function it_omits_locales_not_declared_on_the_route()
    {
        $services = LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
        ], fn () => 'ok')->name('services');
        $this->registerLocalizedRoute($services);

        $this->bindCurrentRequestToRoute('es.services');

        $result = Laralang::alternates();

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('es', $result);
        $this->assertArrayNotHasKey('ca', $result);
    }

    /** @test */
    public function it_uses_current_route_params_and_supports_overrides()
    {
        $post = LocalizedRoute::get('posts/{slug}', [
            'en',
            'es' => 'articulos/{slug}',
            'ca' => 'articles/{slug}',
        ], fn () => 'ok')->name('posts.show');
        $this->registerLocalizedRoute($post);

        $this->bindCurrentRequestToRoute('es.posts.show', ['slug' => 'hola']);

        $result = Laralang::alternates();

        foreach (['en', 'es', 'ca'] as $locale) {
            $this->assertStringContainsString('hola', $result[$locale]);
        }

        $overridden = Laralang::alternates(['slug' => 'custom']);

        foreach (['en', 'es', 'ca'] as $locale) {
            $this->assertStringContainsString('custom', $overridden[$locale]);
            $this->assertStringNotContainsString('hola', $overridden[$locale]);
        }
    }

    /** @test */
    public function it_returns_relative_paths_when_absolute_is_false()
    {
        $services = LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
            'ca' => 'serveis',
        ], fn () => 'ok')->name('services');
        $this->registerLocalizedRoute($services);

        $this->bindCurrentRequestToRoute('es.services');

        $result = Laralang::alternates([], false);

        foreach ($result as $url) {
            $this->assertStringStartsNotWith('http://', $url);
            $this->assertStringStartsNotWith('https://', $url);
        }

        $this->assertSame('/services', $result['en']);
        $this->assertSame('/es/servicios', $result['es']);
        $this->assertSame('/ca/serveis', $result['ca']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_route_is_bound()
    {
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $this->app->instance('request', $request);

        $this->assertSame([], Laralang::alternates());
    }

    /** @test */
    public function it_returns_current_locale_and_current_url_for_plain_non_localized_routes()
    {
        $route = new \Illuminate\Routing\Route(['GET'], 'foo', fn () => 'ok');
        $route->name('foo');
        Route::getRoutes()->add($route);

        $request = \Illuminate\Http\Request::create('/foo', 'GET');
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        app()->setLocale('en');

        $result = Laralang::alternates();

        $this->assertSame(['en'], array_keys($result));
        $this->assertStringEndsWith('/foo', $result['en']);
    }

    /** @test */
    public function it_returns_empty_array_for_routes_with_null_name()
    {
        $route = new \Illuminate\Routing\Route(['GET'], 'foo', fn () => 'ok');
        Route::getRoutes()->add($route);

        $request = \Illuminate\Http\Request::create('/foo', 'GET');
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $this->assertSame([], Laralang::alternates());
    }

    /** @test */
    public function it_handles_naming_collision_when_two_locales_share_a_uri()
    {
        // 'es' and 'ca' both default to the base URI 'foo' (no custom translation).
        // The resulting URLs must still differ thanks to the locale prefix on ca.
        $route = LocalizedRoute::get('foo', [
            'es',
            'ca',
        ], fn () => 'ok')->name('foo');
        $this->registerLocalizedRoute($route);

        $this->bindCurrentRequestToRoute('es.foo');

        $result = Laralang::alternates();

        $this->assertArrayHasKey('es', $result);
        $this->assertArrayHasKey('ca', $result);
        $this->assertStringEndsWith('/es/foo', $result['es']);
        $this->assertStringEndsWith('/ca/foo', $result['ca']);
        $this->assertNotSame($result['es'], $result['ca']);
    }

    /** @test */
    public function it_is_callable_via_the_global_helper_function()
    {
        $services = LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
            'ca' => 'serveis',
        ], fn () => 'ok')->name('services');
        $this->registerLocalizedRoute($services);

        $this->bindCurrentRequestToRoute('es.services');

        $result = laralang_alternates();

        $this->assertSame(['en', 'es', 'ca'], array_keys($result));
    }
}
