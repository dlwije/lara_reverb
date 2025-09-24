<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

Route::middleware(['auth:api'])->prefix('v1')->name('v1.')->group(function () {
//    Route::apiResource('wallets', WalletController::class)->names('wallet');

    Route::get('wallet/statement', [WalletController::class, 'walletStatement'])->name('wallet.statement');
    Route::get('wallet/add-card', [WalletController::class, 'addCard'])->name('wallet.addCard');

    Route::prefix('wallet')->name('api.wallet.')->group(function () {
        Route::get('/balance', [WalletApiController::class, 'getWallet'])->name('getWallet');
        Route::get('/balance-with-lots', [WalletApiController::class, 'getAvailableBalanceWithLots'])->name('balanceWithLots');
        Route::get('/summary', [WalletApiController::class, 'getWalletSummary'])->name('summary');
        Route::get('/lots', [WalletApiController::class, 'getLots'])->name('lots');
        Route::get('/expiring-lots', [WalletApiController::class, 'getExpiringLots'])->name('expiringLots');
        Route::post('/transactions', [WalletApiController::class, 'getTransactions'])->name('transactions');
        Route::get('/overview', [WalletApiController::class, 'getWalletOverview']); // Dashboard overview
        Route::get('/monthly-stats', [WalletApiController::class, 'getMonthlyStats']); // Monthly statistics
        Route::post('/process-payment', [WalletApiController::class, 'processWalletPayment'])->name('processPayment');
        Route::post('/gift-card/redeem', [WalletApiController::class, 'redeemGiftCard'])->name('redeemGiftCard');

        Route::get('/notification-preferences', [WalletNotificationApiController::class, 'getPreferences']);
        Route::put('/notification-preferences', [WalletNotificationApiController::class, 'updatePreferences']);
        Route::put('/notification-preferences/{type}', [WalletNotificationApiController::class, 'updatePreference']);

        Route::get('/notification-list', [NotificationController::class, 'index']);
        Route::get('/notification-stats', [NotificationController::class, 'stats']);
        Route::get('/notification-recent', [NotificationController::class, 'recent']);
        Route::get('/notification-unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/notification/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/notification/{id}/unread', [NotificationController::class, 'markAsUnread']);
        Route::put('/notification-mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notification/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/notification', [NotificationController::class, 'clearAll']);
    });
});
