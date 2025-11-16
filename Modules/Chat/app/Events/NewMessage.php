<?php

namespace Modules\Chat\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Reverb\Loggers\Log;

class NewMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $receiver, public User $sender, public $message) {}

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        if ($this->message->conversation->type === 'group') {
            // Broadcast to all group participants
            return [new PrivateChannel('chat.conversation.' . $this->message->conversation_id)];
        }

        // Private chat: notify only receiver
        return [new PrivateChannel('notification.' . $this->receiver?->id)];
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'message_id'      => $this->message->id,
            'receiver' => [
                'id'     => $this->receiver->id,
                'name'   => $this->receiver->name,
                'email'  => $this->receiver->email,
                'avatar' => $this->receiver->avatar,
            ],
            'sender' => [
                'id'     => $this->sender->id,
                'name'   => $this->sender->name,
                'email'  => $this->sender->email,
                'avatar' => $this->sender->avatar,
            ],
            'message' => [
                'message'         => $this->message->message,
                'attachment_url'  => $this->message->attachment_url,
                'attachment_type' => $this->message->attachment_type,
                'created_at'      => $this->message->created_at->toDateTimeString(),
            ],
            'unread_count' => $this->receiver
                ? $this->receiver->receivedMessages()
                    ->where('conversation_id', $this->message->conversation_id)
                    ->whereNull('read_at')
                    ->count()
                : 0,
        ];
    }

    public function BroadCastAs()
    {
        return 'NewMessageNotification';
    }
}
