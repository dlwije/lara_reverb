<?php

use Illuminate\Support\Facades\Route;
use Modules\Checkout\Http\Controllers\CheckoutController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
//    Route::apiResource('checkouts', CheckoutController::class)->names('checkout');
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::post('/preview-deduction', [CheckoutController::class, 'previewWalletDeduction'])->name('previewDeduction');
        Route::post('/wallet-payment', [CheckoutController::class, 'processWalletPayment'])->name('processWalletPayment');
        Route::post('/wallet-payment-split', [CheckoutController::class, 'processSplitPayment'])->name('processSplitPayment');
    });
});
