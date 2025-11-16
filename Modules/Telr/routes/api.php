<?php

use Illuminate\Support\Facades\Route;
use Modules\Telr\Http\Controllers\TelrController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('telrs', TelrController::class)->names('telr');
});
