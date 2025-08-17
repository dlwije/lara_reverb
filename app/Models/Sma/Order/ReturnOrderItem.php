<?php

namespace App\Models\Sma\Order;

use App\Models\Model;
use App\Models\Sma\Setting\Tax;
use App\Models\Sma\Pos\Register;
use App\Models\Sma\Product\Unit;
use App\Models\Sma\Product\Serial;
use App\Models\Sma\Product\Product;
use App\Models\Sma\Product\Variation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnOrderItem extends Model
{
    use HasFactory;

    public static $hasStore = true;

    protected $with = ['taxes:id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function serials()
    {
        return $this->belongsToMany(Serial::class);
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
                'cost', 'net_cost', 'unit_cost',
                'price', 'net_price', 'unit_price',
                'quantity', 'base_quantity', 'unit_id',
                'discount', 'discount_amount', 'total_discount_amount',
                'taxes', 'tax_amount', 'total_tax_amount', 'subtotal', 'total'
            );
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
