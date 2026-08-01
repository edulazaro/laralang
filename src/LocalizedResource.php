<?php

namespace EduLazaro\Laralang;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;
use EduLazaro\Laralang\Routing\LocalePrefixes;

/**
 * Class LocalizedResource
 *
 * Registers a Laravel resource once per locale. Laravel's own ResourceRegistrar
 * builds the seven routes, so options, parameters and shallow nesting keep
 * working, and this class only takes care of the locale: the URI prefix, the
 * translated resource name, the translated create and edit segments, and the
 * locale prefixed route names.
 */
class LocalizedResource
{
    /** @var array<string, PendingResourceRegistration> Pending registrations by locale */
    protected array $pending = [];

    /** @var array<string, string> URI prefix by locale */
    protected array $prefixes = [];

    /** @var array<string, array<string, string>> Translated create and edit segments by locale */
    protected array $verbs = [];

    protected bool $registered = false;

    /**
     * @param string $name The base resource name, used for route names and parameters.
     * @param array $locales The locales to register.
     * @param mixed $controller The resource controller.
     * @param bool $api Whether to register an API resource (no create and edit).
     */
    public function __construct(string $name, array $locales, $controller, bool $api = false)
    {
        $parameter = $this->parameterFor($name);

        foreach ($locales as $locale => $definition) {
            if (is_int($locale)) {
                $locale = $definition;
                $definition = $name;
            }

            $uri = is_array($definition) ? ($definition['uri'] ?? $name) : $definition;
            $verbs = is_array($definition) ? ($definition['verbs'] ?? []) : [];

            $this->prefixes[$locale] = LocalePrefixes::get($locale);
            $this->verbs[$locale] = $verbs;

            $options = [
                'as' => $locale,
                'names' => $name,
                'parameters' => [$uri => $parameter],
            ];

            $this->pending[$locale] = $api
                ? Route::apiResource($uri, $controller, $options)
                : Route::resource($uri, $controller, $options);
        }
    }

    /**
     * Register a localized resource.
     *
     * @param string $name
     * @param array $locales
     * @param mixed $controller
     * @return static
     */
    public static function make(string $name, array $locales, $controller): static
    {
        return new static($name, $locales, $controller);
    }

    /**
     * Register a localized API resource, without the create and edit routes.
     *
     * @param string $name
     * @param array $locales
     * @param mixed $controller
     * @return static
     */
    public static function api(string $name, array $locales, $controller): static
    {
        return new static($name, $locales, $controller, true);
    }

    /**
     * Get the route parameter name, always derived from the base resource name
     * so the controller signature stays the same in every locale.
     *
     * @param string $name
     * @return string
     */
    protected function parameterFor(string $name): string
    {
        $segments = explode('.', $name);

        return Str::singular(str_replace('-', '_', end($segments)));
    }

    /**
     * Register every pending resource and apply the locale to the routes it created.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        foreach ($this->pending as $locale => $pending) {
            $routes = $this->registerWithVerbs($pending, $this->verbs[$locale]);

            $prefix = $this->prefixes[$locale];

            foreach ($routes as $route) {
                if ($prefix !== '' && $prefix !== null) {
                    $route->setUri(trim($prefix . '/' . $route->uri(), '/'));
                }

                $route->middleware([SetRouteLocale::class]);
            }
        }

        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Register a pending resource with the create and edit segments of its locale.
     *
     * The verbs are a global setting in Laravel, so they are restored right after.
     *
     * @param PendingResourceRegistration $pending
     * @param array<string, string> $verbs
     * @return iterable
     */
    protected function registerWithVerbs(PendingResourceRegistration $pending, array $verbs): iterable
    {
        if (empty($verbs)) {
            return $pending->register() ?? [];
        }

        $original = Route::resourceVerbs();

        Route::resourceVerbs(array_merge($original, $verbs));

        try {
            return $pending->register() ?? [];
        } finally {
            Route::resourceVerbs($original);
        }
    }

    /**
     * Proxy any pending resource method to every locale.
     *
     * @param string $method
     * @param array $arguments
     * @return $this
     */
    public function __call($method, $arguments)
    {
        foreach ($this->pending as $pending) {
            $pending->{$method}(...$arguments);
        }

        return $this;
    }

    public function __destruct()
    {
        $this->register();
    }
}
