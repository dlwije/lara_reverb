<?php

use Illuminate\Support\Facades\Route;
use Modules\Checkout\Http\Controllers\CheckoutController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('checkouts', CheckoutController::class)->names('checkout');
});
