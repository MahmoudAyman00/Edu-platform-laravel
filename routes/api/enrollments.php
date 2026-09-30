<?php

use App\Http\Controllers\Api\Student\EnrollmentController;
use App\Http\Controllers\Api\Student\PlaybackController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('enrollments')->group(function () {
    Route::get('/mine', [EnrollmentController::class, 'mine']);
    Route::post('/enroll', [EnrollmentController::class, 'enroll']);
    Route::get('/lessons/{lesson}/playback', [PlaybackController::class, 'show']);
});
