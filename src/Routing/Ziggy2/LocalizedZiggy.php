<?php

namespace EduLazaro\Laralang\Routing\Ziggy2;

use Tighten\Ziggy\Ziggy;

/**
 * Class LocalizedZiggy
 *
 * Ziggy 2 payload exposing the routes of the current locale also under their
 * unprefixed name, so route('dashboard') works in JavaScript the same way it
 * works in PHP.
 */
class LocalizedZiggy extends Ziggy
{
    /**
     * Build the Ziggy payload, aliasing the routes of the current locale.
     *
     * @return array
     */
    public function toArray(): array
    {
        $payload = parent::toArray();

        $payload['routes'] = $this->withLocaleAliases($payload['routes']);

        return $payload;
    }

    /**
     * Add an unprefixed alias for every route of the current locale.
     *
     * A route already registered under the unprefixed name is never replaced,
     * so a plain Route::get()->name('dashboard') always wins over the alias of
     * es.dashboard.
     *
     * @param  array  $routes
     * @return array
     */
    protected function withLocaleAliases(array $routes): array
    {
        $prefix = app()->getLocale() . '.';
        $aliases = [];

        foreach ($routes as $name => $route) {
            if (!str_starts_with((string) $name, $prefix)) {
                continue;
            }

            $alias = substr((string) $name, strlen($prefix));

            if ($alias === '' || isset($routes[$alias]) || isset($aliases[$alias])) {
                continue;
            }

            $aliases[$alias] = $route;
        }

        return $routes + $aliases;
    }
}
