<?php

namespace App\Models\Sma\Product;

use App\Models\Model;

class UnitPrice extends Model
{
    public function subject()
    {
        return $this->morphTo();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
