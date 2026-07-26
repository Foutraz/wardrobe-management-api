<?php

use Functional\Styling\Http\Controllers\AddOutfitItemController;
use Functional\Styling\Http\Controllers\RenderOutfitPreviewController;
use Functional\Styling\Rest\Controllers\OutfitController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::prefix('v1')->middleware(['auth:sanctum', 'locale'])->group(function (): void {
    Rest::resource('outfits', OutfitController::class);

    Route::post('outfits/{outfit}/items', AddOutfitItemController::class)
        ->name('outfits.items.store');

    Route::post('outfits/{outfit}/previews', RenderOutfitPreviewController::class)
        ->name('outfits.previews.store');
});
