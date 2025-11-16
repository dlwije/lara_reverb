<?php

namespace Modules\Wallet\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Ensure data is array (DatabaseNotification usually casts it to array)
        $data = $this->data;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = $decoded ?: [];
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $data['title'] ?? null,            // read from payload
            'message' => $data['message'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'data' => $data,

            // read_at exists on the notification itself
            'is_read' => !is_null($this->read_at),
            'is_unread' => is_null($this->read_at),
            'read_at' => $this->read_at ? $this->read_at->toISOString() : null,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'created_at_formatted' => $this->created_at ? $this->created_at->diffForHumans() : null,
            'expires_at' => $this->expires_at ? $this->expires_at->toISOString() : null,
        ];
    }
}
