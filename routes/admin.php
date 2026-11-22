<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'user.type:admin'])->group(function () {
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Settings
    Route::get('settings/public', [SettingsController::class, 'publicSettings']);
    Route::apiResource('settings', SettingsController::class);
});
