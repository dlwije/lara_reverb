<?php

namespace Modules\Chat\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Models\ChatConversationParticipant;
use Modules\Chat\Models\ChatMessage;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 users
        $users = User::factory(10)->create();

        // Create 5 private conversations
        ChatConversation::factory(5)->create(['type' => 'private'])->each(function ($conversation) use ($users) {
            $participants = $users->random(2);

            foreach ($participants as $user) {
                ChatConversationParticipant::factory()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ]);
            }

            // Create 10 messages per conversation
            $messages = ChatMessage::factory(10)->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $participants[0]->id,
                'receiver_id' => $participants[1]->id,
            ]);

            // Set last message info
            $lastMessage = $messages->last();
            $conversation->update([
                'last_message_id' => $lastMessage->id,
                'last_message_at' => $lastMessage->created_at,
            ]);
        });

        // Create 3 group conversations
        ChatConversation::factory(3)->create(['type' => 'group'])->each(function ($conversation) use ($users) {
            $participants = $users->random(rand(3, 6));

            foreach ($participants as $user) {
                ChatConversationParticipant::factory()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ]);
            }

            // Create messages in group
            $messages = collect();
            for ($i = 0; $i < 15; $i++) {
                $sender = $participants->random();
                $receiver = $participants->random();

                $messages->push(ChatMessage::factory()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                ]));
            }

            // Set last message info
            $lastMessage = $messages->last();
            $conversation->update([
                'last_message_id' => $lastMessage->id,
                'last_message_at' => $lastMessage->created_at,
            ]);
        });
    }
}
