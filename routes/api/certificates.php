<?php

use App\Http\Controllers\Api\Public\CertificateVerificationController;
use App\Http\Controllers\Api\Student\CertificateController as StudentCertificateController;
use Illuminate\Support\Facades\Route;

// Public certificate verification (no auth).
Route::get('/certificates/verify/{serial}', [CertificateVerificationController::class, 'verify']);

Route::middleware('auth:sanctum')->prefix('certificates')->group(function () {
    Route::get('/mine', [StudentCertificateController::class, 'index']);
    Route::get('/{certificate}/download', [StudentCertificateController::class, 'download'])
        ->whereNumber('certificate');
});
