<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
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
