<?php

use Functional\Catalog\Http\Controllers\MergeProductsController;
use Functional\Catalog\Http\Controllers\VerifyProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/back-office')->middleware(['auth:sanctum', 'locale'])->group(function (): void {
    Route::put('products/{product}/verification', VerifyProductController::class)
        ->name('back-office.products.verification.update');

    Route::post('products/{product}/merge', MergeProductsController::class)
        ->name('back-office.products.merge.store');
});
