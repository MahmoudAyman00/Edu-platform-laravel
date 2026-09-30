<?php

use App\Http\Controllers\Api\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Api\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Api\Student\ExamController as StudentExamController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('exams')->group(function () {
    Route::get('/course/{course}', [StudentExamController::class, 'index']);
    Route::get('/{exam}', [StudentExamController::class, 'show'])->whereNumber('exam');
    Route::post('/{exam}/start', [StudentExamController::class, 'start']);
    Route::post('/{exam}/submit', [StudentExamController::class, 'submit']);
    Route::get('/{exam}/result', [StudentExamController::class, 'result']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminExamController::class, 'index']);
        Route::post('/', [AdminExamController::class, 'store']);
        Route::get('/{exam}', [AdminExamController::class, 'show']);
        Route::put('/{exam}', [AdminExamController::class, 'update']);
        Route::delete('/{exam}', [AdminExamController::class, 'destroy']);
        Route::post('/{exam}/publish', [AdminExamController::class, 'publish']);
        Route::post('/{exam}/final', [AdminExamController::class, 'setFinal']);

        Route::post('/{exam}/questions', [AdminQuestionController::class, 'store']);
        Route::post('/{exam}/questions/reorder', [AdminQuestionController::class, 'reorder']);
        Route::put('/questions/{question}', [AdminQuestionController::class, 'update']);
        Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy']);
    });
});
