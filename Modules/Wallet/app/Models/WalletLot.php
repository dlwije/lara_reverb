<?php

namespace Botble\Wallet\Models;

use App\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\GiftCard;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletLot extends BaseModel
{
    protected $table = 'wallet_lots';

    protected $fillable = [
        'user_id', 'source', 'amount', 'remaining', 'base_value', 'bonus_value',
        'currency', 'acquired_at', 'expires_at', 'status', 'gift_card_id',
        'promo_rule_id', 'metadata', 'ref_number'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining' => 'decimal:2',
        'base_value' => 'decimal:2',
        'bonus_value' => 'decimal:2',
        'acquired_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_LOCKED = 'locked';
    const STATUS_CONSUMED = 'consumed';

    // Source constants
    const SOURCE_GIFT_CARD = 'gift_card';
    const SOURCE_REFUND = 'refund';
    const SOURCE_ADJUSTMENT = 'adjustment';
    const SOURCE_PROMO = 'promo';
    const SOURCE_CREDIT_CARD = 'credit_card';
    const SOURCE_LOYALTY_POINT = 'loyalty_point';

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class, 'gift_card_id');
    }

    public function isFullyConsumed(): bool
    {
        return $this->remaining <= 0 || $this->status === self::STATUS_CONSUMED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpired() && !$this->isFullyConsumed();
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return $this->expires_at->diffInDays(now());
    }

    public function getConsumedAmountAttribute(): float
    {
        return $this->amount - $this->remaining;
    }

    public function getConsumedPercentageAttribute(): float
    {
        if ($this->amount == 0) return 0;
        return ($this->consumed_amount / $this->amount) * 100;
    }

    /* Scopes */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }

    /* Accessors */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->amount, 2) . ' ' . $this->currency
        );
    }

    protected function formattedRemaining(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->remaining, 2) . ' ' . $this->currency
        );
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->ref_number = $lot->ref_number ?? self::generateRef();
        });
    }

    private static function generateRef(): string
    {
        return 'LOT' . strtoupper(Str::random(10)); // e.g., LOTAB12CD34EF
    }
}
