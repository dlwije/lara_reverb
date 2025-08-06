<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Chat\Database\Factories\ChatMessageFactory;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): ChatMessageFactory
    // {
    //     // return ChatMessageFactory::new();
    // }
}
