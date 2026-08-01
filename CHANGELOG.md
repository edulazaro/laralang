# Changelog

All notable changes to `edulazaro/laralang` will be documented in this file.

## 3.0.0

### Removed (breaking)

- Ziggy 1 support is gone, along with the `EduLazaro\Laralang\Routing\LocalizedBladeRouteGenerator` class. **Read this if your JavaScript `route()` stops resolving unprefixed names after updating.**

  The class extended `Tightenco\Ziggy\BladeRouteGenerator` and used `getRoutePayload()`, `prepareDomain()`, `$baseUrl`, `$basePort` and the `namedRoutes` payload key. Ziggy removed all of them in its own `1.0.0` release, back in November 2020, so the integration only ever worked with Ziggy `0.9.x` and earlier. With any published Ziggy 1.x it did not degrade, it threw a fatal error as soon as the class was resolved, which is when a view renders `@routes`.

  Ziggy 2 is supported and unaffected. If you are on Ziggy 1.x, either upgrade to Ziggy 2, in which case `route('dashboard')` keeps resolving to the current locale in JavaScript, or stay on Laralang `2.2.0`, where the class still exists but still throws.

### Added

- Localized fallback. A URL that matches no route but exists under another locale is redirected there with a `301` instead of returning a 404, so `/servicios` reaches `/es/servicios` and `/es/services` reaches `/services`. Off by default, enabled with `'fallback' => true` in `config/locales.php`, or registered by hand with `LocalizedRoute::fallback()` when the application already has a fallback route of its own.
- The default locale can now carry a URL prefix, so every language can be prefixed and nothing lives at the root. Set a prefix for it in `locales.prefixes` and `SetRouteLocale` stops stripping it. With the localized fallback enabled, `/` redirects to the visitor's language with a `302` and `Vary: Accept-Language`.
- `locales.redirect_max_age` config option, defaulting to `86400`. Permanent redirects now carry an explicit `Cache-Control: max-age`, so changing the prefixes on a live site no longer leaves redirects cached forever pointing at URLs that no longer exist.
- Continuous integration covering PHP `8.2` to `8.5` against Laravel `11`, `12` and `13`, plus PHP nightly. The matrix is resolved at run time from the active PHP branches and the published Laravel majors, so a new release joins it without touching the workflow.
- Migration guides from `mcamara/laravel-localization` and from `niels-numbers/laravel-localizer`.

### Changed

- An empty string in `locales.prefixes` now means the same as `null` or a missing key: the locale code is used, except for the default locale, which has no prefix. Previously an empty string was honoured literally for any locale, so giving one to a locale that was not the default registered two languages at the same URLs and the second silently replaced the first in the route collection.
- The prefix rule lives in a single place, `EduLazaro\Laralang\Routing\LocalePrefixes`, instead of being duplicated across route registration, resources and the middleware.

### Removed

- The `locales.domains` config key, which was never implemented.
- `composer.lock` is no longer tracked, as a library resolves against the dependencies of the application that installs it.

## 2.2.0

### Added

- `EduLazaro\Laralang\LocalizedResource` registers a Laravel resource once per locale, with the resource name translated per language and, optionally, the `create` and `edit` segments too. Laravel's own resource registrar does the work, so `only()`, `except()`, `names()`, `middleware()`, `shallow()` and `scoped()` keep working and are applied to every locale. Route names use the base name in every language, and the route parameter is derived from it too, so a single controller signature works no matter which locale resolved the URL.
- `LocalizedResource::api()` for resources without the `create` and `edit` routes.
- `LocalizedRoute::resource()` and `LocalizedRoute::apiResource()` as bridges to the above, for parity with Laravel's own spelling.
- `LocalizedRoute::put()`, `LocalizedRoute::options()` and `LocalizedRoute::any()`. Only `get`, `post`, `patch`, `delete` and `match` existed before.

## 2.1.2

### Changed

- Documentation only. Route registration is documented with real controller actions and the tables of generated URLs, and the middleware examples show actual routes instead of placeholder comments.

## 2.1.1

### Changed

- Artwork and README.

## 2.1.0

### Added

- Ziggy 2 support. Laralang replaces Ziggy's Blade route generator so the routes of the current locale are also published under their unprefixed name, and `route('dashboard')` behaves in JavaScript exactly like it does in PHP. Ziggy 2 moved to the `Tighten\Ziggy` namespace, which the previous integration did not target.

## 2.0.1

### Fixed

- Laravel version constraint.

## 2.0.0

### Changed (breaking)

- Minimum Laravel version is now `^11.0` (was `>=10.0`). PHP minimum stays at `^8.2`.
- The `locales.override_url_generator` config option has been removed. The localized URL generator is now always active. Users who had set it to `false` must migrate to using Laravel's `URL` facade / `url()->to()` manually for the URLs they want unprefixed.
- `routeIs()` (and `request()->routeIs()`) with a locale-prefixed pattern now also checks that the prefix matches `app()->getLocale()`. Previously `routeIs('es.services')` matched by route name alone regardless of the current locale. Plain patterns (without a locale prefix) are unaffected and keep working across all locales.

### Added

- `routeIs()` now understands locale-prefixed route names. Use `routeIs('services')` to match any locale variant, or `routeIs('es.services')` to match only when the current locale matches the prefix. Previously the former never matched and users needed `routeIs('*.services')` as a workaround, which still works.
- New `EduLazaro\Laralang\Routing\Route` class extending `Illuminate\Routing\Route` that overrides `named()`. It is used only by `LocalizedRoute` instances; plain `Route::get()` users keep Laravel's default behaviour untouched.
- `Laralang::alternates()` returns the current route URL in every configured locale. Building block for language switchers, hreflang tags and multi-locale sitemaps. Available as `\EduLazaro\Laralang\Facades\Laralang::alternates()` and as the global `laralang_alternates()` helper.

### Fixed

- Locale middlewares (`SetSmartLocale`, `SetRouteLocale`, `SetBrowserLocale`, `SetSessionLocale`) are now idempotent within a single request. Stacking `SetSmartLocale` on a route group with `LocalizedRoute` (which automatically attaches `SetRouteLocale` per route) no longer triggers spurious 301 redirects on locale-prefixed URLs.
