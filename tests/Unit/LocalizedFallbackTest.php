<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class LocalizedFallbackTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.locale', 'en');
        $app['config']->set('locales.locales', ['en', 'es', 'fr']);
        $app['config']->set('locales.prefixes', ['en' => '', 'es' => 'es', 'fr' => 'fr']);
    }

    protected function defineRoutes($router): void
    {
        LocalizedRoute::get('services', [
            'en',
            'es' => 'servicios',
            'fr' => 'services',
        ], fn () => 'ok')->name('services');

        LocalizedRoute::get('property/{slug}', [
            'en' => 'property/{slug}',
            'es' => 'propiedad/{slug}',
        ], fn ($slug) => $slug)->name('properties.show');

        // Same path in two locales, neither of them the default one.
        LocalizedRoute::get('about', [
            'es' => 'nosotros',
            'fr' => 'nosotros',
        ], fn () => 'ok')->name('about');

        LocalizedRoute::fallback();
    }

    public function test_it_rescues_a_path_that_lives_under_another_locale()
    {
        $this->get('/servicios')->assertRedirect('/es/servicios')->assertStatus(301);
    }

    public function test_it_rescues_a_path_carrying_the_wrong_prefix()
    {
        $this->get('/fr/servicios')->assertRedirect('/es/servicios')->assertStatus(301);
    }

    public function test_it_rescues_a_prefixed_path_that_belongs_to_the_default_locale()
    {
        $this->get('/es/services')->assertRedirect('/services')->assertStatus(301);
    }

    public function test_it_honours_route_parameters()
    {
        $this->get('/propiedad/piso-centro')
            ->assertRedirect('/es/propiedad/piso-centro')
            ->assertStatus(301);
    }

    public function test_it_keeps_the_query_string()
    {
        $this->get('/servicios?utm_source=x&page=2')
            ->assertRedirect('/es/servicios?utm_source=x&page=2');
    }

    public function test_it_prefers_the_default_locale_when_several_match()
    {
        // 'services' exists as /services (en) and /fr/services (fr).
        $this->get('/es/services')->assertRedirect('/services')->assertStatus(301);
    }

    public function test_it_follows_the_configured_order_when_the_default_does_not_match()
    {
        // 'nosotros' exists for es and fr only, and es comes first in the config.
        $this->get('/nosotros')->assertRedirect('/es/nosotros')->assertStatus(301);
    }

    public function test_it_still_returns_404_for_an_unknown_path()
    {
        $this->get('/this-does-not-exist')->assertNotFound();
    }

    public function test_it_leaves_requests_with_a_body_alone()
    {
        // Laravel registers fallback routes for GET only, so a POST never
        // reaches the rescue and Laravel answers by itself.
        $this->post('/servicios')->assertStatus(405);
    }

    public function test_permanent_redirects_carry_a_bounded_cache_lifetime()
    {
        config(['locales.redirect_max_age' => 3600]);

        $this->get('/servicios')->assertHeader('Cache-Control', 'max-age=3600, private');
    }

    public function test_it_is_not_registered_unless_asked_for()
    {
        $fallbacks = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->isFallback);

        $this->assertCount(1, $fallbacks, 'Only the explicitly registered fallback should exist');
    }
}
