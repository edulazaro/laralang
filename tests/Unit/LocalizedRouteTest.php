<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class LocalizedRouteTest extends TestCase
{
    protected function registerLocalizedRoute(LocalizedRoute $localizedRoute)
    {
        foreach ($localizedRoute->getRoutes() as $route) {
            Route::getRoutes()->add($route);
        }
    }

    public function test_it_generates_localized_routes()
    {
        $localizedRoute = LocalizedRoute::get('profile', [
            'en',
            'es' => 'perfil',
            'fr' => 'profil'
        ], fn () => 'ok')->name('profile.edit');

        $routes = $localizedRoute->getRoutes();

        foreach( $routes as  $route) {
            Route::getRoutes()->add($route);
        }

        $routes = Route::getRoutes();

        $this->assertNotNull($routes->getByName('en.profile.edit'));
        $this->assertNotNull($routes->getByName('es.profile.edit'));
        $this->assertNotNull($routes->getByName('fr.profile.edit'));

        $this->assertEquals('profile', $routes->getByName('en.profile.edit')->uri());
        $this->assertEquals('es/perfil', $routes->getByName('es.profile.edit')->uri());
        $this->assertEquals('fr/profil', $routes->getByName('fr.profile.edit')->uri());
    }

    public function test_it_uses_base_uri_when_no_custom_translation_is_provided()
    {
        $localizedRoute = LocalizedRoute::get('profile', [
            'en',
            'es' => 'perfil',
            'fr'
        ], fn () => 'ok')->name('profile.edit');

        $routes = $localizedRoute->getRoutes();

        foreach ($routes as $route) {
            Route::getRoutes()->add($route);
        }

        $routes = Route::getRoutes();

        $this->assertEquals('fr/profile', $routes->getByName('fr.profile.edit')->uri());
    }

    public function test_it_respects_group_prefix()
    {
        Route::prefix('admin')->group(function () {
            $localizedRoute = LocalizedRoute::get('dashboard', [
                'en',
                'es' => 'tablero'
            ], fn () => 'ok')->name('dashboard');

            $this->registerLocalizedRoute($localizedRoute);
        });

        $routes = Route::getRoutes();

        $this->assertEquals('admin/dashboard', $routes->getByName('en.dashboard')->uri());
        $this->assertEquals('es/admin/tablero', $routes->getByName('es.dashboard')->uri());
    }
}
