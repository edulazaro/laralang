# Upgrade Guide

## From 1.x to 2.0

Laralang 2.0 is a small, focused major release: it drops support for Laravel 10, removes a rarely-used opt-out flag, and ships three behaviour improvements. The migration is short for most users.

### High impact — action required

#### 1. Laravel 11 is now required

`composer.json` now requires `laravel/framework: ^11.0` (previously `>=10.0`). If you are still on Laravel 10, either upgrade your app to Laravel 11 first or pin to Laralang `^1.5`.

```diff
- "edulazaro/laralang": "^1.0"
+ "edulazaro/laralang": "^2.0"
```

No other PHP or package changes: PHP minimum is still `^8.2`.

#### 2. `locales.override_url_generator` has been removed

The localized URL generator (which rewrites `route('dashboard')` into `/es/dashboard` / `/ca/dashboard` / etc. based on the current locale) is now always active. The `override_url_generator` config key has been removed entirely.

If you had this set to its default of `true` (or never touched the config), **do nothing** — behaviour is identical.

If you had set it to `false`, you must now either:

- Use Laravel's `URL` facade (`URL::to('/foo')`) or the `url()` helper for the URLs where you wanted the un-localized generator, or
- Stay on Laralang `1.x`.

```diff
  // config/locales.php
- 'override_url_generator' => true,
```

Delete that line if it's still in your published config — it is no longer read.

### Medium impact — review

#### 3. `routeIs()` with a locale prefix now checks the current locale

Previously, `request()->routeIs('es.services')` returned `true` whenever the *current route name* was `es.services`, regardless of what `app()->getLocale()` said. In 2.0, the check also requires `app()->getLocale() === 'es'`.

This was almost always the behaviour users expected — active-state checks in navigation should only light up the Spanish entry when the app is actually in Spanish. But if you were relying on the old "route name only" semantics (for example, forcing `App::setLocale('en')` mid-request while a Spanish route was matched), those checks will now return `false`.

**Why this changed:** in 1.x, `routeIs('services')` never matched any localized route, forcing users to write `routeIs('*.services')` everywhere. The new behaviour — plain patterns match any locale, prefixed patterns require the current locale to match — is consistent with how you read the code.

**How to migrate:** search your codebase for `routeIs('<locale>.<something>')` calls. If any of them assumes the pattern matches independently of `App::getLocale()`, either:

- Drop the prefix (`routeIs('something')`) — matches every locale variant, which is usually what you want, or
- Keep the prefix and make sure the app locale is actually set to the one you're checking, or
- Fall back to the legacy workaround (`routeIs('*.something')`) which still works.

### No action needed — improvements

#### Middlewares are now idempotent

`SetSmartLocale`, `SetRouteLocale`, `SetBrowserLocale` and `SetSessionLocale` are safe to apply twice in the same request. Previously, combining a group-level `SetSmartLocale` with `LocalizedRoute` (which auto-attaches `SetRouteLocale` per route) could trigger spurious 301 redirects on locale-prefixed URLs. That bug is fixed.

If you had a mixed middleware stack that was working around this issue, you can simplify it now — but nothing breaks if you leave it as-is.

#### `Laralang::alternates()` helper

New API for language switchers, `hreflang` tags and multi-locale sitemaps:

```php
use EduLazaro\Laralang\Facades\Laralang;

Laralang::alternates();
// [
//     'en' => 'https://example.com/services',
//     'es' => 'https://example.com/es/servicios',
//     'ca' => 'https://example.com/ca/serveis',
// ]
```

See the "Generating Alternate URLs" section of the README for the full contract. Purely additive — existing code is unaffected.

### Quick checklist

- [ ] Upgrade app to Laravel 11 if not already.
- [ ] Bump `edulazaro/laralang` to `^2.0` in `composer.json`.
- [ ] Remove `'override_url_generator'` from `config/locales.php` if it's published.
- [ ] Grep for `routeIs('<locale>.` calls and audit them (usually fine, sometimes worth simplifying to `routeIs('<name>')`).
- [ ] `composer update edulazaro/laralang` and run your test suite.
