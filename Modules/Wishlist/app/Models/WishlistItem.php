<?php

namespace Modules\Wishlist\Models;

use App\Models\Sma\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Wishlist\Database\Factories\WishlistItemFactory;

class WishlistItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'wishlist_id',
        'row_id',
        'product_id',
        'name',
        'price',
        'options',
        'product_attributes',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'options' => 'array',
        'product_attributes' => 'array',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
