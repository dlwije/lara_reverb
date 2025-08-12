<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;

//Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//    Route::apiResource('chats', ChatController::class)->names('chat');
//});

Route::middleware(['auth:api'])->prefix('v1')->name('v1.')->group(function () {

    Route::get('/get-unread-messages', [ChatController::class, 'getConversations'])->name('conversation.unread');

//    Route::post('/get-team-members', [TeamController::class, 'index']);

    Route::post('/send-message', [ChatController::class, 'store'])->name('conversation.store');
    Route::get('/get-conversation/{user_id}', [ChatController::class, 'showConversation'])->name('conversation.show');
    Route::post('/typing-chat', [ChatController::class, 'storeTyping'])->name('typing.store');
    Route::post('/conversations/get-or-create', [ChatController::class, 'getOrCreate'])->name('conversation.getorcreate');
    Route::post('/conversations/{conversationId}/mark-read', [ChatController::class, 'markAsRead'])->name('conversation.markasread');
    Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages'])->name('conversation.messages');
});
