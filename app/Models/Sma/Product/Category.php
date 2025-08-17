<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use App\Traits\HasPromotions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\Sma\Order\SaleItem;
use App\Models\Sma\Order\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasSlug;
    use HasFactory;
    use HasPromotions;

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function childProducts()
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    public function children()
    {
        return $this->hasMany(self::class);
    }

    public function category()
    {
        return $this->belongsTo(self::class);
    }

    public function purchaseItems()
    {
        return $this->hasManyThrough(PurchaseItem::class, Product::class);
    }

    public function saleItems()
    {
        return $this->hasManyThrough(SaleItem::class, Product::class);
    }

    public function scopeOnlyParent($query)
    {
        return $query->whereNull('category_id');
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
        } elseif (str($sort)->contains('.')) {
            [$relation, $column] = explode('.', $sort);
            [$column, $direction] = explode(':', $column);
            $query->withAggregate($relation, $column)->orderBy($relation . '_' . $column, $direction);
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

    public static function tree($columns = null, $all = false)
    {
        $allCategories = Category::when(! $all, fn ($q) => $q->active())->get($columns);
        $categories = $allCategories->whereNull('category_id');
        self::makeTree($categories, $allCategories);

        return $categories;
    }

    private static function makeTree($categories, $allCategories)
    {
        foreach ($categories as $category) {
            $category->children = $allCategories->where('category_id', $category->id)->values();
            if ($category->children->isNotEmpty()) {
                $category->setRelation('children', $category->children);
                self::makeTree($category->children, $allCategories);
            }
        }
    }

    public function delete()
    {
        if ($this->children()->exists() || $this->products()->exists()) {
            return false;
        }

        return parent::delete();
    }

    public function forceDelete()
    {
        if ($this->children()->exists() || $this->products()->exists()) {
            return false;
        }

        log_activity(__('{record} has permanently deleted.', ['record' => 'Category']), $this, $this, 'Category');

        return parent::forceDelete();
    }
}
