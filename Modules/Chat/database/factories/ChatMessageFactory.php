<?php

namespace Modules\Chat\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\ChatConversation;

class ChatMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Chat\Models\ChatMessage::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'message' => $this->faker->sentence(),
            'attachment_url' => null,
            'attachment_type' => null,
            'read_at' => $this->faker->optional()->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}

