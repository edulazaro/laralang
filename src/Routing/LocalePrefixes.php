<?php

namespace EduLazaro\Laralang\Routing;

/**
 * Class LocalePrefixes
 *
 * Single source of truth for the URL prefix of each locale, so route
 * registration, resources and the localized fallback can never disagree.
 *
 * A value in the config is the literal prefix. Without one, the locale code
 * is used, except for the default locale, which never carries a prefix.
 */
class LocalePrefixes
{
    /**
     * Get the URL prefix of a locale.
     *
     * An empty string counts as no value: only the default locale can live at
     * the root, so two locales can never end up sharing it.
     *
     * @param string $locale
     * @return string
     */
    public static function get(string $locale): string
    {
        $configured = config('locales.prefixes', [])[$locale] ?? null;

        $prefix = trim((string) $configured, '/');

        if ($prefix !== '') {
            return $prefix;
        }

        return $locale === config('app.locale') ? '' : $locale;
    }

    /**
     * How long a permanent redirect may be cached, in seconds.
     *
     * The redirects this package emits are computed from the configured
     * prefixes and locale order, so a config change makes the ones already
     * cached point at URLs that no longer exist. A permanent redirect cannot
     * be revoked, but an explicit lifetime bounds the damage.
     *
     * @return int
     */
    public static function redirectMaxAge(): int
    {
        return (int) config('locales.redirect_max_age', 86400);
    }

    /**
     * Get the URL prefix of every configured locale, keyed by locale.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $prefixes = [];

        foreach ((array) config('locales.locales', []) as $locale) {
            $prefixes[$locale] = static::get($locale);
        }

        return $prefixes;
    }
}
