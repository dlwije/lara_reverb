<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Chat\Database\Factories\ChatConversationParticipantFactory;

class ChatConversationParticipant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'chat_conversation_participants';

    protected $fillable = ['conversation_id', 'user_id', 'joined_at', 'last_read_at'];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
