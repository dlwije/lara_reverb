<?php

use Illuminate\Support\Facades\Route;
use Modules\ApiAuth\Http\Controllers\ApiAuthController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('user', ApiAuthController::class)->names('apiauth');
});

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('login', [ApiAuthController::class, 'login'])->name('login');
    Route::post('register', [ApiAuthController::class, 'register'])->name('register');
});
