<?php

namespace App\Traits;

use App\Models\Sma\Product\Promotion;

trait HasPromotions
{
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function validPromotions()
    {
        return $this->promotions()->valid();
    }
}
