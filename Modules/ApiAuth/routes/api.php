<?php

use Illuminate\Support\Facades\Route;
use Modules\ApiAuth\Http\Controllers\ApiAuthController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('user', ApiAuthController::class)->names('apiauth');
});

Route::middleware(['auth:api'])->prefix('v1')->name('v1.')->group(function () {
    Route::post('login', [ApiAuthController::class, 'login'])->name('login');
    Route::post('register', [ApiAuthController::class, 'register'])->name('register');
    Route::get('users/list', [ApiAuthController::class, 'index'])->name('users.list');
    Route::get('users/list/data', [ApiAuthController::class, 'userList'])->name('users.list.data');
});
