<?php

namespace Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Chat\Database\Factories\ChatMessageFactory;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_messages';
    /**
     * The attributes that are mass assignable.
     */
//    protected $fillable = ['user_id', 'from', 'message'];

    protected $fillable = [
        'conversation_id', 'sender_id', 'receiver_id',
        'message', 'attachment_url', 'attachment_type', 'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];


    // The sender of the message
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // The receiver of the message
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}
