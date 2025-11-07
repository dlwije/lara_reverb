<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\Api\CartApiController;
use Modules\Cart\Http\Middleware\IdentifyCart;

Route::prefix('v1/cart')->group(function () {
//    Route::apiResource('carts', CartController::class)->names('cart');

    // Public cart routes that require cart identification
    Route::middleware([IdentifyCart::class])->group(function () {
        Route::get('/', [CartApiController::class, 'index']);
        Route::post('/add', [CartApiController::class, 'add']);
        Route::put('/update/{rowId}', [CartApiController::class, 'update']);
        Route::delete('/remove/{rowId}', [CartApiController::class, 'remove']);
        Route::delete('/clear', [CartApiController::class, 'clear']);
        Route::post('/shipping', [CartApiController::class, 'setShipping']);
        Route::post('/discount', [CartApiController::class, 'setDiscount']);
    });

// Cart routes that require authentication + cart identification
    Route::middleware(['auth:api', IdentifyCart::class])->group(function () {
        Route::post('/merge-user', [CartApiController::class, 'mergeWithUser']);
        Route::post('/save', [CartApiController::class, 'saveCart']);
    });
});
