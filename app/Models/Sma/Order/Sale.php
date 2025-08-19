<?php

namespace App\Models\Sma\Order;

use App\Models\Scopes\OfStore;
use App\Observers\SaleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use App\Models\Model;

#[ScopedBy(OfStore::class)]
#[ObservedBy(SaleObserver::class)]
class Sale extends Model
{
    //
}
