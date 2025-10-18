<?php

use Illuminate\Support\Facades\Route;
use Modules\ApiAuth\Http\Controllers\ApiAuthController;

Route::post('login', [ApiAuthController::class, 'login'])->name('login');
Route::post('register', [ApiAuthController::class, 'register'])->name('register');

Route::middleware('auth')->prefix('auth')->name('auth.')->group(function () {
    Route::get('users/list', [ApiAuthController::class, 'index'])->name('users.list');
    Route::get('users/create', [ApiAuthController::class, 'create'])->name('users.create');
});
