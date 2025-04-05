<?php

namespace EduLazaro\Laralang\Tests\Unit;

use Illuminate\Support\Facades\Route;
use EduLazaro\Laralang\Tests\TestCase;

class UrlGeneratorTest extends TestCase
{
    public function test_it_generates_localized_urls()
    {
        app()->setLocale('es');

        Route::name('es.test')->get('es/test', fn () => 'ok');

        $url = url()->route('test');

        $this->assertStringContainsString('/es/test', $url);
    }
}
