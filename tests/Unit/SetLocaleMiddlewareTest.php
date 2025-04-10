<?php

namespace EduLazaro\Laralang\Tests\Unit;

use Illuminate\Http\Request;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;
use Illuminate\Support\Facades\App;
use EduLazaro\Laralang\Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    public function test_it_sets_locale_based_on_url_segment()
    {
        $middleware = new SetRouteLocale();

        $request = Request::create('/es/test', 'GET');

        $middleware->handle($request, function () {
            return null;
        });

        $this->assertEquals('es', App::getLocale());
    }
}
