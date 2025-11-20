<?php

namespace App\Models\Sma\Product;

use App\Helpers\ImageHelper;
use App\Models\Model;
use App\Traits\HasStock;
use App\Traits\HasPromotions;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Traits\HasPosImages;
use Spatie\Sluggable\HasSlug;
use App\Traits\HasAttachments;
use App\Models\Sma\Setting\Tax;
use App\Models\Sma\Setting\Store;
use Spatie\Sluggable\SlugOptions;
use App\Models\Sma\Order\SaleItem;
use App\Models\Sma\People\Supplier;
use App\Models\Sma\Order\PurchaseItem;
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasSlug;
    use HasStock;
    use HasFactory;
    use HasPromotions;
    use HasAttachments;
    use HasSchemalessAttributes;
    use HasPosImages;

    public static $hasSku = true;

    public $casts = ['variants' => 'array'];

    protected $with = ['taxes'];

    public function products()
    {
        return $this->belongsToMany(Product::class, null, 'combo_id')->withPivot(['quantity']);
    }

    public function selectedStore()
    {
        return $this->stores()->where('store_id', session('selected_store_id'))
            ->using(ProductStore::class)->withPivot(['price', 'quantity', 'alert_quantity', 'taxes']);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class)
            ->using(ProductStore::class)->withPivot(['price', 'quantity', 'alert_quantity', 'taxes']);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->with('children');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function serials()
    {
        return $this->hasMany(Serial::class)->orderBy('number');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class)->whereNull('variation_id');
    }

    public function storeStock($store = null)
    {
        return $this->hasOne(Stock::class)->ofMany([], fn ($q) => $q->ofStore($store)
        );
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class)->with('subunits');
    }

    public function unitPrices()
    {
        return $this->morphMany(UnitPrice::class, 'subject');
    }

    public function unitPrice()
    {
        return $this->morphMany(UnitPrice::class, 'subject');
    }

    public function variations()
    {
        return $this->hasMany(Variation::class)->orderBy('sku');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'product_promotion');
    }

    public function scopeWithActivePromotions($query)
    {
        return $query->whereHas('promotions', function($q) {
            $q->active();
        });
    }

    public function scopeActive($query)
    {
        $query->where('active', 1);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFilter($query, $filters = [])
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->ofType($type))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereIn('id', $products))
            ->when($filters['category_id'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['store'] ?? null, fn ($query, $store) => $query->whereHas('stocks', fn ($q) => $q->ofStore($store)))
            ->when($filters['reorder'] ?? null, fn ($query) => $query->whereHas('stocks', fn ($q) => $q->whereHasBalanceBelow('alert_quantity')))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['code', 'name', 'description'], 'like', "%$search%")
            ->orWhereRelation('brand', 'name', 'like', "%{$search}%")
            ->orWhereRelation('category', 'name', 'like', "%{$search}%");
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $query->latest();
        } elseif (str($sort)->contains('extra_attributes')) {
            [$relation, $column] = explode('.', $sort);
            [$column, $direction] = explode(':', $column);
            $query->orderByRaw('CAST(JSON_EXTRACT(extra_attributes, "$.' . $column . '") AS CHAR) ' . $direction);
        } else {
            if (str($sort)->contains('.')) {
                $relation_tables = [
                    'brand'    => ['table' => 'brands', 'model' => 'App\Models\Sma\Product\Brand'],
                    'category' => ['table' => 'categories', 'model' => 'App\Models\Sma\Product\Category'],
                    'supplier' => ['table' => 'suppliers', 'model' => 'App\Models\Sma\People\Supplier'],
                ];
                [$relation, $column] = explode('.', $sort);
                [$column, $direction] = explode(':', $column);
                $table = $relation_tables[$relation];
                $query->orderBy($table['model']::select($column)->whereColumn($table['table'] . '.id', 'products.' . $relation . '_id'), $direction);
            } else {
                [$column, $direction] = explode(':', $sort);
                $query->orderBy($column, $direction);
            }
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
        if ($this->saleItems()->exists() || $this->purchaseItems()->exists()) {
            return false;
        }

        return parent::delete();
    }

    public function forceDelete()
    {
        if ($this->saleItems()->exists() || $this->purchaseItems()->exists()) {
            return false;
        }

        log_activity(__('{record} has permanently deleted.', ['record' => 'Product']), $this, $this, 'Product');

        return parent::forceDelete();
    }

    public function getStock($store_id = null)
    {
        return $this->getProductStock($store_id);
    }

    public function adjustStock($type, $quantity, $data)
    {
        $this->adjustProductStock($type, $quantity, $data);
    }

    public function setStock()
    {
        $this->setProductStock();
    }

    /** E-commerce **/
    /**
     * Get the transformed image URL
     */
    public function getImageUrlAttribute()
    {
        // Debug the transformation
        $debugInfo = ImageHelper::debugUrlTransformation($this->image);
        Log::info('Product/Product image URL transformation', $debugInfo);
        if (empty($this->image)) {
            return ImageHelper::posImageWithFallback(null);
        }

        return ImageHelper::posImageWithFallback($this->image);
    }

    /**
     * Get multiple gallery image URLs
     */
    public function getGalleryUrlsAttribute()
    {
        if (empty($this->images)) {
            return [ImageHelper::posImageWithFallback(null)];
        }

        if (is_string($this->images)) {
            // If images is a JSON string
            $imagesArray = json_decode($this->images, true) ?? [$this->images];
        } else {
            $imagesArray = (array) $this->images;
        }

        return array_map(function($image) {
            return ImageHelper::posImageWithFallback($image);
        }, $imagesArray);
    }

    /**
     * Get the first gallery image URL (for thumbnails)
     */
    public function getThumbnailUrlAttribute()
    {
        $galleryUrls = $this->gallery_urls;
        return $galleryUrls[0] ?? $this->image_url;
    }

    /**
     * Check if image exists in POS system
     */
    public function getImageExistsAttribute()
    {
        if (empty($this->image)) {
            return false;
        }

        return ImageHelper::posImageExists($this->image);
    }

    protected static function booted()
    {
        parent::booted();

        static::retrieved(function (Product $product) {
            $user = auth()->user();
            if ($user && $user->cant('show-cost')) {
                $product->setHidden(['cost']);
            }
        });

        static::created(function ($model) {
            $model->setStock();
        });
    }
}
