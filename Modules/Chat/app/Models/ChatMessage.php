<?php

namespace Modules\Chat\Models;

use App\Models\User;
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
    protected $fillable = ['user_id', 'from', 'message'];

    // protected static function newFactory(): ChatMessageFactory
    // {
    //     // return ChatMessageFactory::new();
    // }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function from(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'from');
    }
}
