<?php

namespace Modules\Wallet\Models;

//use App\Models\Model;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\GiftCard\Models\GiftCard;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id', 'direction', 'amount', 'base_value', 'bonus_value', 'currency',
        'type', 'status', 'ref_type', 'ref_id', 'lot_allocation', 'gift_card_id',
        'promo_rule_id', 'ip', 'user_agent', 'created_by','ref_number'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'base_value' => 'decimal:2',
        'bonus_value' => 'decimal:2',
        'lot_allocation' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Direction constants
    const DIRECTION_CREDIT = 'CR';
    const DIRECTION_DEBIT = 'DR';

    // Type constants
    const TYPE_GIFT_CARD_REDEEM = 'gift_card_redeem';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_REFUND = 'refund_credit';
    const TYPE_ADJUSTMENT = 'admin_adjustment';
    const TYPE_PROMO = 'promo_credit';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

//    public function promoRule(): BelongsTo
//    {
//        return $this->belongsTo(PromoRule::class);
//    }

    public function lots()
    {
        return $this->hasMany(WalletLot::class, 'user_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCredit(): bool
    {
        return $this->direction === self::DIRECTION_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->direction === self::DIRECTION_DEBIT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /* Accessors */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $sign = $this->isDebit() ? '-' : '+';
                return $sign . number_format($this->amount, 2) . ' ' . $this->currency;
            }
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->type) {
                    self::TYPE_GIFT_CARD_REDEEM => 'Gift Card Redemption',
                    self::TYPE_PURCHASE => 'Purchase',
                    self::TYPE_REFUND => 'Refund',
                    self::TYPE_ADJUSTMENT => 'Admin Adjustment',
                    self::TYPE_PROMO => 'Promotional Credit',
                    default => ucfirst(str_replace('_', ' ', $this->type))
                };
            }
        );
    }

    protected static function booted()
    {
        static::creating(function ($tx) {
            $tx->ref_number = $tx->ref_number ?? self::generateRef();
        });
    }

    protected static function generateRef(): string
    {
        return 'ET' . strtoupper(Str::random(10)); // e.g., ETXZ89JKL123
    }
}
