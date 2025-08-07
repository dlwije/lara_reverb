<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('online', function (User $user) {
//    \Illuminate\Support\Facades\Log::info('user online');
    return $user ? response()->json([
        'status' => true,
        'message' => 'sucess',
        'data' => $user,
    ], 200) : null;
});
//Broadcast::channel('online', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});

// Private Channel (Only authenticated users can listen)
Broadcast::channel('message.user.{userId1}-{userId2}', function (User $user, int $userId1, int  $userId2)
{
    Log::info('channel user to user conversation');

    return $user->id === $userId1 || $user->id === $userId2 ? $user : null;
});
