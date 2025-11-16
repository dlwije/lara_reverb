<?php

namespace App\Models\Sma\Product;

use App\Casts\ProductStoreTaxes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductStore extends Pivot
{
    protected $casts = ['taxes' => ProductStoreTaxes::class];
}
