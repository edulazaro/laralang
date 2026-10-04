<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\LocalizedRoute;
use EduLazaro\Laralang\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;

class InvokableActionTest extends TestCase
{
    /**
     * Every localized route knows its router.
     *
     * Without it, anything asking the route for the group stack fails with "Call to a
     * member function getGroupStack() on null". Matching a request does not need it, which
     * is why plain requests worked, but Livewire's page components resolve their bindings
     * through it and broke on the first visit.
     */
    public function test_every_localized_route_has_its_router()
    {
        LocalizedRoute::get('hello', ['en', 'es' => 'hola'], InvokableHello::class)->name('hello');

        $routes = Route::getRoutes();
        $routes->refreshNameLookups();
        $router = new ReflectionProperty(\Illuminate\Routing\Route::class, 'router');

        foreach (['en.hello', 'es.hello'] as $name) {
            $this->assertSame(app('router'), $router->getValue($routes->getByName($name)), $name);
        }
    }

    public function test_a_localized_route_can_point_at_an_invokable_class()
    {
        LocalizedRoute::get('hello', ['en', 'es' => 'hola'], InvokableHello::class)->name('hello');

        $this->get('/es/hola')->assertOk()->assertSee('hello es');
    }
}

class InvokableHello
{
    public function __invoke(): string
    {
        return 'hello ' . app()->getLocale();
    }
}
