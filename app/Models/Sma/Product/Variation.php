<?php

namespace App\Models\Sma\Product;

use App\Models\Model;
use App\Traits\HasStock;
use App\Models\Sma\Setting\Store;

class Variation extends Model
{
    use HasStock;

    public static $hasSku = true;

    public $casts = ['meta' => 'array'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function storeStock($store = null)
    {
        return $this->hasOne(Stock::class)->ofMany([], fn ($q) => $q->ofStore($store)
        );
    }

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    public function getStock($store_id = null)
    {
        return $this->getVariationStock($store_id);
    }

    public function adjustStock($type, $quantity, $data)
    {
        $this->adjustVariationStock($type, $quantity, $data);
    }

    protected static function booted()
    {
        parent::booted();

        static::created(function ($model) {
            $stores = Store::pluck('id');
            foreach ($stores as $store) {
                $model->getStock($store);
            }
        });
    }
}
