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
];
