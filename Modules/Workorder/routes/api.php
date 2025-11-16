<?php

use Illuminate\Support\Facades\Route;
use Modules\Workorder\Http\Controllers\WorkorderController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('workorders', WorkorderController::class)->names('workorder');
});

Route::middleware('auth:api')->prefix('v1')->name('v1.')->group(function () {
    Route::get('workorder/list/data', [WorkorderController::class, 'getDataTableList'])->name('workorder.list.data');
});
