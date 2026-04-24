<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class RouteNamedTest extends TestCase
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

    protected function setCurrentRouteByName(?string $name): void
    {
        $router = app('router');

        if ($name === null) {
            $reflection = new \ReflectionClass($router);
            $property = $reflection->getProperty('current');
            $property->setAccessible(true);
            $property->setValue($router, null);
            return;
        }

        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route '{$name}' was not registered");

        $reflection = new \ReflectionClass($router);
        $property = $reflection->getProperty('current');
        $property->setAccessible(true);
        $property->setValue($router, $route);
    }

    protected function registerServices(): void
    {
        $services = LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
            'ca' => 'serveis',
        ], fn () => 'ok')->name('services');
        $this->registerLocalizedRoute($services);
    }

    protected function registerEmpresas(): void
    {
        $empresas = LocalizedRoute::get('companies', [
            'en' => 'companies',
            'es' => 'empresas',
            'ca' => 'empreses',
        ], fn () => 'ok')->name('empresas');
        $this->registerLocalizedRoute($empresas);
    }

    protected function registerOffers(): void
    {
        $offers = LocalizedRoute::get('offers', [
            'en',
            'es' => 'ofertas',
            'ca' => 'ofertes',
        ], fn () => 'ok')->name('offers');
        $this->registerLocalizedRoute($offers);
    }

    protected function registerServicesShow(): void
    {
        $show = LocalizedRoute::get('services/{id}', [
            'en',
            'es' => 'servicios/{id}',
            'ca' => 'serveis/{id}',
        ], fn () => 'ok')->name('services.show');
        $this->registerLocalizedRoute($show);
    }

    protected function registerServicesCreate(): void
    {
        $create = LocalizedRoute::get('services/create', [
            'en',
            'es' => 'servicios/crear',
            'ca' => 'serveis/crear',
        ], fn () => 'ok')->name('services.create');
        $this->registerLocalizedRoute($create);
    }

    // ---------------------------------------------------------------
    // Plain pattern matches on any locale variant
    // ---------------------------------------------------------------

    public function test_plain_pattern_matches_on_es_variant()
    {
        $this->registerServices();
        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');

        $this->assertTrue(Route::is('services'));
    }

    public function test_plain_pattern_matches_on_ca_variant()
    {
        $this->registerServices();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');

        $this->assertTrue(Route::is('services'));
    }

    public function test_plain_pattern_matches_on_en_variant()
    {
        $this->registerServices();
        App::setLocale('en');
        $this->setCurrentRouteByName('en.services');

        $this->assertTrue(Route::is('services'));
    }

    public function test_plain_pattern_does_not_match_unrelated_route()
    {
        $this->registerServices();
        $this->registerEmpresas();

        App::setLocale('es');
        $this->setCurrentRouteByName('es.empresas');

        $this->assertFalse(Route::is('services'));
    }

    // ---------------------------------------------------------------
    // Locale-prefixed pattern requires current locale
    // ---------------------------------------------------------------

    public function test_es_prefix_matches_on_es_variant_with_es_locale()
    {
        $this->registerServices();
        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');

        $this->assertTrue(Route::is('es.services'));
    }

    public function test_es_prefix_does_not_match_on_ca_variant_with_ca_locale()
    {
        $this->registerServices();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');

        $this->assertFalse(Route::is('es.services'));
    }

    public function test_ca_prefix_matches_on_ca_variant_with_ca_locale()
    {
        $this->registerServices();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');

        $this->assertTrue(Route::is('ca.services'));
    }

    public function test_en_prefix_does_not_match_on_es_variant()
    {
        $this->registerServices();
        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');

        $this->assertFalse(Route::is('en.services'));
    }

    public function test_es_prefix_returns_false_when_manual_locale_override_mismatches_route_name()
    {
        // Subtle case: route name is es.services but app locale was forced to 'ca'.
        // Match must depend on current locale, not only on the route name.
        // This documents the intentional breaking change vs 1.x behaviour.
        $this->registerServices();
        $this->setCurrentRouteByName('es.services');
        App::setLocale('ca');

        $this->assertFalse(Route::is('es.services'));
    }

    // ---------------------------------------------------------------
    // Suffix wildcards still work
    // ---------------------------------------------------------------

    public function test_suffix_wildcard_matches_on_ca_services_show()
    {
        $this->registerServicesShow();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services.show');

        $this->assertTrue(Route::is('services.*'));
    }

    public function test_suffix_wildcard_matches_on_en_services_create()
    {
        $this->registerServicesCreate();
        App::setLocale('en');
        $this->setCurrentRouteByName('en.services.create');

        $this->assertTrue(Route::is('services*'));
    }

    public function test_suffix_wildcard_does_not_match_unrelated_route()
    {
        $this->registerEmpresas();
        App::setLocale('es');
        $this->setCurrentRouteByName('es.empresas');

        $this->assertFalse(Route::is('services.*'));
    }

    // ---------------------------------------------------------------
    // Prefix wildcards (1.x workaround) stay backwards compatible
    // ---------------------------------------------------------------

    public function test_legacy_prefix_wildcard_workaround_still_matches()
    {
        // "*" is not a valid locale, so this pattern falls through the plain path
        // and is matched via Str::is('*.services', 'ca.services').
        $this->registerServices();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');

        $this->assertTrue(Route::is('*.services'));
    }

    public function test_double_wildcard_matches_any_localized_route()
    {
        $this->registerServices();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');

        $this->assertTrue(Route::is('*.*'));
    }

    // ---------------------------------------------------------------
    // Explicit locale + wildcard
    // ---------------------------------------------------------------

    public function test_locale_prefixed_wildcard_matches_on_matching_locale()
    {
        $this->registerServicesShow();
        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services.show');

        $this->assertTrue(Route::is('ca.services*'));
    }

    public function test_locale_prefixed_wildcard_does_not_match_when_locale_differs()
    {
        $this->registerEmpresas();
        App::setLocale('es');
        $this->setCurrentRouteByName('es.empresas');

        $this->assertFalse(Route::is('ca.*'));
    }

    // ---------------------------------------------------------------
    // OR semantics with multiple patterns
    // ---------------------------------------------------------------

    public function test_multiple_patterns_second_plain_matches()
    {
        $this->registerServices();
        $this->registerOffers();

        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.offers');

        $this->assertTrue(Route::is('services', 'offers'));
    }

    public function test_multiple_patterns_nonexistent_plus_plain()
    {
        $this->registerServices();

        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');

        $this->assertTrue(Route::is('nonexistent', 'services'));
    }

    public function test_multiple_locale_prefixed_patterns_one_matches()
    {
        $this->registerServices();
        $this->registerEmpresas();

        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.empresas');

        $this->assertTrue(Route::is('es.services', 'ca.empresas'));
    }

    public function test_multiple_locale_prefixed_patterns_none_match_current_locale()
    {
        $this->registerServices();

        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');

        $this->assertFalse(Route::is('es.services', 'en.empresas'));
    }

    // ---------------------------------------------------------------
    // Sanity: plain Laravel routes stay untouched
    // ---------------------------------------------------------------

    public function test_plain_laravel_route_matches_with_route_is()
    {
        $route = new \Illuminate\Routing\Route(['GET'], 'foo', fn () => 'ok');
        $route->name('foo');
        Route::getRoutes()->add($route);

        $this->setCurrentRouteByName('foo');

        $this->assertInstanceOf(\Illuminate\Routing\Route::class, Route::getRoutes()->getByName('foo'));
        $this->assertNotInstanceOf(\EduLazaro\Laralang\Routing\Route::class, Route::getRoutes()->getByName('foo'));
        $this->assertTrue(Route::is('foo'));
    }

    public function test_plain_laravel_route_does_not_match_unrelated_pattern()
    {
        $route = new \Illuminate\Routing\Route(['GET'], 'foo', fn () => 'ok');
        $route->name('foo');
        Route::getRoutes()->add($route);

        $this->setCurrentRouteByName('foo');

        $this->assertFalse(Route::is('bar'));
    }

    // ---------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------

    public function test_returns_false_when_no_current_route()
    {
        $this->registerServices();
        $this->setCurrentRouteByName(null);

        $this->assertFalse(Route::is('services'));
    }

    public function test_returns_false_when_current_route_has_no_name()
    {
        $route = Route::get('/', fn () => 'ok');
        $router = app('router');
        $reflection = new \ReflectionClass($router);
        $property = $reflection->getProperty('current');
        $property->setAccessible(true);
        $property->setValue($router, $route);

        $this->assertFalse(Route::is('anything'));
    }

    public function test_route_is_with_no_arguments_returns_false()
    {
        $this->registerServices();
        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');

        $this->assertFalse(Route::is());
    }

    public function test_does_not_error_when_locales_config_is_empty()
    {
        config()->set('locales.locales', []);
        $this->registerServices();

        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');

        // Without any configured locales the plain path can't expand, so only a
        // literal match on 'services' is attempted — which fails — but the call
        // must still complete without blowing up.
        $this->assertFalse(Route::is('services'));
    }

    // ---------------------------------------------------------------
    // Historical bug regression
    // ---------------------------------------------------------------

    public function test_historical_bug_plain_services_now_matches_localized_variant()
    {
        // Before this change routeIs('services') never matched LocalizedRoute-generated
        // names like es.services / ca.services / en.services, which silently broke
        // active-state checks in navigation. Users worked around it with '*.services'.
        $this->registerServices();

        App::setLocale('es');
        $this->setCurrentRouteByName('es.services');
        $this->assertTrue(Route::is('services'));

        App::setLocale('ca');
        $this->setCurrentRouteByName('ca.services');
        $this->assertTrue(Route::is('services'));

        App::setLocale('en');
        $this->setCurrentRouteByName('en.services');
        $this->assertTrue(Route::is('services'));
    }
}
