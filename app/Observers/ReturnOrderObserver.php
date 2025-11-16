<?php

namespace App\Observers;

use App\Events\ReturnOrderEvent;
use App\Models\Sma\Order\ReturnOrder;

class ReturnOrderObserver
{
    /**
     * Handle the ReturnOrder "deleting" event.
     */
    public function deleting(ReturnOrder $returnOrder): void
    {
        if (! $returnOrder->isForceDeleting()) {
            event(new ReturnOrderEvent(new ReturnOrder, $returnOrder));
        }
    }

    /**
     * Handle the ReturnOrder "restored" event.
     */
    public function restored(ReturnOrder $returnOrder): void
    {
        event(new ReturnOrderEvent($returnOrder));
    }
}
