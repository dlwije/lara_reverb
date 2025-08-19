<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use App\Casts\AppDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promotion extends Model
{
    use HasFactory;

    public static $types = ['simple',  'advance', 'BXGY', 'SXGD'];

    // protected $with = ['productToBuy:id,name,code', 'productToGet:id,name,code'];

    protected function casts(): array
    {
        return [
            'start_date' => AppDate::class,
            'end_date'   => AppDate::class,
            'created_at' => AppDate::class . ':time',
            'updated_at' => AppDate::class . ':time',
        ];
    }

    public function delete()
    {
        $this->products()->detach();
        $this->categories()->detach();

        return parent::delete();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function productToBuy()
    {
        return $this->belongsTo(Product::class, 'product_id_to_buy')->without('taxes');
    }

    public function productToGet()
    {
        return $this->belongsTo(Product::class, 'product_id_to_get')->without('taxes');
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['name', 'type'], 'like', "%$search%");
    }

    public static function scopeValid($query, $date = null)
    {
        $today = $date ?? now();
        $query->where('active', 1)->where(
            fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today)
        )->where(
            fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today)
        );

        return $query;
    }
}
