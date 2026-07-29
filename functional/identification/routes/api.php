<?php

use Functional\Identification\Http\Controllers\ConfirmIdentificationController;
use Functional\Identification\Http\Controllers\SubmitIdentificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'locale'])->group(function (): void {
    Route::post('identifications', SubmitIdentificationController::class)
        ->name('identifications.store');

    Route::post('identifications/{identificationRequest}/confirmation', ConfirmIdentificationController::class)
        ->name('identifications.confirmation.store');
});
