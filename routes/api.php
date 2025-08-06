<?php

use App\Events\PrivateMessageEvent;
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
