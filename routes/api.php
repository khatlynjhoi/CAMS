<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseOutcomeController;
use App\Http\Controllers\LearningOutcomeController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamSubmissionController;
use App\Http\Controllers\AuditLogController;

// Auth Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Assessment Engine & Exam Routes
Route::post('/exams/generate', [ExamController::class, 'generate']);
Route::get('/saved-tests', [ExamController::class, 'getSavedTests']);

// Resource Routes
Route::apiResource('users', UserController::class);
Route::apiResource('questions', QuestionController::class);
Route::apiResource('courses', CourseController::class);
Route::apiResource('course-outcomes', CourseOutcomeController::class);
Route::apiResource('learning-outcomes', LearningOutcomeController::class);
Route::apiResource('exam-submissions', ExamSubmissionController::class);
Route::apiResource('audit-logs', AuditLogController::class);
