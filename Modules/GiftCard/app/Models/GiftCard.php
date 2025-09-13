<?php

namespace Modules\GiftCard\Models;

use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PromoRules\Models\GiftCardBatch;
use Modules\PromoRules\Models\PromoRule;

// use Modules\GiftCard\Database\Factories\GiftCardFactory;

class GiftCard extends Model
{
    use HasFactory;

    protected $table = 'st_gift_cards';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code', 'original_value', 'base_value', 'bonus_value', 'final_credit',
        'currency', 'batch_id', 'status', 'issued_to', 'redeemed_by',
        'redeemed_at', 'expires_at', 'promo_rule_id'
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'original_value' => 'decimal:2',
        'base_value' => 'decimal:2',
        'bonus_value' => 'decimal:2',
        'final_credit' => 'decimal:2'
    ];

    /**
     * Check if gift card is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    /**
     * Check if gift card is redeemed
     */
    public function isRedeemed(): bool
    {
        return $this->status === 'redeemed' && $this->redeemed_at !== null;
    }

    /**
     * Check if gift card is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Relationship with Batch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(GiftCardBatch::class, 'batch_id');
    }

    /**
     * Relationship with PromoRule
     */
    public function promoRule():BelongsTo
    {
        return $this->belongsTo(PromoRule::class, 'promo_rule_id');
    }

    /**
     * Relationship with User who redeemed
     */
    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }
}
