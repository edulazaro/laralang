<?php

namespace EduLazaro\Laralang\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ValidateLocalizedSignature extends ValidateSignature
{
    /**
     * Handle an incoming request and validate its signature with locale context.
     *
     * This middleware extracts the locale from the route name (e.g., "es.client.password.setup")
     * and sets it before validating the signature, ensuring the signature validation
     * happens in the same locale context as when the URL was generated.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @param  mixed  ...$args
     * @return Response
     *
     * @throws \Illuminate\Routing\Exceptions\InvalidSignatureException
     */
    public function handle($request, Closure $next, ...$args): Response
    {
        $this->setLocaleFromRoute($request);

        return parent::handle($request, $next, ...$args);
    }

    /**
     * Extract locale from the route name and set application locale.
     *
     * LocalizedRoute creates route names with locale prefixes (e.g., "es.verify.email").
     * This method extracts the locale prefix and sets it in the application,
     * ensuring the signature validation uses the same locale context as generation.
     *
     * @param  Request  $request
     * @return void
     */
    protected function setLocaleFromRoute(Request $request): void
    {
        $route = $request->route();

        if (!$route) {
            return;
        }

        $routeName = $route->getName();

        if (!$routeName) {
            return;
        }

        $locales = config('locales.locales', [config('app.locale', 'en')]);

        foreach ($locales as $locale) {
            if (str_starts_with($routeName, $locale . '.')) {
                App::setLocale($locale);
                return;
            }
        }
    }
}
