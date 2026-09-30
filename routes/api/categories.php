<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Student\CategoryController as StudentCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('categories')->group(function () {
    Route::get('/', [StudentCategoryController::class, 'index']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/', [AdminCategoryController::class, 'store']);
        Route::get('/{category}', [AdminCategoryController::class, 'show']);
        Route::put('/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('/{category}', [AdminCategoryController::class, 'destroy']);
    });
});
