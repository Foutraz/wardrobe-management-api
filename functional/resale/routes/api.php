<?php

use Functional\Resale\Http\Controllers\PrepareVintedDraftController;
use Functional\Resale\Http\Controllers\TransitionVintedDraftController;
use Functional\Resale\Rest\Controllers\VintedListingDraftController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::prefix('v1')->middleware(['auth:sanctum', 'locale'])->group(function (): void {
    Rest::resource('vinted-listing-drafts', VintedListingDraftController::class);

    Route::post('garments/{garment}/vinted-draft', PrepareVintedDraftController::class)
        ->name('garments.vinted-draft.store');

    Route::put('vinted-listing-drafts/{draft}/status', TransitionVintedDraftController::class)
        ->name('vinted-listing-drafts.status.update');
});
