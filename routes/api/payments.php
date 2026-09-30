<?php

use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Student\PaymentController as StudentPaymentController;
use App\Http\Controllers\Api\Webhooks\FawryWebhookController;
use Illuminate\Support\Facades\Route;

// Fawry server callback: public, signature-protected (no auth).
Route::post('/payments/webhook/fawry', [FawryWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::post('/initiate', [StudentPaymentController::class, 'initiate']);
    Route::get('/{payment}', [StudentPaymentController::class, 'show'])->whereNumber('payment');

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminPaymentController::class, 'index']);
        Route::post('/{payment}/refresh', [AdminPaymentController::class, 'refresh']);
    });
});
