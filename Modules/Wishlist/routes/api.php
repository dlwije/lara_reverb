<?php

use Illuminate\Support\Facades\Route;
use Modules\Wishlist\Http\Controllers\WishlistController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('wishlists', WishlistController::class)->names('wishlist');
});

Route::prefix('wishlist')->group(function () {
    Route::get('/', [WishlistController::class, 'index']);
    Route::post('/add', [WishlistController::class, 'add']);
    Route::delete('/remove/{rowId}', [WishlistController::class, 'remove']);
    Route::post('/clear', [WishlistController::class, 'clear']);
    Route::post('/move-to-cart', [WishlistController::class, 'moveToCart']);
    Route::post('/save', [WishlistController::class, 'save']);
    Route::post('/restore', [WishlistController::class, 'restore']);
    Route::get('/check/{productId}', [WishlistController::class, 'check']);
});
