<?php

namespace App\Models\Sma\Product;

use App\Models\Model;

class Serial extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // public function purchase()
    // {
    //     return $this->belongsTo(Purchase::class);
    // }

    // public function purchaseItem()
    // {
    //     return $this->belongsTo(PurchaseItem::class);
    // }

    public function scopeAvailable($query)
    {
        return $query->whereNull('sold')->orWhere('sold', 0);
    }

    public function scopeInitial($query)
    {
        return $query->whereNULL('purchase_id');
    }

    public function scopeSold($query)
    {
        return $query->where('sold', 1);
    }
}
