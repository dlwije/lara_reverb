<?php

namespace Modules\Chat\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Chat\Models\ChatConversation::class;

    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['private', 'group']),
            'title' => $this->faker->optional()->sentence(3),
            'created_by' => User::factory(),
            'last_message_at' => now(),
        ];
    }
}

