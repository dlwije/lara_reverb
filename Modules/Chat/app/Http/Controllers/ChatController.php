<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Reverb\Loggers\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Events\MessageTyping;
use Modules\Chat\Events\NewMessage;
use Modules\Chat\Models\ChatMessage;

class ChatController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {

        $messages= ChatMessage::create($request->toArray());

        $receiver = User::find($request->from);
        $sender = User::find($request->user_id);

        broadcast(new MessageSent($receiver, $sender, $messages));
        broadcast(new NewMessage($receiver, $sender, $messages));
        return self::success($messages, 'Message saved successfully');
    }

    public function storeTyping(Request $request)
    {

        broadcast(new MessageTyping($request->all()));
        return self::success([], 'Message typing saved successfully');
    }

    /**
     * Get the messages for the user along with messages count.
     */
    public function getUnreadMessages(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::with('from')->where('user_id', $request->user_id)
            ->get();
    }

    public function showConversation($userId)
    {
        $receiverUserId = auth('api')->id(); // Or pass from query if needed

        Log::info('User ID: ' . $userId);
        Log::info('Receiver User ID: ' . $receiverUserId);

        $messages = ChatMessage::where(function ($q) use ($userId, $receiverUserId) {
            $q->where('from', $userId)->where('user_id', $receiverUserId);
        })->orWhere(function ($q) use ($userId, $receiverUserId) {
            $q->where('from', $receiverUserId)->where('user_id', $userId);
        })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($receiverUserId) {
                $msg->isUser = $msg->from == $receiverUserId; // this adds the is_user flag
                return $msg;
            });


        return self::success($messages, 'Messages retrieved successfully');
    }
}
