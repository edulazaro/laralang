# Migrating from niels-numbers/laravel-localizer

`niels-numbers/laravel-localizer` and Laralang solve the same problem and even share an implementation detail, since both replace Laravel's URL generator so `route('about')` resolves to the current language. What differs is how the routes get registered and how they are named.

**laravel-localizer wraps a closure.** `Route::localize()` registers the routes inside it twice: once under a dynamic `{locale}` segment and once with no prefix. `Route::translate()` is the variant for per language paths, and it does run once per locale.

**Laralang declares each route once**, listing the locales and their paths in the same call, and registers one real route per locale.

The practical consequence is the route names, which is where the migration actually happens.

## Route names change

This is the part that touches your code the most.

This is what a `Route::localize()` and a `Route::translate()` actually register, checked against version 1.4:

```
with_locale.about          /{locale}/about
without_locale.about       /about
translated_en.contact      /en/contact
without_locale.contact     /contact
translated_de.contact      /de/kontakt
```

And this is how they map:

| laravel-localizer | Laralang |
|---|---|
| `with_locale.about` | `en.about`, `de.about`, `fr.about` |
| `without_locale.about` | the default locale route, `en.about` |
| `translated_de.about` | `de.about` |

Search your codebase for `with_locale.`, `without_locale.` and `translated_` before you start. Anything that names a route explicitly needs rewriting, and `route('about')` keeps working in both packages, so those calls are fine.

Note two things in that listing. `Route::localize()` puts the locale in a `{locale}` route parameter instead of registering a route per language, and the default locale ends up reachable at two URLs, `/contact` and `/en/contact`. In Laralang there is exactly one URL per locale, and `/en/contact` does not exist when English is the default.

## Your URLs do not change

`laravel-localizer` ships `hide_default_locale => true`, and Laralang always hides it. So the default language stays at `/about` and the rest keep their prefix. No redirects to plan, unlike a migration from mcamara.

Three exceptions worth checking:

- If you set `hide_default_locale => false`, your default language lives at `/en/about` and will move to `/about`. Plan the `301` redirects.
- `laravel-localizer` matches the locale segment case insensitively, so `/EN/about` works and redirects to the canonical form. Laralang does not, and `/EN/about` returns a 404. If you have those URLs indexed, handle them at the web server or with a fallback route.
- Which locale counts as the default is read from `app.fallback_locale` by `laravel-localizer` and from `app.locale` by Laralang. If those two differ in your app, the language that loses its prefix changes with the migration.

## Step 1: install

```bash
composer remove niels-numbers/laravel-localizer
composer require edulazaro/laralang
php artisan vendor:publish --tag="locales"
```

Note that `laravel-localizer` supports Laravel 9 to 13, while Laralang requires Laravel 11 or newer. Check that first if you are on an older application.

## Step 2: config

`config/localizer.php` becomes `config/locales.php`.

| laravel-localizer | Laralang |
|---|---|
| `supported_locales` | `locales` |
| `hide_default_locale` | not configurable, the default locale never has a prefix |
| `redirect_enabled` | not configurable, `SetRouteLocale` always redirects the default locale prefix to the clean URL |
| `persist_locale.session` | the locale middlewares always write the session |
| `persist_locale.cookie` | no equivalent |
| `detectors` | the `SetBrowserLocale` middleware covers `BrowserDetector` |
| `locale_directions` | no equivalent |

```php
// config/locales.php
'locales'  => ['en', 'de', 'fr'],

'prefixes' => [
    'en' => '',
    'de' => 'de',
    'fr' => 'fr',
],
```

Laralang has no detector chain. `BrowserDetector` maps to `SetBrowserLocale`, but there is no equivalent to `UserDetector` or to a custom detector: reading the locale from the authenticated user is something you write yourself, in a middleware of your own that sets the locale before Laralang's middlewares run.

Text direction (`locale_directions`) is not part of Laralang either. If your layout uses it, move that map to an enum or a config file in your app.

## Step 3: routes

### Same path in every language

**Before:**

```php
Route::localize(function () {
    Route::get('about', [AboutController::class, 'show'])->name('about');
    Route::get('contact', [ContactController::class, 'show'])->name('contact');
});
```

**After:**

```php
use EduLazaro\Laralang\LocalizedRoute;

LocalizedRoute::get('about', ['en', 'de', 'fr'], [AboutController::class, 'show'])->name('about');
LocalizedRoute::get('contact', ['en', 'de', 'fr'], [ContactController::class, 'show'])->name('contact');
```

The closure disappears and each route lists its locales. There is no wrapper because there is nothing to wrap: the locales are an argument.

### Translated paths

**Before:**

```php
use NielsNumbers\LaravelLocalizer\Facades\Localizer;

Route::translate(function () {
    Route::get(Localizer::url('about'), [AboutController::class, 'show'])->name('about');
});
```

```php
// lang/en/routes.php
return ['about' => 'about'];

// lang/de/routes.php
return ['about' => 'ueber'];
```

**After:**

```php
LocalizedRoute::get('about', [
    'en',
    'de' => 'ueber',
    'fr' => 'a-propos',
], [AboutController::class, 'show'])->name('about');
```

The translations move into the route definition, so there is no `Localizer::url()` call and you can delete `lang/{locale}/routes.php`.

Watch the lookup keys while you convert. `laravel-localizer` translates the whole URI at once, so a nested route is keyed as `'blog/post/{slug}' => 'artikel/{slug}'`, parameters included. In Laralang the same route is written as `LocalizedRoute::get('blog/post/{slug}', ['en', 'de' => 'artikel/{slug}'], ...)`, so the parameters travel with the path in the same way.

### Resources

```php
use EduLazaro\Laralang\LocalizedResource;

LocalizedResource::make('photos', [
    'en',
    'de' => 'fotos',
], PhotoController::class);
```

## Step 4: helpers

| laravel-localizer | Laralang |
|---|---|
| `Route::localizedUrl($locale)` | `Laralang::alternates()[$locale]` |
| `Route::localizedSwitcherUrl($locale)` | `Laralang::alternates()`, which returns every locale at once |
| `Route::hasLocalized($name)` | `Route::has("{$locale}.{$name}")` |
| `Route::baseName()` / `Route::currentBaseName()` | `Str::after(Route::currentRouteName(), '.')` |
| `Route::isLocalized()` | no equivalent, check whether the route name starts with a supported locale |
| `route('about')` | `route('about')`, unchanged |

The language switcher gets shorter, since you ask once instead of per locale:

```blade
{{-- Before --}}
@foreach(config('localizer.supported_locales') as $locale)
    <a href="{{ Route::localizedSwitcherUrl($locale) }}">{{ strtoupper($locale) }}</a>
@endforeach

{{-- After --}}
@foreach(Laralang::alternates() as $locale => $url)
    <a href="{{ $url }}">{{ strtoupper($locale) }}</a>
@endforeach
```

`alternates()` only returns the locales that actually have a route registered, which is what you want for `hreflang` tags and sitemaps too.

## Step 5: middleware

`laravel-localizer` does its work through the macros and the detector chain, so there is nothing to map one to one. In Laralang:

- Routes created with `LocalizedRoute` already carry `SetRouteLocale`, so localized routes need nothing.
- For plain routes, add `SetSmartLocale` to the group, which reads the prefix when there is one and falls back to session or browser when there is not.
- For a panel whose URLs never change language, use `SetSessionLocale`.

## What you gain

- **Route model binding per locale**, which the closure based approach cannot express, since the routes inside it share a single definition:

  ```php
  LocalizedRoute::get('property/{property:slug_en}', [
      'en' => 'property/{property:slug_en}',
      'de' => 'immobilie/{property:slug_de}',
  ], [PropertyController::class, 'show'])->name('properties.show');
  ```

- **Route names by locale**: `route('de.about')` is addressable, and `route:list` shows one row per language with a readable name.
- **`routeIs()` that understands locales**: `request()->routeIs('about')` is true on `en.about` and on `de.about`.
- **Ziggy support**, for both Ziggy 1 and 2, exposing the current locale routes under their unprefixed name.

## What you lose

- The detector chain, and with it `UserDetector` and any custom detector.
- Cookie persistence.
- Text direction per locale.
- Case insensitive locale segments.
- Laravel 9 and 10 support.
- A route table that does not grow with the number of languages. `Route::localize()` registers two routes per definition regardless of how many locales you have, while Laralang registers one per locale. With three languages a hundred localized definitions go from two hundred routes to three hundred.

## After deploying

Check `route:list` first. Every localized route should appear once per language, named `{locale}.{name}`, and no name should still start with `with_locale`, `without_locale` or `translated_`. If a language is missing, it is not listed in `config('locales.locales')`.
