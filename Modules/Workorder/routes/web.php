<?php

use Illuminate\Support\Facades\Route;
use Modules\Workorder\Http\Controllers\WorkorderController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('workorders', WorkorderController::class)->names('workorder');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('workorder/list', [WorkorderController::class, 'index'])->name('workorder.list');
});
