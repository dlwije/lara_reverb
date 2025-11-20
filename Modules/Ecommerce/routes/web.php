<?php

use Illuminate\Support\Facades\Route;
//use Modules\Ecommerce\Http\Controllers\Customer\CartController;
use Modules\Ecommerce\Http\Controllers\Customer\OrderController;
use Modules\Ecommerce\Http\Controllers\Customer\ProfileController;
use Modules\Ecommerce\Http\Controllers\EcommerceController;
use Modules\Ecommerce\Http\Controllers\Public\ProductCategoryController;
use Modules\Ecommerce\Http\Controllers\Public\ProductController;
use Modules\Ecommerce\Http\Controllers\Public\ReviewController;
use Modules\Ecommerce\Http\Controllers\Public\ShopController;
use Modules\Ecommerce\Http\Controllers\Public\VendorController;
use Modules\Wishlist\Http\Controllers\WishlistController;

//Route::middleware(['auth', 'verified'])->group(function () {
//    Route::resource('ecommerces', EcommerceController::class)->names('ecommerce');
//});

Route::get('/', [EcommerceController::class, 'home'])->name('home');
Route::get('about-us', [EcommerceController::class, 'aboutUs'])->name('about-us');
Route::get('contact-us', [EcommerceController::class, 'contactUs'])->name('contact-us');
// Product Browsing
Route::get('product-list', [ProductController::class, 'index'])->name('front.products'); // List all products
Route::get('product/new-arrivals', [ProductController::class, 'index'])->name('product.new.arrivals'); // List all products
Route::get('product/{slug}', [ProductController::class, 'show']); // Show product details

Route::get('/deals', [ProductController::class, 'getPromotionProducts']);
Route::get('/promotions', [ProductController::class, 'getPromotionProducts']);

// Categories
Route::get('product-categories', [ProductCategoryController::class, 'index']); // List all categories
Route::get('product-categories/{slug}', [ProductCategoryController::class, 'show'])->name('product.categories.show'); // Show category products

// Vendor Information
Route::get('vendors/{vendor}', [VendorController::class, 'show']); // Show vendor details

// Reviews
Route::get('products/{product}/reviews', [ReviewController::class, 'index']); // List product reviews

// Shops
Route::get('shops', [ShopController::class, 'index'])->name('shops');


//Route::get('cart', [CartController::class, 'index'])->name('cart'); // View cart
Route::group(['middleware' => ['auth']], function () {
    // Cart Management
//    Route::post('cart/{product}', [CartController::class, 'store']); // Add to cart
//    Route::delete('cart/{cart}', [CartController::class, 'destroy']); // Remove from cart
//
//    // Wishlist Management
//    Route::get('wishlist', [WishlistController::class, 'index']); // View wishlist
//    Route::post('wishlist/{wishlist}', [WishlistController::class, 'store']); // Add to wishlist
//    Route::delete('wishlist/{wishlist}', [WishlistController::class, 'destroy']); // Remove from wishlist

    // Orders
    Route::get('orders', [OrderController::class, 'index'])->name('orders'); // List user orders
    Route::get('orders/{order}', [OrderController::class, 'show']); // View order details
    Route::post('orders', [OrderController::class, 'store']); // Place an order

    // User Profile
    Route::get('profile', [ProfileController::class, 'show']); // View user profile
    Route::put('profile', [ProfileController::class, 'update']); // Update user profile

    Route::post('products/{product}/reviews', [Modules\Ecommerce\Http\Controllers\Customer\ReviewController::class, 'store']); // Submit a product review
});
