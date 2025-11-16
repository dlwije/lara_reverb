<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public $request) {}

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        Log::info('typing Event: '.$this->request['channel']);
        $channels[] = new PrivateChannel($this->request['channel']);
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->request['user_id'],
            'typing' => $this->request['typing']
        ];
    }

    public function BroadCastAs()
    {
        return 'UserTyping';
    }
}
