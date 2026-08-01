<?php

namespace EduLazaro\Laralang;

use Illuminate\Support\Facades\Route;
use EduLazaro\Laralang\Routing\Route as LaravelRoute;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;

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

            $route->setContainer(app());
            $route->middleware([SetRouteLocale::class]);

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
     * Defines a localized PUT route.
     *
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function put(string $uri, array $locales, $action)
    {
        return new self($locales, ['PUT'], $uri, $action);
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
     * Defines a localized OPTIONS route.
     *
     * @param string $uri The base URI.
     * @param array $locales The locales to register.
     * @param mixed $action Controller or action.
     * @return static
     */
    public static function options(string $uri, array $locales, $action)
    {
        return new self($locales, ['OPTIONS'], $uri, $action);
    }

    /**
     * Defines a localized resource. Bridge to LocalizedResource, which is the
     * canonical entry point, so both spellings do exactly the same.
     *
     * @param string $name The base resource name.
     * @param array $locales The locales to register.
     * @param mixed $controller The resource controller.
     * @return LocalizedResource
     */
    public static function resource(string $name, array $locales, $controller): LocalizedResource
    {
        return LocalizedResource::make($name, $locales, $controller);
    }

    /**
     * Defines a localized API resource, without the create and edit routes.
     *
     * @param string $name The base resource name.
     * @param array $locales The locales to register.
     * @param mixed $controller The resource controller.
     * @return LocalizedResource
     */
    public static function apiResource(string $name, array $locales, $controller): LocalizedResource
    {
        return LocalizedResource::api($name, $locales, $controller);
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
