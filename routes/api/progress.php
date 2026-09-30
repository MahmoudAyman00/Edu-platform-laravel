<?php

use App\Http\Controllers\Api\Student\ProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('progress')->group(function () {
    Route::get('/my-courses', [ProgressController::class, 'myCourses']);
    Route::get('/courses/{course}', [ProgressController::class, 'show']);
    Route::post('/lessons/{lesson}/complete', [ProgressController::class, 'complete']);
});
