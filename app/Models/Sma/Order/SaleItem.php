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

class SaleItem extends Model
{
    use HasFactory;

    public static $hasStore = true;

    protected $with = ['taxes:id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
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
                'quantity', 'base_quantity', 'unit_id',
                'discount', 'discount_amount', 'total_discount_amount',
                'price', 'net_price', 'unit_price', 'cost', 'total_cost',
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

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['end_date'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start_date'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereIn('product_id', $products));
    }

    public function forceDelete()
    {
        $this->taxes()->detach();

        return parent::forceDelete();
    }
}
