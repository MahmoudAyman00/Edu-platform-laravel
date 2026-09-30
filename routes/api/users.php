<?php

use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Student\UserController as StudentUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [StudentUserController::class, 'show']);
    Route::put('/me', [StudentUserController::class, 'update']);

    Route::middleware('role:admin')->prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::post('/', [AdminUserController::class, 'store']);
        Route::put('/{user}', [AdminUserController::class, 'update']);
        Route::delete('/{user}', [AdminUserController::class, 'destroy']);
    });
});
