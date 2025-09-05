<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('wallets', WalletController::class)->names('wallet');
    Route::get('wallet/statement', [WalletController::class, 'walletStatement'])->name('wallet.statement');
    Route::get('wallet/add-card', [WalletController::class, 'addCard'])->name('wallet.addCard');
});
