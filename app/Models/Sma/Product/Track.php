<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Sma\Setting\Store;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Track extends Model
{
    use HasFactory;

    public static $hasStore = true;

    protected function casts(): array
    {
        return [
            'created_at' => AppDate::class . ':time',
            'updated_at' => AppDate::class . ':time',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfProduct($query, $product)
    {
        return $query->where('product_id', $product);
    }

    public function scopeOfStore($query, $store = null)
    {
        return $query->where('store_id', $store ?? session('selected_store_id'));
    }

    public function scopeFilter($query, $filters)
    {
        $query->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['product_id'] ?? null, fn ($query, $product_id) => $query->ofProduct($product_id))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['stocks'] ?? null, fn ($query, $stocks) => $query->whereHasMorph('trackable', '*', function ($query) use ($stocks) {
                $query->where('trackable_type', get_class($stocks->first()))->whereIn('trackable_id', $stocks->pluck('id'));
            }))
            ->when($filters['trackable'] ?? null, fn ($query, $trackable) => $query->whereMorphedTo('trackable', $trackable));
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['value', 'description'], 'like', "%$search%");
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Track $track) {
            $track->store_id = $track->store_id ?? $track->trackable->store_id;
            $track->product_id = $track->product_id ?? $track->trackable->product_id;
            $track->variation_id = $track->variation_id ?? $track->trackable->variation_id;
        });
    }
}
