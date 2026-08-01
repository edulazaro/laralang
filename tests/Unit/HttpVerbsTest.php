<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class HttpVerbsTest extends TestCase
{
    public static function verbProvider(): array
    {
        return [
            'get' => ['get', ['GET', 'HEAD']],
            'post' => ['post', ['POST']],
            'put' => ['put', ['PUT']],
            'patch' => ['patch', ['PATCH']],
            'delete' => ['delete', ['DELETE']],
            'options' => ['options', ['OPTIONS']],
            'any' => ['any', ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']],
        ];
    }

    /**
     * @dataProvider verbProvider
     */
    public function test_it_registers_one_route_per_locale_for_every_verb(string $verb, array $expectedMethods)
    {
        LocalizedRoute::{$verb}('profile', [
            'en',
            'es' => 'perfil',
        ], fn () => 'ok')->name('profile');

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $english = $routes->getByName('en.profile');
        $spanish = $routes->getByName('es.profile');

        $this->assertNotNull($english, "en.profile was not registered for {$verb}");
        $this->assertNotNull($spanish, "es.profile was not registered for {$verb}");

        $this->assertEquals('profile', $english->uri());
        $this->assertEquals('es/perfil', $spanish->uri());

        $this->assertEqualsCanonicalizing($expectedMethods, $english->methods());
        $this->assertEqualsCanonicalizing($expectedMethods, $spanish->methods());
    }

    public function test_match_registers_the_given_methods()
    {
        LocalizedRoute::match(['PUT', 'PATCH'], 'profile', [
            'en',
            'es' => 'perfil',
        ], fn () => 'ok')->name('profile');

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertEqualsCanonicalizing(['PUT', 'PATCH'], $routes->getByName('en.profile')->methods());
        $this->assertEqualsCanonicalizing(['PUT', 'PATCH'], $routes->getByName('es.profile')->methods());
    }

    public function test_any_registers_one_route_per_locale_answering_every_verb()
    {
        $before = count(Route::getRoutes()->getRoutes());

        LocalizedRoute::any('webhook', [
            'en',
            'es' => 'gancho',
        ], fn () => 'ok')->name('webhook');

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertCount(2, array_slice($routes->getRoutes(), $before));

        $this->assertEquals('webhook', $routes->getByName('en.webhook')->uri());
        $this->assertEquals('es/gancho', $routes->getByName('es.webhook')->uri());

        $this->assertEqualsCanonicalizing(Router::$verbs, $routes->getByName('es.webhook')->methods());
    }

    public function test_any_follows_laravel_verb_list()
    {
        LocalizedRoute::any('webhook', ['en'], fn () => 'ok')->name('webhook');

        Route::getRoutes()->refreshNameLookups();

        $this->assertEqualsCanonicalizing(
            Router::$verbs,
            Route::getRoutes()->getByName('en.webhook')->methods()
        );
    }

    public function test_get_routes_also_answer_head()
    {
        LocalizedRoute::get('profile', ['en', 'es' => 'perfil'], fn () => 'ok')->name('profile');

        Route::getRoutes()->refreshNameLookups();

        $this->assertContains('HEAD', Route::getRoutes()->getByName('es.profile')->methods());
    }

    public function test_chained_methods_apply_to_every_generated_route()
    {
        LocalizedRoute::put('profile', ['en', 'es' => 'perfil'], fn () => 'ok')
            ->middleware('auth')
            ->name('profile');

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();

        $this->assertContains('auth', $routes->getByName('en.profile')->gatherMiddleware());
        $this->assertContains('auth', $routes->getByName('es.profile')->gatherMiddleware());
    }
}
