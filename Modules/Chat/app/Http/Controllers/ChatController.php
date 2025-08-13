<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Events\MessageTyping;
use Modules\Chat\Events\NewMessage;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Models\ChatMessage;

class ChatController extends Controller
{

    //Conversation List with Last Message + Unread Count
    public function getConversations(Request $request)
    {
        $authUserId = auth('api')->id();

        $conversations = DB::table('chat_messages as cm')
            ->select(
                'cm.conversation_id',
                DB::raw('MAX(cm.created_at) as last_message_at'),
                DB::raw('(SELECT message
                       FROM chat_messages
                       WHERE conversation_id = cm.conversation_id
                       ORDER BY created_at DESC
                       LIMIT 1) as last_message'),
                DB::raw('(SELECT COUNT(*)
                       FROM chat_messages
                       WHERE conversation_id = cm.conversation_id
                         AND receiver_id = ' . $authUserId . '
                         AND read_at IS NULL) as unread_count')
            )
            ->where(function ($q) use ($authUserId) {
                $q->where('cm.sender_id', $authUserId)
                    ->orWhere('cm.receiver_id', $authUserId);
            })
            ->groupBy('cm.conversation_id')
            ->orderByDesc('last_message_at')
            ->get();

        // Attach other participant's profile
        $conversations->transform(function ($conv) use ($authUserId) {
            $otherUserId = DB::table('chat_messages')
                ->where('conversation_id', $conv->conversation_id)
                ->where(function ($q) use ($authUserId) {
                    $q->where('sender_id', '!=', $authUserId)
                        ->orWhere('receiver_id', '!=', $authUserId);
                })
                ->value('sender_id'); // or receiver_id depending on position

            $conv->user = User::select('id', 'name', 'email', 'avatar')
                ->find($otherUserId);

            return $conv;
        });

        return self::success($conversations, 'Conversations retrieved successfully');
    }

    //Show Conversation + Mark as Read
    public function showConversation($conversationId)
    {
        $authUserId = auth('api')->id();

        \Illuminate\Support\Facades\Log::info('Conversation ID on Show Conversation: ' . $conversationId);
        // Mark unread messages as read
        ChatMessage::where('conversation_id', $conversationId)
            ->where('receiver_id', $authUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = ChatMessage::with(['sender:id,name,email,avatar', 'receiver:id,name,email,avatar'])
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($authUserId) {
                $msg->isUser = $msg->sender_id == $authUserId;
                return $msg;
            });

        return self::success($messages, 'Messages retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $senderId = $request->from;
        $receiverId = $request->user_id;  // For private chat; for group, this may be null or group handled differently
        $conversationId = $request->conversation_id ?? null; // Optional if sending in existing conversation

        DB::beginTransaction();
        try {
            if ($conversationId) {
                // Use existing conversation (could be private or group)
                $conversation = ChatConversation::find($conversationId);

                if (!$conversation) {
                    self::error('Conversation not found', 404);
                }

            } else {
                // Private chat - find or create conversation between sender and receiver
                $conversation = ChatConversation::where('type', 'private')
                    ->whereHas('participants', function ($q) use ($senderId) {
                        $q->where('user_id', $senderId);
                    })
                    ->whereHas('participants', function ($q) use ($receiverId) {
                        $q->where('user_id', $receiverId);
                    })
                    ->first();

                if (!$conversation) {
                    $conversation = ChatConversation::create([
                        'type' => 'private',
                        'created_by' => $senderId,
                    ]);
                    $conversation->participants()->attach([$senderId, $receiverId], ['joined_at' => now()]);
                }
            }

            // Create the message
            $message = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'receiver_id' => $conversation->type === 'private' ? $receiverId : null,
                'message' => $request->message,
                'attachment_url' => $request->attachment_url ?? null,
                'attachment_type' => $request->attachment_type ?? null,
            ]);

            // Update conversation last message info
            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ]);

            // Load sender and receiver info
            $sender = User::select('id', 'name', 'email', 'avatar')->find($senderId);

            // For private chats, load receiver user
            $receiver = $conversation->type === 'private'
                ? User::select('id', 'name', 'email', 'avatar')->find($receiverId)
                : null;

            DB::commit();
            // Broadcast events
            broadcast(new MessageSent($receiver, $sender, $message));
            broadcast(new NewMessage($receiver, $sender, $message));

            return self::success($message, 'Message saved successfully');
        } catch (\Exception $ex) {
            Log::error($ex);
            DB::rollBack();
            return self::error($ex->getMessage(), 500);
        }
    }

    public function getOrCreate(Request $request)
    {
        $request->validate([
            'user1_id' => 'required|numeric|exists:users,id',
            'user2_id' => 'required|numeric|exists:users,id',
        ]);

        $user1Id = $request->input('user1_id');
        $user2Id = $request->input('user2_id');

        // Ensure IDs are not the same
        if ($user1Id === $user2Id) {
            return self::error('Cannot create conversation with the same user.', 422);
        }

        // Check if conversation already exists
        $conversation = ChatConversation::where('type', 'private')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user1Id))
            ->whereHas('participants', fn($q) => $q->where('user_id', $user2Id))
            ->first();

        if ($conversation) {
            return self::success([
                'id' => $conversation->id,
                'exists' => true,
            ], 'Conversation already exists');
        }

        // Create new conversation
        DB::beginTransaction();
        try {
            $conversation = ChatConversation::create([
                'type' => 'private',
                'created_by' => $user1Id,
            ]);

            $conversation->participants()->attach([$user1Id, $user2Id], [
                'joined_at' => now(),
            ]);

            DB::commit();

            return self::success([
                'id' => $conversation->id,
                'exists' => false,
            ], 'Conversation created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create conversation: ' . $e);
            return self::error('Failed to create conversation', 500);
        }
    }

    public function markAsRead($conversationId)
    {
        $userId = auth('api')->id();

        // 1. Ensure conversation exists & user is a participant
        $conversation = ChatConversation::where('id', $conversationId)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->first();

        Log::error('Conversation on mark as read: ', ['convo' => $conversation?->toArray(), 'user' => $userId, 'id' => $conversationId]);

        if (!$conversation) {
            return self::error('Conversation not found or you are not a participant', 404);
        }

        // 2. Update pivot table last_read_at
        $conversation->participants()
            ->updateExistingPivot($userId, [
                'last_read_at' => now(),
            ]);

        // (Optional) Update any cached unread counts if you have them
        // e.g., Redis::del("user:{$userId}:conversation:{$conversationId}:unread_count");

        return self::success([], 'Conversation marked as read');
    }

    public function getMessages($conversationId)
    {
        $userId = auth('api')->id();

        // 1. Ensure the conversation exists & user is a participant
        $conversation = ChatConversation::where('id', $conversationId)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->first();

        if (!$conversation) {
            return self::error('Conversation not found or access denied', 404);
        }

        // 2. Fetch messages with sender info
        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->with(['sender:id,name,email,avatar', 'receiver:id,name,email,avatar'])
            ->orderBy('created_at', 'asc') // oldest first
            ->get();

        // 3. Return in your API format
        return self::success($messages, 'Messages fetched successfully');
    }
    public function getAvailableUsers(Request $request)
    {
        // Get all users except the authenticated one
        $authUserId = auth('api')->id();
        $query = $request->get('q', '');

        $users = User::query()
            ->select('id', 'name', 'email', 'avatar')
            ->where('id', '!=', $authUserId)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('name', 'asc')
            ->limit(20)
            ->get();

       return self::success($users, 'Available users retrieved successfully');
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
        return ChatMessage::with(['receiver', 'sender'])->where('user_id', $request->user_id)
            ->get();
    }

//    public function showConversation($userId)
//    {
//        $receiverUserId = auth('api')->id(); // Or pass from query if needed
//
//        Log::info('User ID: ' . $userId);
//        Log::info('Receiver User ID: ' . $receiverUserId);
//
//        $messages = ChatMessage::where(function ($q) use ($userId, $receiverUserId) {
//            $q->where('from', $userId)->where('user_id', $receiverUserId);
//        })->orWhere(function ($q) use ($userId, $receiverUserId) {
//            $q->where('from', $receiverUserId)->where('user_id', $userId);
//        })
//            ->orderBy('created_at', 'asc')
//            ->get()
//            ->map(function ($msg) use ($receiverUserId) {
//                $msg->isUser = $msg->from == $receiverUserId; // this adds the is_user flag
//                return $msg;
//            });
//
//
//        return self::success($messages, 'Messages retrieved successfully');
//    }
}
