<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftCard\Http\Controllers\GiftCardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('giftcards', GiftCardController::class)->names('giftcard');
});
