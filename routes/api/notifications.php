<?php

use App\Http\Controllers\Api\Student\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/read-all', [NotificationController::class, 'markAllRead']);
});
