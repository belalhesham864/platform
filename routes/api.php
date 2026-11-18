<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(base_path('routes/admin.php'));
Route::prefix('instructor')->group(base_path('routes/instructor.php'));
Route::prefix('student')->group(base_path('routes/student.php'));

// Also support v1 prefix aliases: /api/v1/admin, /api/v1/instructor, /api/v1/student
Route::prefix('v1/admin')->group(base_path('routes/admin.php'));
Route::prefix('v1/instructor')->group(base_path('routes/instructor.php'));
Route::prefix('v1/student')->group(base_path('routes/student.php'));
