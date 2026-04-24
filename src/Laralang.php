<?php

namespace EduLazaro\Laralang;

use Illuminate\Support\Facades\Route as RouteFacade;

class Laralang
{
    /**
     * Return the current request's route URLs in every configured locale.
     *
     * @param  array  $params    Override route parameters; defaults to the
     *                           current route's resolved parameters.
     * @param  bool   $absolute  Whether to produce absolute URLs.
     * @return array<string,string>  Keyed by locale.
     */
    public static function alternates(array $params = [], bool $absolute = true): array
    {
        $currentRoute = request()->route();

        if (!$currentRoute) {
            return [];
        }

        $currentName = $currentRoute->getName();

        if (!$currentName) {
            return [];
        }

        $locales = (array) config('locales.locales', []);

        // Strip the locale prefix from the current route name to get the base.
        $baseName = $currentName;
        foreach ($locales as $locale) {
            $needle = "{$locale}.";
            if (str_starts_with($currentName, $needle)) {
                $baseName = substr($currentName, strlen($needle));
                break;
            }
        }

        // If nothing was stripped the route is not a LocalizedRoute; return
        // just the current locale.
        if ($baseName === $currentName) {
            return [
                app()->getLocale() => url()->current(),
            ];
        }

        $routeParams = empty($params) ? $currentRoute->parameters() : $params;

        $urls = [];

        foreach ($locales as $locale) {
            $localizedName = "{$locale}.{$baseName}";
            if (RouteFacade::has($localizedName)) {
                $urls[$locale] = route($localizedName, $routeParams, $absolute);
            }
        }

        return $urls;
    }
}
