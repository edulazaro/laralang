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
    | Localized Fallback
    |--------------------------------------------------------------------------
    |
    | When enabled, a URL that matches no route but does exist under another
    | locale is redirected there instead of returning a 404. For example
    | /servicios reaches /es/servicios, and /es/services reaches /services.
    |
    | The default locale is tried first, so the redirect never depends on the
    | visitor and is safe to be permanent.
    |
    | If your application registers its own fallback route, leave this off and
    | call LocalizedRoute::fallback() yourself, before your own fallback: the
    | first fallback registered is the one Laravel runs.
    |
    */

    'fallback' => false,
];
