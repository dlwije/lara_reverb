<?php

namespace App\Models\Sma\Order;

use App\Models\Sma\Product\Unit;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ItemVariation extends Pivot
{
    protected $appends = ['unit'];

    protected $casts = ['taxes' => 'array'];

    protected function unit(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['unit_id'] ? Unit::find($attributes['unit_id'], ['id', 'code', 'name']) : null,
        );
    }
}
