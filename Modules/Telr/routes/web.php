<?php

use Illuminate\Support\Facades\Route;
use Modules\Telr\Http\Controllers\TelrController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('telrs', TelrController::class)->names('telr');

});
    Route::get('payment', [TelrController::class, 'process'])->name('payment');
Route::prefix('telr')->name('telr.')->group(function () {
    Route::post('process-payment', [TelrController::class, 'process'])->name('process');
    Route::get('auth', [TelrController::class, 'auth'])->name('auth');
    Route::get('cancel', [TelrController::class, 'cancel'])->name('cancel');
    Route::get('decline', [TelrController::class, 'decline'])->name('decline');
});
