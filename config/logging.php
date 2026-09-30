<?php

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // Fawry payment integration: webhooks, polling, reconciliation.
        // Never log card data or secrets here (reference numbers only).
        'fawry' => [
            'driver' => 'single',
            'path' => storage_path('logs/fawry.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],
    ],

];
