<?php

namespace EduLazaro\Laralang\Tests\Unit;

use EduLazaro\Laralang\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_it_has_default_locales_config()
    {
        $config = config('locales.locales');

        $this->assertIsArray($config);
        $this->assertContains('en', $config);
    }

    public function test_it_has_prefixes_config_structure()
    {
        $config = config('locales.prefixes');

        $this->assertIsArray($config);

        foreach ($config as $locale => $prefix) {
            $this->assertContains($locale, config('locales.locales'), "Locale '{$locale}' definido en 'prefixes' no existe en 'locales'");
            $this->assertIsString($prefix, "El prefijo para el locale '{$locale}' debe ser una cadena de texto");
        }
    }

    public function test_it_has_default_domains_config()
    {
        $config = config('locales.domains');

        $this->assertIsArray($config);

        $this->assertArrayHasKey('en', $config);
    }
}
