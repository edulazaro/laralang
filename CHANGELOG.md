# Changelog

All notable changes to `edulazaro/laralang` will be documented in this file.

## 2.0.0

### Changed (breaking)

- Minimum Laravel version is now `^11.0` (was `>=10.0`). PHP minimum stays at `^8.2`.
- The `locales.override_url_generator` config option has been removed. The localized URL generator is now always active. Users who had set it to `false` must migrate to using Laravel's `URL` facade / `url()->to()` manually for the URLs they want unprefixed.
- `routeIs()` (and `request()->routeIs()`) with a locale-prefixed pattern now also checks that the prefix matches `app()->getLocale()`. Previously `routeIs('es.services')` matched by route name alone regardless of the current locale. Plain patterns (without a locale prefix) are unaffected and keep working across all locales.

### Added

- `routeIs()` now understands locale-prefixed route names. Use `routeIs('services')` to match any locale variant, or `routeIs('es.services')` to match only when the current locale matches the prefix. Previously the former never matched and users needed `routeIs('*.services')` as a workaround — those workarounds still work.
- New `EduLazaro\Laralang\Routing\Route` class extending `Illuminate\Routing\Route` that overrides `named()`. It is used only by `LocalizedRoute` instances; plain `Route::get()` users keep Laravel's default behaviour untouched.
- `Laralang::alternates()` returns the current route URL in every configured locale. Building block for language switchers, hreflang tags and multi-locale sitemaps. Available as `\EduLazaro\Laralang\Facades\Laralang::alternates()` and as the global `laralang_alternates()` helper.

### Fixed

- Locale middlewares (`SetSmartLocale`, `SetRouteLocale`, `SetBrowserLocale`, `SetSessionLocale`) are now idempotent within a single request. Stacking `SetSmartLocale` on a route group with `LocalizedRoute` (which automatically attaches `SetRouteLocale` per route) no longer triggers spurious 301 redirects on locale-prefixed URLs.
