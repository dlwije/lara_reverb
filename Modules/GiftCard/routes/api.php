<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftCard\Http\Controllers\GiftCardController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('giftcards', GiftCardController::class)->names('giftcard');
});
