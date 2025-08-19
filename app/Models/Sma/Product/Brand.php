<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\Sma\Order\SaleItem;
use App\Models\Sma\Order\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    use HasFactory;
    use HasSlug;

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseItems()
    {
        return $this->hasManyThrough(PurchaseItem::class, Product::class);
    }

    public function saleItems()
    {
        return $this->hasManyThrough(SaleItem::class, Product::class);
    }

    public function scopeActive($query)
    {
        $query->where('active', 1);
    }

    public function scopeFilter($query, $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['name', 'description'], 'like', "%$search%");
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $query->latest();
        } else {
            [$column, $direction] = explode(':', $sort);
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')
            ->saveSlugsTo('slug')->slugsShouldBeNoLongerThan(50);
    }

    public function delete()
    {
        if ($this->products()->exists()) {
            return false;
        }

        return parent::delete();
    }

    public function forceDelete()
    {
        if ($this->products()->exists()) {
            return false;
        }

        log_activity(__('{record} has permanently deleted.', ['record' => 'Brand']), $this, $this, 'Brand');

        return parent::forceDelete();
    }
}
