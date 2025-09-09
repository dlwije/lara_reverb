<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('.well-known/appspecific/com.chrome.devtools.json', function () {
    return response('', 204);
});

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale', 'en');
    session(['locale' => $locale]);
    app()->setLocale($locale);
    return back();
})->name('locale.change');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
