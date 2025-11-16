<?php

namespace App\Models\Sma\Order;

use App\Models\Model;
use App\Models\Sma\Setting\Tax;
use App\Models\Sma\Product\Unit;
use App\Models\Sma\Product\Product;
use App\Models\Sma\Product\Variation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuotationItem extends Model
{
    use HasFactory;

    public static $hasStore = true;

    protected $with = ['taxes:id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function variations()
    {
        return $this->belongsToMany(Variation::class)
            ->using(ItemVariation::class)
            ->withPivot(
                'quantity', 'base_quantity', 'unit_id',
                'price', 'net_price', 'unit_price',
                'discount', 'discount_amount', 'total_discount_amount',
                'taxes', 'tax_amount', 'total_tax_amount', 'subtotal', 'total'
            );
    }

    public function scopeOfStore($query, $store)
    {
        return $query->where('store_id', $store);
    }

    public function scopeWithProduct($query)
    {
        $query->with(['product' => fn ($q) => $q->with('selectedStore')]);
    }

    public function forceDelete()
    {
        $this->taxes()->detach();

        return parent::forceDelete();
    }
}
