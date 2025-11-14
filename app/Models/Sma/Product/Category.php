<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use App\Traits\HasPromotions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    /** E-commerce **/

    /**
     * Get popular categories based on product sales
     */
    public function scopePopular($query, $limit = 10, $period = 30)
    {
        return $query->withCount([
            'saleItems as total_sales' => function ($query) use ($period) {
                $query->select(\DB::raw('COALESCE(SUM(quantity), 0)'))
                    ->when($period, function ($q) use ($period) {
                        $q->whereHas('sale', function ($saleQuery) use ($period) {
                            $saleQuery->where('created_at', '>=', now()->subDays($period));
                        });
                    });
            },
            'products as total_products',
            'saleItems as recent_orders_count' => function ($query) use ($period) {
                $query->select(\DB::raw('COUNT(DISTINCT sale_id)'))
                    ->when($period, function ($q) use ($period) {
                        $q->whereHas('sale', function ($saleQuery) use ($period) {
                            $saleQuery->where('created_at', '>=', now()->subDays($period));
                        });
                    });
            }
        ])
            ->whereHas('saleItems')
            ->active()
            ->orderBy('total_sales', 'desc')
            ->orderBy('recent_orders_count', 'desc')
            ->orderBy('total_products', 'desc')
            ->limit($limit);
    }

    /**
     * Get trending categories (recent popularity)
     */
    public function scopeTrending($query, $limit = 10, $period = 7)
    {
        return $query->withCount([
            'saleItems as recent_sales' => function ($query) use ($period) {
                $query->select(\DB::raw('COALESCE(SUM(quantity), 0)'))
                    ->whereHas('sale', function ($saleQuery) use ($period) {
                        $saleQuery->where('created_at', '>=', now()->subDays($period));
                    });
            }
        ])
            ->where('recent_sales', '>', 0)
            ->active()
            ->orderBy('recent_sales', 'desc')
            ->limit($limit);
    }

    /**
     * Get featured categories (manual selection or based on specific criteria)
     */
    public function scopeFeatured($query, $limit = 8)
    {
        // If you have a 'featured' column, use it. Otherwise use most products.
        if (\Schema::hasColumn('categories', 'featured')) {
            return $query->where('featured', true)
                ->active()
                ->withCount('products as total_products')
                ->orderBy('total_products', 'desc')
                ->limit($limit);
        }

        // Fallback: get categories with most products
        return $query->withCount('products as total_products')
            ->having('total_products', '>', 5)
            ->active()
            ->orderBy('total_products', 'desc')
            ->limit($limit);
    }

    /**
     * Get categories with most products
     */
    public function scopeMostProducts($query, $limit = 10)
    {
        return $query->withCount('products as total_products')
            ->having('total_products', '>', 0)
            ->active()
            ->orderBy('total_products', 'desc')
            ->limit($limit);
    }

    /**
     * Get parent categories only for slider
     */
    public function scopeForSlider($query)
    {
        return $query->onlyParent()
            ->active()
            ->with(['children' => function ($q) {
                $q->active()->withCount('products');
            }]);
    }

    /**
     * Get categories with basic data for slider
     * REMOVED select() to preserve withCount columns
     */
    public function scopeWithBasicData($query)
    {
        return $query->withCount(['products' => function ($q) {
            $q->active();
        }]);
        // Removed ->select(['id', 'name', 'slug', 'photo', 'description'])
        // as it conflicts with orderBy on withCount columns
    }

    /**
     * Static method to get popular categories for frontend
     */
    public static function getPopularForSlider($limit = 8)
    {
        $categories = static::popular($limit)
            ->forSlider()
            ->withBasicData()
            ->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'products_count' => $category->products_count,
                'total_sales' => $category->total_sales ?? 0,
                'image' => $category->getImageUrl(),
                'url' => route('product.categories.show', $category->slug),
            ];
        });
    }

    /**
     * Alternative: Simple popular categories without complex sales calculations
     */
    public static function getSimplePopularForSlider($limit = 8)
    {
        return static::withCount(['products' => function ($query) {
            $query->active();
        }])
            ->whereHas('products') // Only categories with products
            ->active()
            ->onlyParent()
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'products_count' => $category->products_count,
                    'image' => $category->getImageUrl(),
                    'url' => route('product.categories.show', $category->slug),
                ];
            });
    }

    /**
     * Static method to get featured categories for frontend
     */
    public static function getFeaturedForSlider($limit = 6)
    {
        $categories = static::featured($limit)
            ->forSlider()
            ->withBasicData()
            ->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'products_count' => $category->products_count,
                'image' => $category->getImageUrl(),
                'url' => route('product.categories.show', $category->slug),
                'is_featured' => true,
            ];
        });
    }

    /**
     * Get category image URL using the photo column
     */
    public function getImageUrl()
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }

        // Fallback: get image from category's first product
        $productWithImage = $this->products()
            ->whereNotNull('photo')
            ->first();

        if ($productWithImage && $productWithImage->image) {
            return asset('storage/' . $productWithImage->image);
        }

        // Final fallback: default category image
        return null;//asset('images/default-category.jpg');
    }

    /**
     * Get category by slug with all related data
     */
    public static function getBySlug($slug)
    {
        return static::with([
            'products' => function ($query) {
                $query->active()
                    ->with(['brand', 'category'])
                    ->withCount('saleItems')
                    ->orderBy('created_at', 'desc');
            },
            'children' => function ($query) {
                $query->active()->withCount('products');
            },
            'category' => function ($query) {
                $query->select(['id', 'name', 'slug']);
            }
        ])
            ->withCount('products')
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();
    }

    /**
     * Get categories with their nested children for navigation
     */
    public static function getForNavigation($limit = 10)
    {
        return static::popular($limit)
            ->forSlider()
            ->with(['children' => function ($q) {
                $q->active()->withCount('products');
            }])
            ->get();
    }

    /**
     * Alternative navigation method using simple approach
     */
    public static function getSimpleForNavigation($limit = 10)
    {
        return static::withCount('products')
            ->having('products_count', '>', 0)
            ->active()
            ->onlyParent()
            ->with(['children' => function ($q) {
                $q->active()->withCount('products');
            }])
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get breadcrumb for category
     */
    public function getBreadcrumb()
    {
        $breadcrumb = [];
        $current = $this;

        while ($current) {
            $breadcrumb[] = [
                'name' => $current->name,
                'slug' => $current->slug,
                'url' => route('product.categories.show', $current->slug),
            ];
            $current = $current->category;
        }

        return array_reverse($breadcrumb);
    }
}
