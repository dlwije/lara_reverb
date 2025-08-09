<?php

namespace Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatConversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['type', 'title', 'created_by', 'last_message_id', 'last_message_at'];

//    public function participants()
//    {
//        return $this->belongsToMany(User::class, 'chat_conversation_participants')
//            ->withPivot('joined_at', 'last_read_at')
//            ->withTimestamps();
//    }

    public function participants()
    {
        return $this->belongsToMany(
            User::class,
            'chat_conversation_participants', // pivot table
            'conversation_id',                 // FK on pivot table for ChatConversation
            'user_id'                         // FK on pivot table for User
        )
            ->withPivot('joined_at', 'last_read_at')
            ->withTimestamps();
    }

    public function lastMessage()
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }
}
