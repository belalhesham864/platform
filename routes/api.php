<?php

use App\Http\Controllers\Api\Student\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Dedicated Public Settings endpoint (unauthenticated / guest)
Route::get('settings/public', [SettingsController::class, 'publicSettings']);
Route::get('v1/settings/public', [SettingsController::class, 'publicSettings']);

Route::prefix('admin')->group(base_path('routes/admin.php'));
Route::prefix('instructor')->group(base_path('routes/instructor.php'));
Route::prefix('student')->group(base_path('routes/student.php'));

// Also support v1 prefix aliases: /api/v1/admin, /api/v1/instructor, /api/v1/student
Route::prefix('v1/admin')->group(base_path('routes/admin.php'));
Route::prefix('v1/instructor')->group(base_path('routes/instructor.php'));
Route::prefix('v1/student')->group(base_path('routes/student.php'));
