<?php

namespace Modules\Chat\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\ChatConversation;

class ChatConversationParticipantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Chat\Models\ChatConversationParticipant::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'conversation_id' => ChatConversation::factory(),
            'user_id' => User::factory(),
            'joined_at' => now(),
            'last_read_at' => now()->subMinutes(rand(0, 30)),
        ];
    }
}

