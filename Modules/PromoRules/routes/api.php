<?php

use Illuminate\Support\Facades\Route;
use Modules\PromoRules\Http\Controllers\PromoRulesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('promorules', PromoRulesController::class)->names('promorules');
});
