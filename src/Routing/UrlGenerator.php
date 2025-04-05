<?php

namespace EduLazaro\Laralang\Routing;

use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Support\Str;

/**
 * Class UrlGenerator
 */
class UrlGenerator extends BaseUrlGenerator
{
    /**
     * Generates the URL to a named route.
     *
     * @param string $name The name of the route.
     * @param mixed $parameters The parameters for the route.
     * @param bool $absolute Whether to generate an absolute or relative URL.
     * @return string
     *
     * @throws RouteNotFoundException
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        $locale = app()->getLocale();
        $locales = config('locales.locales', []);

        $prefix = Str::before($name, '.');
        $alreadyLocalized = $prefix !== $name && in_array($prefix, $locales);

        if (! $alreadyLocalized) {
            $localizedName = "{$locale}.{$name}";
    
            if (! is_null($route = $this->routes->getByName($localizedName))) {
                return $this->toRoute($route, $parameters, $absolute);
            }
        }

        if (! is_null($route = $this->routes->getByName($name))) {
            return $this->toRoute($route, $parameters, $absolute);
        }


        if (! is_null($this->missingNamedRouteResolver) &&
            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))) {
            return $url;
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }
}
