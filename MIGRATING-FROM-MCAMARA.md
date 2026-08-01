# Migrating from mcamara/laravel-localization

Both packages localize URLs, but they do it in opposite ways, and understanding that difference makes the rest of the migration obvious.

**mcamara registers one route** and gives it a prefix that is resolved at boot time (`LaravelLocalization::setLocale()`), with the translated paths living in `lang/{locale}/routes.php`.

**Laralang registers one route per locale**, declared in a single call in the routes file, each with its own path and its own name (`en.about`, `es.about`).

That is why in Laralang there are no route translation files, `route:list` shows every language, and you can link to a specific locale by name.

## Route names gain a locale prefix

This is what mcamara registers, checked against version 2.4:

```
NOMBRE                URI
about                 /about
```

One route, one name, and the prefix comes from whatever locale the current request resolved to. In Laralang the same route becomes two:

```
en.about              /about
es.about              /es/sobre-nosotros
```

`route('about')` keeps working in both packages, so most of your code is fine. What breaks is anything that reads or compares the name directly:

```php
Route::has('about')                     // true with mcamara, false with Laralang
Route::currentRouteName() === 'about'   // now returns 'es.about'
```

Search for `Route::has(`, `currentRouteName()` and `getName()` before you start. For active state checks in navigation, `request()->routeIs('about')` works in both, because Laralang makes bare patterns match every locale.

## Before you start: your default language URLs will change

mcamara ships `hideDefaultLocaleInURL => false`, so unless you changed it, your default language lives at `/en/about`. In Laralang **the default locale never carries a prefix**, so that page becomes `/about`.

If your site is already indexed, plan the redirects before deploying. A `301` from every old URL to the new one, and a fresh sitemap. This is the single most expensive part of the migration, and it has nothing to do with code.

If you had `hideDefaultLocaleInURL => true`, your URLs do not change at all and you can skip this.

## Step 1: install

```bash
composer remove mcamara/laravel-localization
composer require edulazaro/laralang
php artisan vendor:publish --tag="locales"
```

## Step 2: config

`config/laravellocalization.php` becomes `config/locales.php`.

| mcamara | Laralang |
|---|---|
| `supportedLocales` (array of arrays with name, script, native, regional) | `locales` (flat array of codes) |
| `hideDefaultLocaleInURL` | not configurable, the default locale never has a prefix |
| `useAcceptLanguageHeader` | the `SetBrowserLocale` middleware |
| `localesMapping` | `prefixes`, which maps each locale to the prefix you want |
| `urlsIgnored` | not needed, Laralang only touches the routes you declare with `LocalizedRoute` |
| `utf8suffix`, `localesOrder` | no equivalent |

```php
// config/locales.php
'locales'  => ['en', 'es', 'fr'],

'prefixes' => [
    'en' => '',
    'es' => 'es',
    'fr' => 'fr',
],
```

The locale metadata that mcamara carries in its config (native name, direction, script) is not part of Laralang. If you were using it for a language switcher, move it to an enum or a config file of your own.

## Step 3: routes

This is the real work. The prefix group and the route translation files both disappear.

**Before:**

```php
// routes/web.php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect'],
], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get(LaravelLocalization::transRoute('routes.about'), [AboutController::class, 'show'])->name('about');
});
```

```php
// lang/en/routes.php
return ['about' => 'about'];

// lang/es/routes.php
return ['about' => 'sobre-nosotros'];
```

**After:**

```php
// routes/web.php
use EduLazaro\Laralang\LocalizedRoute;

Route::middleware(['web'])->group(function () {
    LocalizedRoute::get('/', ['en', 'es'], [HomeController::class, 'index'])->name('home');

    LocalizedRoute::get('about', [
        'en',
        'es' => 'sobre-nosotros',
    ], [AboutController::class, 'show'])->name('about');
});
```

Delete `lang/{locale}/routes.php`. The translations now live next to the route they belong to.

Note that the locale detection middleware is not in the group any more. Every route created with `LocalizedRoute` carries `SetRouteLocale` on its own, so there is nothing to add for them. See step 4 for the routes that are not localized.

### Resources

```php
// Before
Route::resource(LaravelLocalization::transRoute('routes.photos'), PhotoController::class);

// After
use EduLazaro\Laralang\LocalizedResource;

LocalizedResource::make('photos', [
    'en',
    'es' => ['uri' => 'fotos', 'verbs' => ['create' => 'crear', 'edit' => 'editar']],
], PhotoController::class);
```

## Step 4: middleware

| mcamara | Laralang |
|---|---|
| `localize` (`LaravelLocalizationRoutes`) | not needed, translations are in the route definition |
| `localizationRedirect` (`LaravelLocalizationRedirectFilter`) | built into `SetRouteLocale`, which redirects the default locale prefix to the clean URL |
| `localeSessionRedirect` (`LocaleSessionRedirect`) | `SetSessionLocale` |
| `localeCookieRedirect` (`LocaleCookieRedirect`) | no equivalent, use the session |
| `localeViewPath` (`LaravelLocalizationViewPath`) | no equivalent, see below |

Laralang adds one middleware that mcamara does not have, `SetSmartLocale`, which reads the prefix when there is one and falls back to session or browser when there is not. Use it on groups that mix localized and plain routes.

**About `localeViewPath`**: mcamara can resolve `resources/views/es/about.blade.php` per language. Laralang does not, on purpose. A view per language means the same template duplicated N times, and they drift. Translate the strings inside a single view instead. If you really want per-language views, nothing stops you from returning `view('about.' . app()->getLocale())` from the controller.

## Route caching gets simpler

Because mcamara resolves the prefix while the routes are being registered, `php artisan route:cache` freezes a single language into the cache. That is why the package ships its own commands:

```bash
php artisan route:trans:cache
php artisan route:trans:clear
php artisan route:trans:list
```

With Laralang there is nothing special to do. Every locale is a real route in the collection, so plain `route:cache` and `route:list` work. Drop the `route:trans:*` commands from your deployment scripts and use Laravel's own.

## Step 5: URLs in views

| mcamara | Laralang |
|---|---|
| `LaravelLocalization::getLocalizedURL($locale)` | `Laralang::alternates()`, which returns every locale at once |
| `LaravelLocalization::getURLFromRouteNameTranslated($locale, 'routes.about')` | `route("{$locale}.about")` |
| `route('about')` inside the prefix group | `route('about')`, which resolves to the current locale |
| `LaravelLocalization::getNonLocalizedURL($url)` | no equivalent, work with route names |
| `LaravelLocalization::getCurrentLocale()` | `app()->getLocale()` |
| `LaravelLocalization::getSupportedLanguagesKeys()` | `config('locales.locales')` |
| `getCurrentLocaleName()`, `getCurrentLocaleNative()`, `getCurrentLocaleDirection()`, `getCurrentLocaleScript()` | no equivalent, keep that metadata in your app |

The language switcher becomes shorter, because you no longer ask locale by locale:

```blade
{{-- Before --}}
@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
    <a href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
        {{ $properties['native'] }}
    </a>
@endforeach

{{-- After --}}
@foreach(Laralang::alternates() as $locale => $url)
    <a href="{{ $url }}">{{ strtoupper($locale) }}</a>
@endforeach
```

Both `getLocalizedURL()` and `getURLFromRouteNameTranslated()` return absolute URLs (`http://example.com/es/sobre-nosotros`). `alternates()` does too by default, and takes `false` as its second argument when you want relative paths.

`alternates()` only returns the locales that actually have a route registered, so a page that exists in two of your three languages produces two links instead of a dead one. That is exactly what `hreflang` needs:

```blade
@foreach(Laralang::alternates() as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
@endforeach
```

The same call feeds a multi locale sitemap, with `laralang_alternates([], false)` if you build the host yourself.

## What you gain

- **A route per locale, addressable by name.** `route('es.about')` works, `route:list` shows every language, and Ziggy exposes them to JavaScript.
- **Route model binding per locale.** Each language can resolve against its own column, which mcamara cannot do with a single route definition:

  ```php
  LocalizedRoute::get('property/{property:slug_en}', [
      'en' => 'property/{property:slug_en}',
      'es' => 'propiedad/{property:slug_es}',
  ], [PropertyController::class, 'show'])->name('properties.show');
  ```

- **Translations next to the route**, instead of in a separate lang file per language.
- **`routeIs()` that understands locales**: `request()->routeIs('about')` is true on `en.about` and on `es.about`.

## What you lose

- Prefixing the default locale (`hideDefaultLocaleInURL => false`).
- Per language view paths.
- The locale metadata in config (native names, direction, script).
- The cookie based redirect strategy.

## After deploying

Check `route:list` first. Every localized route should appear once per language, named `{locale}.{name}`. If a language is missing, it is not listed in `config('locales.locales')`.

Then check that the old URLs redirect. If you came from `hideDefaultLocaleInURL => false`, `/en/about` now returns a `301` to `/about` on its own, because `SetRouteLocale` strips the default locale prefix. The other languages keep the same shape, so only the paths you actually renamed need redirects of your own.
