<?php

namespace EduLazaro\Laralang\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetBrowserLocale
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
        if ($request->attributes->get(SetRouteLocale::RESOLVED_FLAG)) {
            return $next($request);
        }

        $locales = config('locales.locales', [config('app.locale', config('app.fallback_locale', 'en'))]);
        $sessionLocale = session('locale');


        if ($sessionLocale) {
            App::setLocale($sessionLocale);
        } else {
            $locale = $request->getPreferredLanguage($locales) ?? config('app.locale', config('app.fallback_locale', 'en'));

            App::setLocale($locale);
            session()->put('locale', $locale);
        }

        $request->attributes->set(SetRouteLocale::RESOLVED_FLAG, true);

        return $next($request);
    }
}
