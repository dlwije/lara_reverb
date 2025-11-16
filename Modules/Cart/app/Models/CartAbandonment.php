<?php

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Cart\Database\Factories\CartAbandonmentFactory;

class CartAbandonment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'email',
        'phone',
        'token',
        'email_sent',
        'email_sent_at',
        'recovered',
        'recovered_at',
        'reminder_count',
        'last_reminder_sent_at',
        'customer_data',
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'recovered' => 'boolean',
        'recovered_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'customer_data' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function markAsRecovered()
    {
        $this->update([
            'recovered' => true,
            'recovered_at' => now(),
        ]);
    }

    public function incrementReminder()
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_sent_at' => now()]);
    }
}
