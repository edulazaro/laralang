<?php

namespace EduLazaro\Laralang\Routing;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LocalizedFallback
 *
 * Rescues a URL that does not match any route but does exist under another
 * locale, so /servicios reaches /es/servicios instead of a 404.
 *
 * Only runs once the router has already failed, so it costs nothing on
 * normal traffic.
 */
class LocalizedFallback
{
    /**
     * Handle a request that matched no route.
     *
     * @param Request $request
     * @return RedirectResponse
     *
     * @throws NotFoundHttpException When there is nothing to rescue.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // With every locale prefixed, including the default one, nothing is
        // registered at the root, so it is sent to the visitor's language.
        // Temporary and varying by header, unlike the permanent redirects
        // below, because the destination depends on who is asking.
        if ($this->isRoot($request) && LocalePrefixes::get(config('app.locale')) !== '') {
            return redirect()
                ->to('/' . LocalePrefixes::get($this->preferredLocale($request)), 302)
                ->header('Vary', 'Accept-Language');
        }

        $target = $this->resolve($request);

        if ($target === null) {
            throw new NotFoundHttpException;
        }

        return redirect()->to($target, 301);
    }

    /**
     * Whether the request is for the site root.
     *
     * @param Request $request
     * @return bool
     */
    protected function isRoot(Request $request): bool
    {
        return trim($request->path(), '/') === '';
    }

    /**
     * The locale to send a visitor to when the URL says nothing: what they
     * chose before, then what their browser asks for, then the default.
     *
     * @param Request $request
     * @return string
     */
    protected function preferredLocale(Request $request): string
    {
        $locales = (array) config('locales.locales', []);
        $session = session('locale');

        if ($session && in_array($session, $locales, true)) {
            return $session;
        }

        return $request->getPreferredLanguage($locales) ?: config('app.locale');
    }

    /**
     * Find the same path registered under another locale.
     *
     * @param Request $request
     * @return string|null The URL to redirect to, or null when there is none.
     */
    public function resolve(Request $request): ?string
    {
        // A redirect drops the body, so anything that carries one is left alone.
        if (! in_array($request->getMethod(), ['GET', 'HEAD'])) {
            return null;
        }

        $path = trim($request->path(), '/');

        if ($path === '' || $path === '/') {
            return null;
        }

        $prefixes = $this->prefixes();
        $bare = $this->withoutLocalePrefix($path, $prefixes);

        foreach ($this->localeOrder() as $locale) {
            if (! array_key_exists($locale, $prefixes)) {
                continue;
            }

            $candidate = trim($prefixes[$locale] . '/' . $bare, '/');

            if ($candidate === $path) {
                continue;
            }

            if ($this->isRegistered($candidate)) {
                // The raw string, since getQueryString() sorts the parameters.
                $query = $request->server->get('QUERY_STRING') ?: $request->getQueryString();

                return url($candidate) . ($query ? '?' . $query : '');
            }
        }

        return null;
    }

    /**
     * Locales to try, the default one first so the redirect never depends on
     * the visitor and can safely be permanent.
     *
     * @return array<int, string>
     */
    protected function localeOrder(): array
    {
        $locales = (array) config('locales.locales', []);
        $default = config('app.locale');

        return array_values(array_unique(array_merge([$default], $locales)));
    }

    /**
     * URL prefix of every configured locale, keyed by locale.
     *
     * @return array<string, string>
     */
    protected function prefixes(): array
    {
        return LocalePrefixes::all();
    }

    /**
     * Strip the leading locale prefix from a path, when it has one.
     *
     * @param string $path
     * @param array<string, string> $prefixes
     * @return string
     */
    protected function withoutLocalePrefix(string $path, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if ($prefix === '') {
                continue;
            }

            if ($path === $prefix) {
                return '';
            }

            if (str_starts_with($path, $prefix . '/')) {
                return trim(substr($path, strlen($prefix) + 1), '/');
            }
        }

        return $path;
    }

    /**
     * Ask the router whether a URI resolves, so route parameters and their
     * constraints are honoured instead of comparing strings.
     *
     * @param string $uri
     * @return bool
     */
    protected function isRegistered(string $uri): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create('/' . $uri, 'GET'));
        } catch (\Throwable $e) {
            return false;
        }

        // Fallback routes match everything, this one included, so they do not
        // count as a rescue.
        return ! $route->isFallback;
    }
}
