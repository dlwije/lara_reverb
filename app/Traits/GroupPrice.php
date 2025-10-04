<?php

namespace App\Traits;

use App\Models\Sma\People\PriceGroup;
use App\Models\Sma\Setting\Tax;

trait GroupPrice
{
    public function priceGroup()
    {
        return $this->priceGroups()->where('id', session('price_group_id'));
    }

    public function priceGroups()
    {
        return $this->belongsToMany(PriceGroup::class)->withPivot('price', 'taxes');
    }

    public function getPrice()
    {
        return $this->priceGroup?->first()?->pivot->price ?? $this->storeStock->first()?->price ?? $this->price;
    }

    public function getTaxes()
    {
        $taxes = json_decode($this->priceGroup?->first()?->pivot->taxes ?? '[]');

        return count($taxes) ? Tax::whereIn('id', $taxes)->get() : $this->taxes;
    }
}
