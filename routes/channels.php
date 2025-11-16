<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\ChatConversation;

Broadcast::channel('online', function (User $user) {
    return $user ? response()->json([
        'status' => true,
        'message' => 'sucess',
        'data' => $user,
    ], 200) : null;
});

// Private Channel (Only authenticated users can listen)
//Broadcast::channel('chat.{userId1}-{userId2}', function (User $user, int $userId1, int  $userId2)
//{
//    return $user->id === $userId1 || $user->id === $userId2 ? $user : null;
//});

// Private chat between two users
Broadcast::channel('chat.{userIds}', function ($user, $userIds) {
    // userIds comes as something like '4-7'
    $ids = explode('-', $userIds);

    // Check if current user is part of the private chat pair
    return in_array($user->id, $ids);
});

// Group chat channel authorization
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is participant of the conversation
    return ChatConversation::where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->exists();
});

Broadcast::channel('notification.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
