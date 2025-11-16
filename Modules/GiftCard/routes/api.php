<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftCard\Http\Controllers\AdminGiftCardController;
use Modules\GiftCard\Http\Controllers\GiftCardController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
//    Route::apiResource('giftcards', GiftCardController::class)->names('giftcard');

    Route::prefix('wallet/gift-cards')->group(function () {
        Route::post('/validate', [GiftCardController::class, 'validateCard'])->name('validateGiftCard');
        Route::post('/preview', [GiftCardController::class, 'previewRedemption'])->name('previewRedemptionGiftCard');
        Route::post('/redeem', [GiftCardController::class, 'redeem'])->name('redeemGiftCard');
        Route::post('/redeem-history', [GiftCardController::class, 'redemptionHistory'])->name('redeemHistoryGiftCard');
        Route::post('/resend-otp', [GiftCardController::class, 'resendOtp'])->name('resendOtpGiftCard');
    });

    // Admin routes
    Route::prefix('admin/gift-cards')->name('admin.')->group(function () {
        Route::get('/', [AdminGiftCardController::class, 'index'])->name('giftCards.index');
        Route::get('/create', [AdminGiftCardController::class, 'create'])->name('giftCards.create');
        Route::post('/store', [AdminGiftCardController::class, 'store'])->name('giftCards.store');
        Route::get('/show/{id}', [AdminGiftCardController::class, 'show'])->name('giftCards.show');
        Route::get('/edit/{id}', [AdminGiftCardController::class, 'edit'])->name('giftCards.edit');
        Route::post('/update/{id}', [AdminGiftCardController::class, 'update'])->name('giftCards.update');
        Route::post('/delete/{id}', [AdminGiftCardController::class, 'destroy'])->name('giftCards.destroy');
        Route::get('/activate/{id}', [AdminGiftCardController::class, 'activate'])->name('giftCards.activate');
        Route::get('/deactivate/{id}', [AdminGiftCardController::class, 'deactivate'])->name('giftCards.deactivate');
        Route::post('/bulk-redeem', [AdminGiftCardController::class, 'bulkRedeem'])->name('bulkRedeemGiftCard');
    });
});
