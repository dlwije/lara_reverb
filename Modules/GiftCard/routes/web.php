<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftCard\Http\Controllers\GiftCardController;

Route::middleware(['auth', 'verified'])->group(function () {
//    Route::resource('giftcards', GiftCardController::class)->names('giftcard');

    Route::prefix('wallet/gift-cards')->group(function () {
        Route::post('/validate', [GiftCardController::class, 'validateCard'])->name('validateGiftCard');
        Route::post('/preview', [GiftCardController::class, 'previewRedemption'])->name('previewRedemptionGiftCard');
        Route::post('/redeem', [GiftCardController::class, 'redeem'])->name('redeemGiftCard');
        Route::post('/redeem-history', [GiftCardController::class, 'redemptionHistory'])->name('redeemHistoryGiftCard');

        // Admin routes
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [GiftCardController::class, 'index'])->name('giftCards.index');
            Route::get('/create', [GiftCardController::class, 'create'])->name('giftCards.create');
            Route::post('/store', [GiftCardController::class, 'store'])->name('giftCards.store');
            Route::get('/show/{id}', [GiftCardController::class, 'show'])->name('giftCards.show');
            Route::get('/edit/{id}', [GiftCardController::class, 'edit'])->name('giftCards.edit');
            Route::post('/update/{id}', [GiftCardController::class, 'update'])->name('giftCards.update');
            Route::post('/delete/{id}', [GiftCardController::class, 'destroy'])->name('giftCards.destroy');
            Route::get('/activate/{id}', [GiftCardController::class, 'activate'])->name('giftCards.activate');
            Route::get('/deactivate/{id}', [GiftCardController::class, 'deactivate'])->name('giftCards.deactivate');
            Route::post('/bulk-redeem', [GiftCardController::class, 'bulkRedeem'])->name('bulkRedeemGiftCard');
        });
    });
});
