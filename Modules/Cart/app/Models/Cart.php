<?php

namespace Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// use Modules\Cart\Database\Factories\CartFactory;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'identifier',
        'instance',
        'content',
        'user_id',
        'coupon_code',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'subtotal',
        'total',
        'notes',
        'currency',
        'expires_at',
    ];

    protected $casts = [
        'content' => 'array',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function abandonment(): HasOne
    {
        return $this->hasOne(CartAbandonment::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(CartCoupon::class);
    }

    public function scopeInstance($query, $instance)
    {
        return $query->where('instance', $instance);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForIdentifier($query, $identifier)
    {
        return $query->where('identifier', $identifier);
    }

    public function recalculateTotals()
    {
        $subtotal = $this->items->sum('total');
        $this->update([
            'subtotal' => $subtotal,
            'total' => $subtotal - $this->discount_amount + $this->tax_amount + $this->shipping_amount,
        ]);
    }
}
