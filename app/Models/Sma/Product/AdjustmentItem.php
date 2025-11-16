<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdjustmentItem extends Model
{
    use HasFactory;

    public static $hasStore = true;

    public function adjustment()
    {
        return $this->belongsTo(Adjustment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function serials()
    {
        return $this->belongsToMany(Serial::class);
    }

    public function variations()
    {
        return $this->belongsToMany(Variation::class)->withPivot('quantity');
    }

    public function scopeWithProduct($query)
    {
        $query->with(['product' => fn ($q) => $q->with('selectedStore')]);
    }
}
