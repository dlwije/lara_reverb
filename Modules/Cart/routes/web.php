<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;

Route::prefix('cart')->group(function () {

    Route::post('/store-to-database', [CartController::class, 'storeCartToDatabase']);
    Route::get('/data', [CartController::class, 'getCart']);
    Route::get('/', [CartController::class, 'index'])->name('cart');
    Route::post('/add', [CartController::class, 'addToCart'])->name('cart.add');
    Route::put('/update/{rowId}', [CartController::class, 'updateCart']);
    Route::delete('/remove/{rowId}', [CartController::class, 'removeFromCart']);
    Route::delete('/clear', [CartController::class, 'clearCart']);
    Route::post('/save', [CartController::class, 'saveCart']);
    Route::post('/restore', [CartController::class, 'restoreCart']);
    Route::get('/count', [CartController::class, 'getCartCount']);
});

Route::middleware(['auth', 'verified'])->group(function () {
//    Route::prefix('cart')->group(function () {


//    });
});
