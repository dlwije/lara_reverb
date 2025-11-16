<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockCountItem extends Model
{
    use HasFactory;

    public function stockCount()
    {
        return $this->belongsTo(StockCount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'code')->withoutGlobalScopes();
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_code', 'code');
    }
}
