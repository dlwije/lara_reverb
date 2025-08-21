<?php

use App\Events\PrivateMessageEvent;
use App\Http\Controllers\Sma\Search\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

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
    Route::post('/user/two-factor/enable', function (Request $request) {
        $user = $request->user();

        app(EnableTwoFactorAuthentication::class)($user);

        return response()->json([
            'svg' => $user->twoFactorQrCodeSvg(),
            'codes' => $user->recoveryCodes(),
        ]);
    });

    Route::post('/user/two-factor/disable', function (Request $request) {
        app(DisableTwoFactorAuthentication::class)($request->user());

        return response()->json(['status' => 'disabled']);
    });

    Route::get('/user/two-factor', function (Request $request) {
        return response()->json([
            'enabled' => ! is_null($request->user()->two_factor_secret),
            'svg'     => $request->user()->twoFactorQrCodeSvg(),
            'codes'   => $request->user()->recoveryCodes(),
        ]);
    });

    Route::post('/search/products', [SearchController::class, 'products'])->name('search.products');
});
