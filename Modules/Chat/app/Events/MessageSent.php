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
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\ChatMessage;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ?User $receiver, // nullable for groups
        public User $sender,
        public ChatMessage $message
    ) {}

    public function broadcastWith()
    {
        return [
            'message' => [
                'id'              => $this->message->id,
                'conversation_id'  => $this->message->conversation_id,
                'sender_id'       => $this->sender->id,
                'receiver_id'     => $this->receiver?->id,
                'message'         => $this->message->message,
                'attachment_url'  => $this->message->attachment_url,
                'attachment_type' => $this->message->attachment_type,
                'read_at'         => $this->message->read_at,
                'created_at'      => $this->message->created_at,
                'updated_at'      => $this->message->updated_at->toDateTimeString(),
                'sender' => [
                    'id'     => $this->sender->id,
                    'name'   => $this->sender->name,
                    'email'  => $this->sender->email,
                    'avatar' => $this->sender->avatar,
                ],
                'receiver' => $this->receiver ? [
                    'id'     => $this->receiver->id,
                    'name'   => $this->receiver->name,
                    'email'  => $this->receiver->email,
                    'avatar' => $this->receiver->avatar,
                ] : null,
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        if ($conversation && $conversation->type === 'group') {
            return [new PrivateChannel('chat.conversation.' . $conversation->id)];
        }

        // Private chat: channel by user ids
        $channelName = collect([$this->receiver?->id, $this->sender->id])
//            ->filter()
            ->sort()
            ->implode('-');

        return [new PrivateChannel('chat.' . $channelName)];
    }
    public function BroadCastAs()
    {
        return 'MessageSent';
    }
}
