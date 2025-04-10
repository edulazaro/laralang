<?php

namespace EduLazaro\Laralang\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;
use EduLazaro\Laralang\Http\Middleware\SetBrowserLocale;

class SetSmartLocale
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
        $locales = config('locales.locales', [config('app.locale')]);
        $segment = $request->segment(1);

        if (in_array($segment, $locales)) {
            return app(SetRouteLocale::class)->handle($request, $next);
        }

        return app(SetBrowserLocale::class)->handle($request, $next);
    }
}
