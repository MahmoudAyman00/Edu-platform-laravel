<?php

use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Api\Student\CourseController as StudentCourseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('courses')->group(function () {
    Route::get('/', [StudentCourseController::class, 'index']);
    Route::get('/{course}', [StudentCourseController::class, 'show'])->whereNumber('course');

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminCourseController::class, 'index']);
        Route::post('/', [AdminCourseController::class, 'store']);
        Route::get('/{course}', [AdminCourseController::class, 'show']);
        Route::put('/{course}', [AdminCourseController::class, 'update']);
        Route::post('/{course}/publish', [AdminCourseController::class, 'publish']);
        Route::post('/{course}/archive', [AdminCourseController::class, 'archive']);

        Route::post('/{course}/lessons', [AdminLessonController::class, 'store']);
        Route::post('/{course}/lessons/reorder', [AdminLessonController::class, 'reorder']);
        Route::post('/{course}/lessons/upload-url', [AdminLessonController::class, 'uploadUrl']);
        Route::put('/lessons/{lesson}', [AdminLessonController::class, 'update']);
        Route::delete('/lessons/{lesson}', [AdminLessonController::class, 'destroy']);
    });
});
