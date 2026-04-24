<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\Http\Middleware\SetBrowserLocale;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;
use EduLazaro\Laralang\Http\Middleware\SetSessionLocale;
use EduLazaro\Laralang\Http\Middleware\SetSmartLocale;
use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class MiddlewareIdempotencyTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
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

    protected function registerFooRoute(): void
    {
        $route = LocalizedRoute::get('foo', [
            'en',
            'es' => 'foo',
            'ca' => 'foo',
        ], fn () => 'ok')->name('foo');
        $this->registerLocalizedRoute($route);
    }

    /** @test */
    public function it_resolves_locale_from_prefixed_url_on_single_application()
    {
        $this->registerFooRoute();

        $response = $this->get('/ca/foo');

        $response->assertOk();
        $this->assertSame('ca', App::getLocale());
    }

    /** @test */
    public function it_does_not_double_redirect_when_smart_and_route_middleware_stack()
    {
        Route::middleware([SetSmartLocale::class])->group(function () {
            $route = LocalizedRoute::get('foo', [
                'en',
                'es' => 'foo',
                'ca' => 'foo',
            ], fn () => 'ok')->name('foo');
            $this->registerLocalizedRoute($route);
        });

        $response = $this->get('/ca/foo');

        $response->assertOk();
        $response->assertStatus(200);
        $this->assertSame('ca', App::getLocale());
    }

    /** @test */
    public function it_still_redirects_when_url_uses_default_locale_prefix()
    {
        // Default locale is 'en' here and has no prefix. Register an es-prefixed
        // route for /es/foo so /en/foo can fall through to the redirect path.
        config()->set('app.locale', 'en');

        $route = LocalizedRoute::get('foo', [
            'en',
            'es' => 'foo',
        ], fn () => 'ok')->name('foo');
        $this->registerLocalizedRoute($route);

        // Add a bare route at /en/foo so routing doesn't 404 before middleware runs.
        Route::get('en/foo', fn () => 'ok')
            ->middleware(SetRouteLocale::class)
            ->name('en.foo.literal');

        $response = $this->get('/en/foo');

        $response->assertStatus(301);
        $location = $response->headers->get('Location');
        $this->assertStringEndsWith('/foo', $location);
        $this->assertStringNotContainsString('/en/foo', $location);
    }

    /** @test */
    public function it_is_a_noop_when_set_route_locale_runs_twice()
    {
        session()->put('locale', 'ca');
        App::setLocale('en');

        $request = Request::create('/ca/foo', 'GET');
        $middleware = new SetRouteLocale();

        $calls = 0;
        $next = function ($req) use (&$calls) {
            $calls++;
            return response('ok');
        };

        $middleware->handle($request, $next);
        $firstLocale = App::getLocale();

        App::setLocale('en');

        $middleware->handle($request, $next);
        $secondLocale = App::getLocale();

        $this->assertSame('ca', $firstLocale);
        $this->assertSame('en', $secondLocale, 'Second invocation must not re-set the locale.');
        $this->assertSame(2, $calls);
    }

    /** @test */
    public function it_short_circuits_across_middleware_classes()
    {
        session()->put('locale', 'es');
        App::setLocale('en');

        $request = Request::create('/', 'GET');

        $browser = new SetBrowserLocale();
        $route = new SetRouteLocale();

        $next = fn ($req) => response('ok');

        $browser->handle($request, $next);
        $afterBrowser = App::getLocale();

        App::setLocale('en');

        $route->handle($request, $next);
        $afterRoute = App::getLocale();

        $this->assertSame('es', $afterBrowser);
        $this->assertSame('en', $afterRoute, 'Second middleware must early-return without touching locale.');
        $this->assertTrue($request->attributes->get(SetRouteLocale::RESOLVED_FLAG));
    }

    /** @test */
    public function it_exposes_a_stable_public_flag_constant()
    {
        $this->assertSame('laralang.locale_resolved', SetRouteLocale::RESOLVED_FLAG);

        session()->put('locale', 'ca');
        $request = Request::create('/', 'GET');

        (new SetSessionLocale())->handle($request, fn ($req) => response('ok'));

        $this->assertTrue($request->attributes->has(SetRouteLocale::RESOLVED_FLAG));
    }
}
