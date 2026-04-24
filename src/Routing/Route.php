<?php

namespace EduLazaro\Laralang\Routing;

use Illuminate\Routing\Route as BaseRoute;
use Illuminate\Support\Str;

class Route extends BaseRoute
{
    public function named(...$patterns): bool
    {
        $name = $this->getName();

        if (is_null($name)) {
            return false;
        }

        $locales = (array) config('locales.locales', []);
        $currentLocale = app()->getLocale();

        foreach ($patterns as $pattern) {
            $prefix = Str::before($pattern, '.');
            $hasLocalePrefix = $prefix !== $pattern && in_array($prefix, $locales);

            if ($hasLocalePrefix) {
                if ($prefix === $currentLocale && Str::is($pattern, $name)) {
                    return true;
                }
            } else {
                if (Str::is($pattern, $name)) {
                    return true;
                }

                foreach ($locales as $locale) {
                    if (Str::is("{$locale}.{$pattern}", $name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
