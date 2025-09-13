<?php

namespace Modules\PromoRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GiftCard\Models\GiftCard;

// use Modules\PromoRules\Database\Factories\GiftCardBatchFactory;

class GiftCardBatch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name', 'batch_code', 'quantity', 'original_value',
        'promo_rule_id', 'final_credit', 'expires_at', 'status', 'metadata'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'original_value' => 'decimal:2',
        'final_credit' => 'decimal:2',
        'metadata' => 'array'
    ];


    /**
     * Relationship with GiftCards
     */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'batch_id');
    }

    /**
     * Relationship with PromoRule
     */
    public function promoRule(): BelongsTo
    {
        return $this->belongsTo(PromoRule::class, 'promo_rule_id');
    }

    /**
     * Get total redeemed value
     */
    public function getTotalRedeemedAttribute(): float
    {
        return $this->giftCards()->where('status', 'redeemed')->sum('final_credit');
    }

    /**
     * Get redemption rate
     */
    public function getRedemptionRateAttribute(): float
    {
        if($this->quantity === 0) return 0;

        $redeemedCount = $this->giftCards()->where('status', 'redeemed')->count();

        return ($redeemedCount / $this->quantity) * 100;
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Activate batch
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Deactivate batch
     */
    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }
}
