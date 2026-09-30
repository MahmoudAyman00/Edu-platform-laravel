<?php

use Illuminate\Support\Facades\Route;

// Aggregator: 10 files = 10 features (per spec).
foreach ([
    'auth',
    'users',
    'categories',
    'courses',
    'enrollments',
    'progress',
    'exams',
    'certificates',
    'payments',
    'notifications',
] as $file) {
    $path = __DIR__.'/api/'.$file.'.php';

    if (file_exists($path)) {
        require $path;
    }
}
