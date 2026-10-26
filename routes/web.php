<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - CAMS STCW Exam Engine API v2.5
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'system' => 'CAMS STCW Engine API v2.5',
        'institution' => 'Baliwag Maritime Academy',
        'timestamp' => now()->toIso8601String(),
    ]);
});