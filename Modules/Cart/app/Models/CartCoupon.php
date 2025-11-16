<?php

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Cart\Database\Factories\CartCouponFactory;

class CartCoupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'code',
        'type',
        'value',
        'discount_amount',
        'data',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'data' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
