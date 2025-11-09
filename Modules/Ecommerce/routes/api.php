<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\EcommerceController;
use Modules\Ecommerce\Http\Controllers\Public\ProductCategoryController;
use Modules\Ecommerce\Http\Controllers\Public\ProductController;
use Modules\Ecommerce\Http\Controllers\Public\ReviewController;
use Modules\Ecommerce\Http\Controllers\Public\ShopController;
use Modules\Ecommerce\Http\Controllers\Public\VendorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('ecommerces', EcommerceController::class)->names('ecommerce');
});

//// Product Browsing
//Route::get('products', [ProductController::class, 'index']); // List all products
//Route::get('product/{product}', [ProductController::class, 'show'])->name(''); // Show product details
//
//// Categories
//Route::get('product-categories', [ProductCategoryController::class, 'index']); // List all categories
//Route::get('product-categories/{category}', [ProductCategoryController::class, 'show']); // Show category products
//
//// Vendor Information
//Route::get('vendors/{vendor}', [VendorController::class, 'show']); // Show vendor details
//
//// Reviews
//Route::get('products/{product}/reviews', [ReviewController::class, 'index']); // List product reviews
//
//// Shops
//Route::get('shops', [ShopController::class, 'index'])->name('shop');
