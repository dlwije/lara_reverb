<?php

use Illuminate\Support\Facades\Route;
use Modules\ApiAuth\Http\Controllers\ApiAuthController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('apiauths', ApiAuthController::class)->names('apiauth');
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', [ApiAuthController::class, 'login'])->name('login');
    Route::post('register', [ApiAuthController::class, 'register'])->name('register');
    Route::get('users/list', [ApiAuthController::class, 'index'])->name('users.list');
});
