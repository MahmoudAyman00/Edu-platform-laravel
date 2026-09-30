<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Allowed Paths
    |--------------------------------------------------------------------------
    |
    | Covers the JSON API and the Reverb private-channel auth endpoint
    | (used by the SPA on another origin with Bearer tokens).
    |
    */

    'paths' => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Bearer-token auth (no cookies), so credentials are not needed.
    'supports_credentials' => false,

];
