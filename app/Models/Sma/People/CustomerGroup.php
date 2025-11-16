<?php

namespace App\Models\Sma\People;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerGroup extends Model
{
    use HasFactory;

    public function priceGroup()
    {
        return $this->belongsTo(PriceGroup::class);
    }
}
