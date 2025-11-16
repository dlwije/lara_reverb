<?php

namespace Modules\Ecommerce\Models;

use App\Models\Sma\Product\Category;
use App\Traits\HasPromotions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

// use Modules\Ecommerce\Database\Factories\ProductCategoryFactory;

class ProductCategory extends Category
{
    use HasSlug;
    use HasFactory;
    use HasPromotions;

    protected $table = 'categories';
    /**
     * The attributes that are mass assignable.
     */

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')
            ->saveSlugsTo('slug')->slugsShouldBeNoLongerThan(50);
    }


}
