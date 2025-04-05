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

## License

Larakeep is open-sourced software licensed under the [MIT license](LICENSE.md).