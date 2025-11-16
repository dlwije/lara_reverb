<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nnjeim\World\Models\Currency;

// use Modules\Ecommerce\Database\Factories\ShopCurrencyFactory;

class ShopCurrency extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
