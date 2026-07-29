<?php

use Functional\Wardrobe\Http\Controllers\MarkGarmentWornController;
use Functional\Wardrobe\Http\Controllers\ShowWardrobeStatisticsController;
use Functional\Wardrobe\Http\Controllers\UpdateGarmentAvailabilityController;
use Functional\Wardrobe\Http\Controllers\UploadGarmentPhotoController;
use Functional\Wardrobe\Http\Controllers\UploadWishlistImageController;
use Functional\Wardrobe\Rest\Controllers\GarmentController;
use Functional\Wardrobe\Rest\Controllers\WishlistItemController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

Route::prefix('v1')->middleware(['auth:sanctum', 'locale'])->group(function (): void {
    Rest::resource('garments', GarmentController::class);
    Rest::resource('wishlist-items', WishlistItemController::class);

    Route::get('wardrobe/statistics', ShowWardrobeStatisticsController::class)
        ->name('wardrobe.statistics.show');

    Route::post('garments/{garment}/wears', MarkGarmentWornController::class)
        ->name('garments.wears.store');

    Route::put('garments/{garment}/availability', UpdateGarmentAvailabilityController::class)
        ->name('garments.availability.update');

    Route::post('wishlist-items/{wishlistItem}/image', UploadWishlistImageController::class)
        ->name('wishlist-items.image.store');

    Route::post('garments/{garment}/photos', UploadGarmentPhotoController::class)
        ->name('garments.photos.store');
});
