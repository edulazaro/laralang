<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class PrefixedDefaultLocaleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.locale', 'en');
        $app['config']->set('locales.locales', ['en', 'es']);
        // Every locale prefixed, the default one included.
        $app['config']->set('locales.prefixes', ['en' => 'en', 'es' => 'es']);
    }

    protected function defineRoutes($router): void
    {
        LocalizedRoute::get('about', [
            'en',
            'es' => 'nosotros',
        ], fn () => 'ok')->name('about');

        LocalizedRoute::fallback();
    }

    public function test_every_locale_is_registered_under_its_prefix()
    {
        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertSame('en/about', $routes->getByName('en.about')->uri());
        $this->assertSame('es/nosotros', $routes->getByName('es.about')->uri());
    }

    public function test_the_default_locale_prefix_is_no_longer_stripped()
    {
        $this->get('/en/about')->assertOk()->assertSee('ok');
    }

    public function test_route_helper_points_at_the_prefixed_url()
    {
        $this->assertSame('http://localhost/en/about', route('about'));

        $this->app->setLocale('es');

        $this->assertSame('http://localhost/es/nosotros', route('about'));
    }

    public function test_the_root_goes_to_the_language_of_the_visitor()
    {
        $this->get('/', ['Accept-Language' => 'es-ES,es;q=0.9'])
            ->assertRedirect('/es')
            ->assertStatus(302)
            ->assertHeader('Vary', 'Accept-Language');
    }

    public function test_the_root_falls_back_to_the_default_language()
    {
        $this->get('/', ['Accept-Language' => 'de-DE,de;q=0.9'])
            ->assertRedirect('/en')
            ->assertStatus(302);
    }

    public function test_the_root_honours_a_stored_choice()
    {
        $this->withSession(['locale' => 'es'])
            ->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])
            ->assertRedirect('/es');
    }

    public function test_an_unprefixed_url_is_still_rescued()
    {
        $this->get('/nosotros')->assertRedirect('/es/nosotros')->assertStatus(301);
    }
}
