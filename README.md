![Laralang](art/banner.png)

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
- Support for Ziggy 1 and Ziggy 2

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
use App\Http\Controllers\DashboardController;
use EduLazaro\Laralang\LocalizedRoute;

LocalizedRoute::get('dashboard', [
    'en',
    'es' => 'panel'
], [DashboardController::class, 'index'])->name('dashboard');
```

You can add any middleware as usual:


```php
use App\Http\Controllers\DashboardController;
use EduLazaro\Laralang\LocalizedRoute;

LocalizedRoute::get('dashboard', [
    'en',
    'es' => 'panel'
], [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
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

Use `LocalizedRoute::get()`, `LocalizedRoute::post()`, `LocalizedRoute::put()`, `LocalizedRoute::patch()`, `LocalizedRoute::delete()`, `LocalizedRoute::options()` or `LocalizedRoute::match()`, the same way you would use Laravel's regular routes:

```php
use App\Http\Controllers\ProfileController;
use EduLazaro\Laralang\LocalizedRoute;

// Define a localized GET route
LocalizedRoute::get('profile', [
    'en',
    'es' => 'perfil'
], [ProfileController::class, 'show'])->name('profile');

// Define a localized POST route
LocalizedRoute::post('update-profile', [
    'en',
    'es' => 'actualizar-perfil'
], [ProfileController::class, 'update'])->name('update-profile');
```

Assuming `en` is your default locale, those two calls register four routes:

| Method | URL | Route name |
|---|---|---|
| GET | `/profile` | `en.profile` |
| GET | `/es/perfil` | `es.profile` |
| POST | `/update-profile` | `en.update-profile` |
| POST | `/es/actualizar-perfil` | `es.update-profile` |

This is how it works:

* The first parameter is the URI, and it is the one used for any locale listed without a translation.
* The second parameter lists the locales. A bare value like `'en'` reuses the first parameter, and `'es' => 'perfil'` gives that locale its own path.
* The third parameter is the regular controller action or closure.
* The default locale gets no prefix, and every other locale is prefixed with its code unless you configure a different prefix.

### Inside a route group

The locale prefix always goes first, before any prefix coming from the group:

```php
use App\Http\Controllers\DashboardController;
use EduLazaro\Laralang\LocalizedRoute;

Route::prefix('admin')->group(function () {
    LocalizedRoute::get('dashboard', [
        'en',
        'fr',
        'es' => 'panel'
    ], [DashboardController::class, 'index'])->name('admin.dashboard');
});
```

| URL | Route name |
|---|---|
| `/admin/dashboard` | `en.admin.dashboard` |
| `/fr/admin/dashboard` | `fr.admin.dashboard` |
| `/es/admin/panel` | `es.admin.dashboard` |

Note that `fr` reuses `dashboard` because it was listed without a translation, while `es` uses its own `panel`. Group middleware, name prefixes and everything else you chain on the group keep working as usual.

## Localized Resources

`LocalizedResource` registers a Laravel resource once per locale. Laravel's own resource registrar builds the routes, so `only()`, `except()`, `names()`, `middleware()`, `shallow()` and `scoped()` keep working exactly as you know them, applied to every locale at once.

```php
use App\Http\Controllers\PhotoController;
use EduLazaro\Laralang\LocalizedResource;

LocalizedResource::make('photos', [
    'en',
    'es' => 'fotos',
], PhotoController::class);
```

| URL | Route name |
|---|---|
| `/photos` | `en.photos.index` |
| `/photos/create` | `en.photos.create` |
| `/photos/{photo}` | `en.photos.show` |
| `/es/fotos` | `es.photos.index` |
| `/es/fotos/create` | `es.photos.create` |
| `/es/fotos/{photo}` | `es.photos.show` |

Two things are deliberate here. The route names use the base resource name in every locale, so `route('photos.index')` resolves to the current language like any other localized route. And the route parameter is `{photo}` in every locale, derived from the base name too, so a single `show(Photo $photo)` controller signature keeps working no matter which language resolved the URL.

The first parameter is the base name, used for names and parameters. The second lists the locales, exactly like the other methods. The third is the resource controller.

### Translating create and edit

Pass an array instead of a string to give a locale its own `create` and `edit` segments:

```php
LocalizedResource::make('photos', [
    'en',
    'es' => ['uri' => 'fotos', 'verbs' => ['create' => 'crear', 'edit' => 'editar']],
], PhotoController::class);
```

| URL | Route name |
|---|---|
| `/photos/create` | `en.photos.create` |
| `/photos/{photo}/edit` | `en.photos.edit` |
| `/es/fotos/crear` | `es.photos.create` |
| `/es/fotos/{photo}/editar` | `es.photos.edit` |

Without the `verbs` key those two segments stay in English for every locale.

### API resources

`LocalizedResource::api()` registers the same resource without the `create` and `edit` routes:

```php
LocalizedResource::api('photos', ['en', 'es' => 'fotos'], PhotoController::class);
```

Localizing an API is rarely what you want, since its URLs are consumed by machines and the language is usually negotiated with the `Accept-Language` header. It is here for APIs meant to be browsed, not for integration endpoints.

### Chaining options

Anything you would chain on `Route::resource()` is forwarded to every locale:

```php
LocalizedResource::make('photos', ['en', 'es' => 'fotos'], PhotoController::class)
    ->only(['index', 'show'])
    ->middleware('auth');
```

### The LocalizedRoute bridge

If you prefer the spelling that mirrors Laravel, `LocalizedRoute::resource()` and `LocalizedRoute::apiResource()` do exactly the same, delegating to `LocalizedResource`:

```php
LocalizedRoute::resource('photos', ['en', 'es' => 'fotos'], PhotoController::class);
LocalizedRoute::apiResource('photos', ['en', 'es' => 'fotos'], PhotoController::class);
```

## How to Use Localized Routes in Views

To generate URLs for your localized routes, you can use the `route()` helper as usual:

```php
route('en.dashboard') // English
route('es.dashboard') // Spanish
```

However if you run just `route('dashboard')` it will also work, and it will redirect to the route named `dashboard` of the current locale.

## Ziggy

If Ziggy is installed, Laralang replaces its Blade route generator so the routes of the current locale are also published under their unprefixed name. That way `route('dashboard')` behaves in JavaScript exactly like it does in PHP, while `route('es.dashboard')` keeps working too.

Both major versions are supported and detected automatically, with no configuration:

* Ziggy 2, namespace `Tighten\Ziggy`.
* Ziggy 1, namespace `Tightenco\Ziggy`.

A route registered directly under the unprefixed name, for example a plain `Route::get(...)->name('dashboard')`, is never overwritten by the alias.

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

- `$params`: override route parameters. Defaults to the current route's resolved parameters, so dynamic segments like `{slug}` are carried into every locale URL automatically.
- `$absolute`: set to `false` for relative paths instead of absolute URLs (useful for sitemaps where you build the host yourself).

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

All locale middlewares are idempotent. Applying `SetSmartLocale` to a group and using `LocalizedRoute` (which auto-attaches `SetRouteLocale` per route) is safe: the first middleware to run resolves the locale and the rest become no-ops. Build whatever middleware stack you need without worrying about double execution or duplicate redirects.

If every route is created via `LocalizedRoute`, you do not need to add `SetSmartLocale` to your group, since `SetRouteLocale` is already injected per route. Add `SetSmartLocale` only when you have a mix of localized and plain routes and want the plain ones to inherit the session/browser locale.

### SetRouteLocale

This middleware will detect the locale from the URL prefix and apply it.

If you have localized routes with prefixes (e.g., /es/dashboard), this middleware ensures the application locale matches the URL.

```php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use EduLazaro\Laralang\Http\Middleware\SetRouteLocale;
use EduLazaro\Laralang\LocalizedRoute;

Route::middleware(['web', SetRouteLocale::class])
    ->group(function () {
        LocalizedRoute::get('dashboard', [
            'en',
            'es' => 'panel'
        ], [DashboardController::class, 'index'])->name('dashboard');

        LocalizedRoute::get('profile', [
            'en',
            'es' => 'perfil'
        ], [ProfileController::class, 'show'])->name('profile');
    });
```

Visiting `/es/panel` sets the application locale to `es` before the controller runs, so `__()`, dates and anything else locale aware already use Spanish. Visiting `/dashboard` does the same for the default locale.

### SetSessionLocale

This middleware applies the locale stored in the user session.

Useful for internal routes like dashboards or admin panels, where the locale is determined once and stored in the session.

```php
use App\Http\Controllers\Admin\OrderController;
use EduLazaro\Laralang\Http\Middleware\SetSessionLocale;

Route::middleware(['web', 'auth', SetSessionLocale::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->name('admin.orders');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');

        Route::post('locale/{locale}', function (string $locale) {
            session()->put('locale', $locale);

            return back();
        })->name('admin.locale');
    });
```

These URLs never change, whatever the language. The locale comes from `session('locale')`, which the language switcher writes, so the panel stays in the language the user picked until they pick another one.

### SetBrowserLocale

This middleware reads the locale from the browser's Accept-Language header only if no session locale is already set.

On first visit, it detects the preferred browser language and stores it in the session. Good for public routes to auto-detect a first-time visitor's language and store it for subsequent requests.

```php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PricingController;
use EduLazaro\Laralang\Http\Middleware\SetBrowserLocale;

Route::middleware(['web', SetBrowserLocale::class])
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('pricing', [PricingController::class, 'index'])->name('pricing');
    });
```

A visitor arriving with `Accept-Language: es-ES` gets `/` in Spanish on the very first request, and the choice is stored in the session so the rest of the visit stays in Spanish.

### SetSmartLocale

This is the recommended "universal" middleware.

It combines all the previous strategies in this priority order:

* Route prefix locale
* Session locale or Browser locale (fallback)

If you want to apply localization globally without thinking about it, this middleware is for you.

```php
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use EduLazaro\Laralang\Http\Middleware\SetSmartLocale;
use EduLazaro\Laralang\LocalizedRoute;

Route::middleware(['web', SetSmartLocale::class])
    ->group(function () {
        // Localized: the URL prefix decides
        LocalizedRoute::get('/', ['en', 'es'], [HomeController::class, 'index'])->name('home');

        LocalizedRoute::get('contact', [
            'en',
            'es' => 'contacto'
        ], [ContactController::class, 'show'])->name('contact');

        // Plain: no prefix to read, so the session or the browser decides
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
```

`/es/contacto` runs in Spanish because of the prefix, and `/dashboard`, which has no prefix to read, falls back to the session and then to the browser language.

## Author

Created by [Edu Lazaro](https://edulazaro.com)

## License

Laralang is open-sourced software licensed under the [MIT license](LICENSE.md).