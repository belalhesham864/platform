<?php

use App\Http\Controllers\Api\Instructor\AuthController;
use App\Http\Controllers\Api\Instructor\SettingsController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'user.type:instructor'])->group(function () {
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Settings (Public Only)
    Route::get('settings/public', [SettingsController::class, 'publicSettings']);
    Route::any('settings/{any?}', function () {
        abort(403);
    })->where('any', '.*');
    Route::any('settings', function () {
        abort(403);
    });
});
