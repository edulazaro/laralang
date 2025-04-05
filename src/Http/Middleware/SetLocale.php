<?php

namespace EduLazaro\Laralang\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
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
        $locales = config('locales.locales',  [config('app.locale')]);
        $defaultLocale = config('app.locale', config('app.fallback_locale', 'en'));
        $segment = $request->segment(1);

        if ($segment === $defaultLocale) {
            return redirect()->to($this->removePrefixFromUri($request->getRequestUri(), $segment), 301);
        }

        if (in_array($segment, $locales)) {
            App::setLocale($segment);
        } 

        $locale = in_array($segment, $locales) ? $segment : $defaultLocale;
        App::setLocale($locale);
  
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
