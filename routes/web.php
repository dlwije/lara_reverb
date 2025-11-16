<?php

use App\Http\Controllers\Installer\InstallController;
use App\Http\Controllers\Installer\ModuleController;
use App\Http\Middleware\CanInstall;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('.well-known/appspecific/com.chrome.devtools.json', function () {
    return response('', 204);
});

Route::prefix('install')
    ->middleware('web')
    ->middleware('\\' . CanInstall::class)
    ->group(function () {
        // cache()->forget('sma_modules');
        Route::get('/', ['\\' . InstallController::class, 'index']);
        Route::post('demo', ['\\' . InstallController::class, 'demo']);
        Route::post('save', ['\\' . InstallController::class, 'save']);
        Route::post('check', ['\\' . InstallController::class, 'show']);
        Route::post('finalize', ['\\' . InstallController::class, 'finalize']);
    });

Route::prefix('manage-modules')->middleware('web')->group(function () {
    Route::get('/', ['\\' . ModuleController::class, 'index'])
        ->name('modules')->can('manage-modules');
    Route::post('/', ['\\' . ModuleController::class, 'enable'])
        ->name('modules.enable')->can('manage-modules');
    Route::post('disable', ['\\' . ModuleController::class, 'disable'])
        ->name('modules.disable')->can('manage-modules');
});

//Route::get('/', function () {
//    return Inertia::render('welcome');
//})->name('home');

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale', 'en');
//    \Illuminate\Support\Facades\Log::info($locale);
    session(['locale' => $locale]);
    app()->setLocale($locale);
    return back();
})->name('locale.change');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('e-commerce/customer/page',[]);
//        return Inertia::render('dashboard', []);
    })->name('dashboard');

    Route::get('customer/dashboard', function () {
        return Inertia::render('e-commerce/customer/page',[]);
    })->name('customer.dashboard');

    Route::get('super-admin/dashboard', function () {
        return Inertia::render('dashboard',[]);
    })->name('super-admin.dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
