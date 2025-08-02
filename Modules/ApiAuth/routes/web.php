<?php

use Illuminate\Support\Facades\Route;
use Modules\ApiAuth\Http\Controllers\ApiAuthController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('apiauths', ApiAuthController::class)->names('apiauth');
});
