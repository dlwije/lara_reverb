<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\CategoryController;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware(['auth', 'verified'])->group(function () {
//    Route::resource('products', ProductController::class)->names('product');
});

Route::middleware(['language', 'auth', config('jetstream.auth_session')])->group(function () {
    Route::extendedResources([
        'products' => ProductController::class
    ], [
        'prefix'     => 'admin',
        'as'         => 'admin',
    ]);

    Route::prefix('/search')->name('search.')->group(function () {
        Route::get('/category', [CategoryController::class, 'dropDownList'])->name('category');
        Route::get('/sub-category', [CategoryController::class, 'subDropDownList'])->name('subCategory');
    });
});
