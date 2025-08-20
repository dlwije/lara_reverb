<?php

use App\Events\PrivateMessageEvent;
use App\Http\Controllers\Sma\Search\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Broadcast::routes(['middleware' => ['auth:api']]);

// Send a private message
Route::get('/broadcast-private/{userId}', function ($userId) {
    event(new PrivateMessageEvent("Hello User {$userId}, this is a private message! 🚀", $userId));
    return response()->json(['status' => 'Private event sent']);
});

Route::middleware(['auth:api'])->prefix('v1')->name('v1.')->group(function () {
    Route::post('/search/products', [SearchController::class, 'products'])->name('search.products');
});
