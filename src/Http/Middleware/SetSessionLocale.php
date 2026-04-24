<?php

namespace EduLazaro\Laralang\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetSessionLocale
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

        $defaultLocale = config('app.locale', config('app.fallback_locale', 'en'));

        $locale = session('locale', $defaultLocale);

        App::setLocale($locale);

        $request->attributes->set(SetRouteLocale::RESOLVED_FLAG, true);

        return $next($request);
    }
}
