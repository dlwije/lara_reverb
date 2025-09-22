<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

Route::middleware(['auth:api'])->prefix('v1')->name('v1.')->group(function () {
//    Route::apiResource('wallets', WalletController::class)->names('wallet');

    Route::get('wallet/statement', [WalletController::class, 'walletStatement'])->name('wallet.statement');
    Route::get('wallet/add-card', [WalletController::class, 'addCard'])->name('wallet.addCard');

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/balance', [WalletController::class, 'getWallet'])->name('getWallet');
        Route::get('/balance-with-lots', [WalletController::class, 'getAvailableBalanceWithLots'])->name('balanceWithLots');
        Route::get('/summary', [WalletController::class, 'getWalletSummary'])->name('summary');
        Route::get('/lots', [WalletController::class, 'getLots'])->name('lots');
        Route::get('/transactions', [WalletController::class, 'getTransactions'])->name('transactions');
    });
});
