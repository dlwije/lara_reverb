<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::put('/update/{rowId}', [CartController::class, 'updateCart']);
        Route::delete('/remove/{rowId}', [CartController::class, 'removeFromCart']);
        Route::post('/clear', [CartController::class, 'clearCart']);
        Route::post('/save', [CartController::class, 'saveCart']);
        Route::post('/restore', [CartController::class, 'restoreCart']);
        Route::get('/count', [CartController::class, 'getCartCount']);
    });
});
