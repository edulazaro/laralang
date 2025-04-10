<?php

namespace EduLazaro\Laralang\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use EduLazaro\Laralang\Http\Middleware\SetSessionLocale;

class SetRouteLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-Livewire') || $request->ajax()) {
            return app(SetSessionLocale::class)->handle($request, $next);
        }

        $locales = config('locales.locales',  [config('app.locale')]);
        $defaultLocale = config('app.locale', config('app.fallback_locale', 'en'));
        $segment = $request->segment(1);

        if ($segment === $defaultLocale) {
            return redirect()->to($this->removePrefixFromUri($request->getRequestUri(), $segment), 301);
        }

        $locale = in_array($segment, $locales) ? $segment : session('locale', $defaultLocale);

        App::setLocale($locale);

        if (session()->get('locale') !== $locale) {
            session()->put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Remove the locale prefix from a URI.
     *
     * @param  string  $uri
     * @param  string  $prefix
     * @return string
     */
    protected function removePrefixFromUri(string $uri, string $prefix)
    {
        return preg_replace("#^/?" . preg_quote($prefix, '#') . "(/|$)#", '/', $uri);
    }
}
