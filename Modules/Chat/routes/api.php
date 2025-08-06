<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;

//Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//    Route::apiResource('chats', ChatController::class)->names('chat');
//});

Route::middleware(['auth:api'])->group(function () {

    Route::post('/get-unread-messages', [ChatController::class, 'getUnreadMessages']);

//    Route::post('/get-team-members', [TeamController::class, 'index']);

    Route::post('/send-message', [ChatController::class, 'store']);
});
