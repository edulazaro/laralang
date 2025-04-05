<?php

namespace EduLazaro\Laralang;

use Illuminate\Support\Facades\Route;
use \Illuminate\Routing\Route as LaravelRoute;
use EduLazaro\Laralang\Http\Middleware\SetLocale;

/**
 * Class LocalizedRoute
 *
 * Defines localized routes, generating a route for each configured language.
 */
class LocalizedRoute
{
    /** @var array<string, Route> Routes grouped by language code */
    protected $routes = [];

    /**
     * Constructor.
     *
     * @param array $locales Supported locales.
     * @param array $methods HTTP methods allowed for the route.
     * @param string $uri The base URI of the route.
     * @param mixed $action Controller or action for the route.
     */
    public function __construct(array $locales, array $methods, string $uri, $action)
    {
        $defaultLocale = config('app.locale');
        $prefixes = config('locales.prefixes', []);
        //$groupPrefix = trim(Route::getLastGroupPrefix() ?? '', '/');
        $groupPrefix = collect(Route::getGroupStack())
            ->pluck('prefix')
            ->filter()
            ->map(fn ($prefix) => trim($prefix, '/'))
            ->implode('/');

        foreach ($locales as $locale => $customTranslation) {


            if (is_int($locale)) {
                $locale = $customTranslation;
                $customTranslation = $uri;
            }

            $isDefault = $locale === $defaultLocale;
            $prefix = $prefixes[$locale] ?? ($isDefault ? '' : $locale);

            $segments = array_filter([
                $prefix,
                trim($groupPrefix, '/'),
                ltrim($customTranslation, '/'),
            ]);

            $localizedUri = implode('/', $segments);

            /*
            $route = Route::match(
                $methods,
                $localizedUri,
                is_array($action) ? $action : ['uses' => $action]
            );
            */

            $route = new LaravelRoute(
                $methods,
                $localizedUri,
                is_array($action) ? $action : ['uses' => $action]
            );

            $route->middleware([SetLocale::class]);
            
            $router = app('router');
            if ($router->hasGroupStack()) {
                $route->setAction($router->mergeWithLastGroup(
                    $route->getAction(),
                    false
                ));

            }
      
            if (method_exists($route, 'withoutGroupPrefix')) {
                $route->withoutGroupPrefix();
            }

            $this->routes[$locale] = $route;
        }

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $router = app('router');
    
        foreach ($this->routes as $route) {
            $router->getRoutes()->add($route);
        }
    }

    /**
     * Return routes

     * @return array
     */
    public function getRoutes():array
    {
        return $this->routes;
    }

    /**
     * Defines a localized GET route.
     *
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function get(string $uri, array $locales, $action)
    {
        return new self($locales, ['GET'], $uri, $action);
    }

    /**
     * Defines a localized POST route.
     *
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function post(string $uri, array $locales, $action)
    {
        return new self($locales, ['POST'], $uri, $action);
    }

    /**
     * Defines a localized PATCH route.
     *
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function patch(string $uri, array $locales, $action)
    {
        return new self($locales, ['PATCH'], $uri, $action);
    }

    /**
     * Defines a localized DELETE route.
     *
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function delete(string $uri, array $locales, $action)
    {
        return new self($locales, ['DELETE'], $uri, $action);
    }

    /**
     * Defines a localized route with custom HTTP methods.
     *
     * @param array $methods HTTP methods.
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function match(array $methods, string $uri, array $locales, $action)
    {
        return new self($locales, $methods, $uri, $action);
    }

    /**
     * Dynamic proxy to apply methods to all generated localized routes.
     *
     * @param string $method The method name.
     * @param array $arguments The method arguments.
     * @return $this
     */
    public function __call($method, $arguments)
    {
        if ($method === 'name') {
            $name = $arguments[0];
            foreach ($this->routes as $locale => $route) {
                $route->name("$locale.$name");
            }
        } else {
            foreach ($this->routes as $route) {
                $route->{$method}(...$arguments);
            }
        }

        return $this;
    }
}
