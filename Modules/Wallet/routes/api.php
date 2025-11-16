<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\NotificationController;
use Modules\Wallet\Http\Controllers\Api\WalletController;
use Modules\Wallet\Http\Controllers\WalletNotificationApiController;

Route::middleware(['auth:api'])->prefix('v1')->name('v1.')->group(function () {
//    Route::apiResource('wallets', WalletController::class)->names('wallet');

    Route::get('wallet/statement', [Modules\Wallet\Http\Controllers\WalletController::class, 'walletStatement'])->name('wallet.statement');
    Route::get('wallet/add-card', [Modules\Wallet\Http\Controllers\WalletController::class, 'addCard'])->name('wallet.addCard');

    Route::prefix('wallet')->name('api.wallet.')->group(function () {
        Route::post('/export-transactions', [WalletController::class, 'exportTransactions'])->name('export.transactions');

        Route::get('/balance', [WalletController::class, 'getWallet'])->name('getWallet');
        Route::get('/balance-with-lots', [WalletController::class, 'getAvailableBalanceWithLots'])->name('balanceWithLots');
        Route::get('/summary', [WalletController::class, 'getWalletSummary'])->name('summary');
        Route::get('/lots', [WalletController::class, 'getLots'])->name('lots');
        Route::get('/expiring-lots', [WalletController::class, 'getExpiringLots'])->name('expiringLots');
        Route::post('/transactions', [WalletController::class, 'getTransactions'])->name('transactions');
        Route::get('/overview', [WalletController::class, 'getWalletOverview']); // Dashboard overview
        Route::get('/monthly-stats', [WalletController::class, 'getMonthlyStats']); // Monthly statistics
        Route::post('/release-frozen-amount', [WalletController::class, 'releaseFrozenWalletByOrder'])->name('releaseFrozenAmount');
        Route::post('/process-payment', [WalletController::class, 'processWalletPayment'])->name('processPayment');
        Route::post('/gift-card/redeem', [WalletController::class, 'redeemGiftCard'])->name('redeemGiftCard');

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
