<?php

namespace App\Traits;

use App\Models\Sma\People\Address;

trait HasTaxes
{
    public function applicableTaxes(Address $address)
    {
        return $this->taxes;
    }
}
