<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Models\ChatMessage;

class ChatController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {

        ChatMessage::create($request->toArray());

        $receiver = User::find($request->user_id);
        $sender = User::find($request->from);

        broadcast(new MessageSent($receiver, $sender, $request->message));
        return response()->noContent();
    }

    /**
     * Get the messages for the user along with messages count.
     */
    public function getUnreadMessages(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::with('from')->where('user_id', $request->user_id)
            ->get();
    }
}
