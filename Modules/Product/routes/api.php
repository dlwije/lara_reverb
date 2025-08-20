<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class)->names('product');
});

Route::middleware('auth:api')->prefix('v1')->name('v1.')->group(function () {
    Route::get('product/list/data', [ProductController::class, 'index'])->name('products.list.data');
});
