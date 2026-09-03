<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ExamSubmissionController;
use App\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);


/*
|--------------------------------------------------------------------------
| Protected CAMS API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('courses', CourseController::class);

    Route::apiResource('questions', QuestionController::class);

    Route::apiResource('exam-submissions', ExamSubmissionController::class)
        ->only(['index', 'store', 'show', 'destroy']);

    Route::apiResource('audit-logs', AuditLogController::class)
        ->only(['index', 'store', 'show']);
});