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
        Log::info('NewMessageEvent'.$this->sender->id);
        return [
            new PrivateChannel('newmessage.'.$this->sender->id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->sender->id,
                'receiver_id' => $this->receiver->id,
                'created_at' => $this->message->created_at,
                'updated_at' => $this->message->updated_at,
                'message' => $this->message->message
            ],
        ];
    }

    public function BroadCastAs()
    {
        return 'NewMessage';
    }
}
