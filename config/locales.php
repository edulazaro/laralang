<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | These are the languages your application supports.
    |
    */

    'locales' => [
        'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Prefixes
    |--------------------------------------------------------------------------
    |
    | Define optional URL prefixes for each locale.
    | Leave empty '' for locales that don't require a prefix.
    |
    */

    'prefixes' => [
        'en' => '', // English (default URL: /)
        'es' => 'es', // Spanish (URL: /es)
        'fr' => 'fr', // French (URL: /fr)
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Domains (Future Support)
    |--------------------------------------------------------------------------
    |
    | Define optional domains per locale.
    | Leave null if no dedicated domain is required.
    |
    */

    'domains' => [
        'en' => null,
        'es' => null,
        'fr' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Use Optimised URL Generator
    |--------------------------------------------------------------------------
    |
    | By default, Laralang replaces Laravel's URL generator to ensure that
    | all generated URLs (such as those created with the `route()` helper)
    | automatically include the correct locale prefix.
    |
    | This allows you to write routes like `route('dashboard')`, and Laralang
    | will generate a localized URL based on the current locale context,
    | without manually specifying the locale each time.
    |
    | If you prefer to use Laravel's default URL generator and handle
    | localization manually, you can disable this by setting it to false.
    |
    */
    'override_url_generator' => true,
];
