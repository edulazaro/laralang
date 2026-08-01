<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Routing\LocalePrefixes;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class LocalePrefixesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.locale', 'en');
        $app['config']->set('locales.locales', ['en', 'es', 'fr']);
    }

    protected function prefixes(array $prefixes): void
    {
        config(['locales.prefixes' => $prefixes]);
    }

    public function test_a_value_is_the_literal_prefix()
    {
        $this->prefixes(['es' => 'esp']);

        $this->assertSame('esp', LocalePrefixes::get('es'));
    }

    public function test_without_a_value_the_locale_code_is_used()
    {
        $this->prefixes([]);

        $this->assertSame('fr', LocalePrefixes::get('fr'));
        $this->assertSame('es', LocalePrefixes::get('es'));
    }

    public function test_null_uses_the_locale_code_too()
    {
        $this->prefixes(['fr' => null]);

        $this->assertSame('fr', LocalePrefixes::get('fr'));
    }

    public function test_an_empty_string_behaves_like_no_value()
    {
        $this->prefixes(['fr' => '']);

        $this->assertSame('fr', LocalePrefixes::get('fr'));
    }

    public function test_the_default_locale_never_carries_a_prefix()
    {
        $this->prefixes(['en' => '']);
        $this->assertSame('', LocalePrefixes::get('en'));

        $this->prefixes(['en' => null]);
        $this->assertSame('', LocalePrefixes::get('en'));

        $this->prefixes([]);
        $this->assertSame('', LocalePrefixes::get('en'));
    }

    public function test_only_the_default_locale_can_live_at_the_root()
    {
        // The config that used to drop a route: two locales with no prefix.
        $this->prefixes(['en' => '', 'es' => 'es', 'fr' => '']);

        LocalizedRoute::get('about', ['en', 'es' => 'nosotros', 'fr'], fn () => 'ok')->name('about');

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertSame('about', $routes->getByName('en.about')->uri());
        $this->assertSame('es/nosotros', $routes->getByName('es.about')->uri());
        $this->assertSame('fr/about', $routes->getByName('fr.about')->uri());
    }

    public function test_every_configured_locale_is_resolved()
    {
        $this->prefixes(['es' => 'esp', 'fr' => '']);

        $this->assertSame(
            ['en' => '', 'es' => 'esp', 'fr' => 'fr'],
            LocalePrefixes::all()
        );
    }
}
