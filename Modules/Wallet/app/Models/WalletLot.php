<?php

namespace Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GiftCard\Models\GiftCard;
use Modules\PromoRules\Models\PromoRule;

// use Modules\Wallet\Database\Factories\WalletLotFactory;

class WalletLot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id', 'source', 'amount', 'base_value', 'bonus_value', 'currency',
        'acquired_at', 'expires_at', 'status', 'gift_card_id', 'promo_rule_id'
    ];

    protected $casts = [
        'acquired_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount' => 'decimal:2',
        'base_value' => 'decimal:2',
        'bonus_value' => 'decimal:2'
    ];

    /**
     * Check if lot is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): int
    {
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with GiftCard
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class, 'gift_card_id');
    }

    /**
     * Relationship with PromoRule
     */
    public function promoRule(): BelongsTo
    {
        return $this->belongsTo(PromoRule::class, 'promo_rule_id');
    }
}
