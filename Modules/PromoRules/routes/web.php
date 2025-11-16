<?php

use Illuminate\Support\Facades\Route;
use Modules\PromoRules\Http\Controllers\PromoRulesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('promorules', PromoRulesController::class)->names('promorules');
});
