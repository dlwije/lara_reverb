<?php

use Illuminate\Support\Facades\Route;
use Modules\Checkout\Http\Controllers\CheckoutController;

Route::middleware(['auth', 'verified'])->group(function () {
//    Route::resource('checkouts', CheckoutController::class)->names('checkout');

    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/preview-deduction', [CheckoutController::class, 'previewWalletDeduction'])->name('previewDeduction');
        Route::post('/wallet-payment', [CheckoutController::class, 'processWalletPayment'])->name('processWalletPayment');
        Route::post('/wallet-payment-split', [CheckoutController::class, 'processSplitPayment'])->name('processSplitPayment');
    });
});
