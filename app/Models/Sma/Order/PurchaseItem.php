<?php

namespace App\Models\Sma\Order;

use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Sma\Setting\Tax;
use App\Models\Sma\Pos\Register;
use App\Models\Sma\Product\Unit;
use App\Models\Sma\Product\Serial;
use App\Models\Sma\Product\Product;
use App\Models\Sma\Product\Variation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Model
{
    use HasFactory;

    public static $hasStore = true;

    protected $with = ['taxes:id'];

    protected function casts(): array
    {
        return [
            'expiry_date' => AppDate::class,
            // 'created_at'  => AppDate::class . ':time',
            // 'updated_at'  => AppDate::class . ':time',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
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
                'quantity', 'base_quantity', 'balance', 'unit_id',
                'discount', 'discount_amount', 'total_discount_amount',
                'taxes', 'tax_amount', 'total_tax_amount', 'subtotal', 'total'
            );
    }

    public function scopeAvailable($query)
    {
        $query->whereNotNull('balance')->where('balance', '>', 0);
    }

    public function scopeExpired($query)
    {
        $query->whereNotNull('expired_quantity');
    }

    public function scopeNotExpired($query)
    {
        $query->whereNull('expired_quantity');
    }

    public function scopeOfStore($query, $store)
    {
        return $query->where('store_id', $store);
    }

    public function scopeOversold($query)
    {
        $query->whereNull('balance')->orWhere('balance', '<', 0);
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
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereHas('items', fn ($q) => $q->whereIn('product_id', $products)));
    }

    public function forceDelete()
    {
        $this->taxes()->detach();

        return parent::forceDelete();
    }
}
