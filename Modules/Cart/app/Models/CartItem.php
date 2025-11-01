<?php

namespace Modules\Cart\Models;

use App\Models\Sma\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Cart\Database\Factories\CartItemFactory;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'row_id',
        'product_id',
        'name',
        'qty',
        'price',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'subtotal',
        'total',
        'options',
        'product_attributes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'options' => 'array',
        'product_attributes' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->qty * $this->price;
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;

        $this->save();
    }

    public function getOption($key, $default = null)
    {
        return data_get($this->options, $key, $default);
    }

    public function getProductAttribute($key, $default = null)
    {
        return data_get($this->product_attributes, $key, $default);
    }
}
