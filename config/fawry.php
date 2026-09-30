<?php

return [
    'base_url' => env('FAWRY_BASE_URL', 'https://atfawry.fawrystaging.com'),
    'merchant_code' => env('FAWRY_MERCHANT_CODE'),
    'security_key' => env('FAWRY_SECURITY_KEY'),
    'webhook_secret' => env('FAWRY_WEBHOOK_SECRET'),

    // Reference code validity in hours (48h per assumptions).
    'reference_ttl_hours' => (int) env('FAWRY_REFERENCE_TTL_HOURS', 48),
];
