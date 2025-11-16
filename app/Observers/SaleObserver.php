<?php

namespace App\Observers;

use App\Events\SaleEvent;
use App\Models\Sma\Order\Sale;

class SaleObserver
{
    /**
     * Handle the Sale "deleted" event.
     */
    public function deleting(Sale $sale): void
    {
        if (! $sale->isForceDeleting()) {
            $sale->user->deletePoints($sale->id);
            $sale->customer->deletePoints($sale->id);
            // $sale->loadMissing(['customer', 'items.product', 'items.variations']);
            event(new SaleEvent(new Sale, $sale));
        }
    }

    /**
     * Handle the Sale "restored" event.
     */
    public function restored(Sale $sale): void
    {
        event(new SaleEvent($sale));
    }
}
