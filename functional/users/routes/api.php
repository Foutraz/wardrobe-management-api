<?php

use Functional\Users\Http\Controllers\LoginController;
use Functional\Users\Http\Controllers\LogoutController;
use Functional\Users\Http\Controllers\RefreshTokenController;
use Functional\Users\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->middleware('throttle:6,1')->name('login');

    Route::middleware(['auth:sanctum', 'locale'])->group(function (): void {
        Route::post('token/refresh', RefreshTokenController::class)->name('token.refresh');
        Route::post('logout', LogoutController::class)->name('logout');
    });
});
