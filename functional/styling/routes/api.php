<?php

use Functional\Styling\Http\Controllers\AddOutfitItemController;
use Functional\Styling\Http\Controllers\CreateAvatarVersionController;
use Functional\Styling\Http\Controllers\RenderOutfitPreviewController;
use Functional\Styling\Rest\Controllers\AvatarController;
use Functional\Styling\Rest\Controllers\OutfitController;
use Functional\Styling\Rest\Controllers\OutfitPlanController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::prefix('v1')->middleware(['auth:sanctum', 'locale'])->group(function (): void {
    Rest::resource('outfits', OutfitController::class);
    Rest::resource('avatars', AvatarController::class);
    Rest::resource('outfit-plans', OutfitPlanController::class);

    Route::post('outfits/{outfit}/items', AddOutfitItemController::class)
        ->name('outfits.items.store');

    Route::post('outfits/{outfit}/previews', RenderOutfitPreviewController::class)
        ->name('outfits.previews.store');

    Route::post('avatars/{avatar}/versions', CreateAvatarVersionController::class)
        ->name('avatars.versions.store');
});
