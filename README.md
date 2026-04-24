# Laralang for Laravel

<p align="center">
    <a href="https://packagist.org/packages/edulazaro/laralang"><img src="https://img.shields.io/packagist/dt/edulazaro/laralang" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/edulazaro/laralang"><img src="https://img.shields.io/packagist/v/edulazaro/laralang" alt="Latest Stable Version"></a>
</p>

## Introduction

Laralang is a Laravel package that allows you to create localized routes for different languages. By defining routes with language support, you can easily manage multilingual applications.

With Laralang, you can define a route for each language, and the package will automatically generate routes for the specified locales. It provides an efficient way to handle localized URIs and ensures that the URL structure is properly mapped to the language-specific paths.

## Features

- Define multilingual routes with one simple API.
- Automatic locale redirection via usual `route` helper.
- Redirect to any specific locale also via the `route` helper.
- Support for Ziggy

## Requirements

- PHP `^8.2`
- Laravel `^11.0`

For Laravel 10 support use Laralang `^1.5`. See [UPGRADING.md](UPGRADING.md) for the 1.x → 2.0 migration guide.

## Installation

Execute the following command in your Laravel root project directory:

```bash
composer require edulazaro/laralang
```

## Getting started

This will generate routes for `/dashboard` in English and `/es/panel` in Spanish:

```php
use EduLazaro\Laralang\LocalizedRoute;

LocalizedRoute::get('dashboard', [
    'en',
    'es' => 'panel'
], fn () => 'ok')->name('dashboard');
```

You can add any middleware as usual:


```php
use EduLazaro\Laralang\LocalizedRoute;

LocalizedRoute::get('dashboard', [
    'en',
    'es' => 'panel'
], fn () => 'ok')->middleware('auth')->name('dashboard');
```


## Configuration

Publish the Laralang configuration using this command

```bash
php artisan vendor:publish  --tag="locales"
```

If it does not work, then try:

```bash
php artisan vendor:publish --provider="EduLazaro\Laralang\LaralangServiceProvider" --tag="locales"
```

This will generate the `locales.php` file in the `config` folder. This configuration file defines the supported locales and other localization-related settings for your Laravel application. It provides the flexibility to define language preferences, URL prefixes, and potential domain mappings for each locale. Below is a breakdown of the different sections of the locales.php configuration file.

### Supported Locales

The `locales` array defines the languages that your application supports. You can list multiple languages here, and the system will handle the routes accordingly.

```php
'locales' => [
    'en',   // English (default)
    'es',   // Spanish
    'fr',   // French
],
```

Add or remove any locales as per your application's requirements.

### Locale Prefixes

The `prefixes` array allows you to specify custom URL prefixes for each locale. When a locale has a prefix defined, URLs in that locale will have the prefix as part of the path. You do not need to specify all prefixes, as by default the locale name will be used as a prefix except for the default language. However, if you want to customize the prefixes:

```php
'prefixes' => [
    'en' => '',   // No prefix for English (URL: /)
    'es' => 'es', // 'es' prefix for Spanish (URL: /es)
    'fr' => 'fr', // 'fr' prefix for French (URL: /fr)
],
```

### Domain Settings  (Future Support)

The `domains` array allows you to define custom domains for specific locales. If your application requires different domains for different languages (e.g., example.com for English, es.example.com for Spanish), or even totally different tlds, which is a bit challenging in Laravel,  you can configure it here:

```php
'domains' => [
    'en' => null,   // No custom domain for English
    'es' => null,   // No custom domain for Spanish
    'fr' => null,   // No custom domain for French
],
```

## How to Register Localized Routes

To register the localized routes, you can use the `LocalizedRoute::get()`, `LocalizedRoute::post()`, or any other HTTP verb method like Laravel's regular routes:

```php
use EduLazaro\Laralang\LocalizedRoute;

// Define a localized GET route
LocalizedRoute::get('profile', [
    'en',
    'es' => 'perfil'
], fn () => 'Profile Page')->name('profile');

// Define a localized POST route
LocalizedRoute::post('update-profile', [
    'en',
    'es' => 'actualizar-perfil'
], fn () => 'Update Profile')->name('update-profile');
```

This is how it works:

* The first parameter is the URI (e.g., `profile`, `dashboard`).
* The second parameter is an associative array with the locale as the key and the localized URI as the value.
* The third parameter is the regular closure or controller action.

Routes weill be created like:

* /admin/dashboard (for English)
* /es/admin/panel (for Spanish)

As you can see, the language prefix will always be added at teh beginning. This will happen even when using groups with prefixes:

```php
Route::prefix('admin')->group(function () {
    $localizedRoute = LocalizedRoute::get('profile', [
        'en',
        'fr'
        'es' => 'perfil'
    ], fn () => 'ok')->name('dashboard');
});
```

These routes will be created:

* /admin/profile (for English)
* /fr/admin/profile (for French)
* /es/admin/perfil (for Spanish)


## How to Use Localized Routes in Views

To generate URLs for your localized routes, you can use the `route()` helper as usual:

```php
route('en.dashboard') // English
route('es.dashboard') // Spanish
```

However if you run just `route('dashboard')` it will also work, and it will redirect to the route named `dashboard` of the current locale.

## Generating Alternate URLs

For language switchers, `hreflang` tags in the `<head>` for SEO, multi-locale sitemaps and canonical URL switching, Laralang exposes `Laralang::alternates()`. It returns a map of every configured locale to the URL of the current route in that locale, without the usual hand-rolled `App::setLocale()` swapping.

```php
use EduLazaro\Laralang\Facades\Laralang;

Laralang::alternates();
// [
//   'en' => 'https://example.com/services',
//   'es' => 'https://example.com/es/servicios',
//   'ca' => 'https://example.com/ca/serveis',
// ]
```

Typical `hreflang` usage inside a Blade `<head>`:

```blade
@foreach (Laralang::alternates() as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
@endforeach
```

A typical language switcher:

```blade
<ul>
    @foreach (Laralang::alternates() as $locale => $url)
        <li><a href="{{ $url }}">{{ strtoupper($locale) }}</a></li>
    @endforeach
</ul>
```

The signature is:

```php
Laralang::alternates(array $params = [], bool $absolute = true): array
```

- `$params` — override route parameters. Defaults to the current route's resolved parameters, so dynamic segments like `{slug}` are carried into every locale URL automatically.
- `$absolute` — set to `false` for relative paths instead of absolute URLs (useful for sitemaps where you build the host yourself).

Behaviour notes:

- If a route is declared in a subset of locales (e.g. `['en', 'es']` but not `ca`), the returned array only contains keys for the locales that actually have a registered route. No dead links.
- Called on a plain non-localized route (`Route::get('foo')->name('foo')`), it degrades gracefully and returns `[current_locale => current_url]`, so you can always iterate safely.
- Outside of a matched request, or on a route with no name, it returns `[]`.

A global helper is also available if you prefer to avoid the facade import:

```php
laralang_alternates();                         // same as Laralang::alternates()
laralang_alternates(['slug' => 'custom']);     // with param override
laralang_alternates([], false);                // relative paths
```

## Using `routeIs()` with localized routes

Because `LocalizedRoute` registers one route per locale with names like `en.services`, `es.services` and `fr.services`, a plain `routeIs('services')` check used to silently never match, forcing users to write `routeIs('*.services')` in navigation active-state helpers.

Laralang now ships a thin `EduLazaro\Laralang\Routing\Route` subclass that overrides `named()` for `LocalizedRoute` instances only. Plain `Route::get()` routes keep Laravel's default behaviour untouched.

The matching rules are:

- A plain pattern (no locale prefix) matches if the current route name matches the pattern literally **or** if it matches `{anyLocale}.{pattern}`. So `routeIs('services')` is `true` on `en.services`, `es.services` and `fr.services`.
- A locale-prefixed pattern (e.g. `es.services`) matches only when the prefix equals the current `app()->getLocale()` **and** the current route name matches literally. So `routeIs('es.services')` is `true` only when you are actually on the Spanish variant.

```php
// On the Spanish variant of /servicios:
request()->routeIs('services');       // true
request()->routeIs('es.services');    // true
request()->routeIs('fr.services');    // false

// Wildcards keep working across locales:
request()->routeIs('services*');      // true on es.services.show, fr.services.create, etc.
```

The previous `routeIs('*.services')` workaround still works.

## Middleware

Laralang comes with several optional middlewares that you can apply depending on your needs. These middlewares help you control how the locale is detected and applied throughout your application.

You can assign these middlewares to your route groups just like any Laravel middleware. For most applications, you can simply use `SetSmartLocale` globally to cover all use cases.

For specific sections of your app, you can fine-tune and assign different middlewares to different route groups.

### Idempotency

All locale middlewares are idempotent. Applying `SetSmartLocale` to a group and using `LocalizedRoute` (which auto-attaches `SetRouteLocale` per route) is safe — the first middleware to run resolves the locale and the rest become no-ops. Build whatever middleware stack you need without worrying about double execution or duplicate redirects.

If every route is created via `LocalizedRoute`, you do not need to add `SetSmartLocale` to your group — `SetRouteLocale` is already injected per route. Add `SetSmartLocale` only when you have a mix of localized and plain routes and want the plain ones to inherit the session/browser locale.

### SetRouteLocale

This middleware will detect the locale from the URL prefix and apply it.

If you have localized routes with prefixes (e.g., /es/dashboard), this middleware ensures the application locale matches the URL.

```php
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;

Route::middleware(['web', SetRouteLocale::class])
    ->group(function () {
        // routes with locale prefix
    });
```

### SetSessionLocale

This middleware applies the locale stored in the user session.

Useful for internal routes like dashboards or admin panels, where the locale is determined once and stored in the session.

```php
use EduLazaro\Laralang\Http\Middleware\SetSessionLocale;

Route::middleware(['web', SetSessionLocale::class])
    ->group(function () {
        // admin or internal routes
    });
```

### SetBrowserLocale

This middleware reads the locale from the browser's Accept-Language header only if no session locale is already set.

On first visit, it detects the preferred browser language and stores it in the session. Good for public routes to auto-detect a first-time visitor's language and store it for subsequent requests.

```php
use EduLazaro\Laralang\Http\Middleware\SetBrowserLocale;

Route::middleware(['web', SetBrowserLocale::class])
    ->group(function () {
        // public routes
    });
```

### SetSmartLocale

This is the recommended "universal" middleware.

It combines all the previous strategies in this priority order:

* Route prefix locale
* Session locale or Browser locale (fallback)

If you want to apply localization globally without thinking about it, this middleware is for you.

```php
use EduLazaro\Laralang\Http\Middleware\SetSmartLocale;

Route::middleware(['web', SetSmartLocale::class])
    ->group(function () {
        // all routes (public, admin, etc.)
    });
```

### SetRouteLocale

This middleware will detect the locale from the URL prefix and apply it.

If you have localized routes with prefixes (e.g., /es/dashboard), this middleware ensures the application locale matches the URL.

## License

Larakeep is open-sourced software licensed under the [MIT license](LICENSE.md).