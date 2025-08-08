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
    public function __construct(public User $receiver, public User $sender, public $message) {}

    public function broadcastWith()
    {
        return [
            'message' => [
                'sender_id' => $this->sender->id,
                'receiver_id' => $this->receiver->id,
                'created_at' => $this->message->created_at,
                'message' => $this->message->message
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
        $m = $this->message;

        $channels = [];
        $channelName = collect([$this->receiver->id, $this->sender->id])->sort()->implode('-');
        $channels[] = new PrivateChannel('chat.' . $channelName);

//        if ($m->group_id) {
//            $channels[] = new PrivateChannel('message.group.' . $m->group_id);
//        } elseif ($m->sender_id && $m->reciever_id) {
//
//
//
//        } else {
//            Log::warning('Message missing sender or receiver ID:', ['sender_id' => $m->sender_id, 'receiver_id' => $m->reciever_id]);
//        }
        /*Log::info('SocketMessage payload:', [
            'sender_id' => $m->sender_id,
            'reciever_id' => $m->reciever_id,
            'group_id' => $m->group_id,
            'message_id' => $m->id,
        ]);*/
        return $channels;
    }
    public function BroadCastAs()
    {
        return 'MessageSent';
    }
}
