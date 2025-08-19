<?php

namespace App\Listeners;

use App\Events\PurchaseEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PurchaseEventListener
{
    /**
     * Create the event listener.
     */
//    public function __construct()
//    {
//        //
//    }

    /**
     * Handle the event.
     */
    public function handle(PurchaseEvent $event): void
    {
        // logger()->info('PurchaseEvent called.', ['purchase' => $event->purchase->toArray(), 'oldPurchase' => $event->oldPurchase?->toArray()]);

        if ($event->oldPurchase) {
            $this->setStock($event->oldPurchase, true, __('Resetting'));

            $event->oldPurchase->supplier->decreaseBalance($event->oldPurchase->grand_total, [
                'reference'   => $event->oldPurchase,
                'description' => __('Reset balance for {purchase}', ['purchase' => '<a class="link" href="' . route('purchases.index', ['id' => $event->oldPurchase->id], false) . '">' . (__('Purchase') . ' #' . $event->oldPurchase->id) . '</a>']),
            ]);

            $event->oldPurchase->store->account?->decreaseBalance($event->oldPurchase->grand_total, [
                'reference'   => $event->oldPurchase,
                'description' => __('Reset balance for {purchase}', ['purchase' => '<a class="link" href="' . route('purchases.index', ['id' => $event->oldPurchase->id], false) . '">' . (__('Purchase') . ' #' . $event->oldPurchase->id) . '</a>']),
            ]);
        }

        $this->setStock($event->purchase, false, __('Syncing'));

        $event->purchase->supplier?->increaseBalance($event->purchase->grand_total, [
            'reference'   => $event->purchase,
            'description' => __('Sync balance for {purchase}', ['purchase' => '<a class="link" href="' . route('purchases.index', ['id' => $event->purchase->id], false) . '">' . (__('Purchase') . ' #' . $event->purchase->id) . '</a>']),
        ]);

        $event->purchase->store->account?->increaseBalance($event->purchase->grand_total, [
            'reference'   => $event->purchase,
            'description' => __('Sync balance for {purchase}', ['purchase' => '<a class="link" href="' . route('purchases.index', ['id' => $event->purchase->id], false) . '">' . (__('Purchase') . ' #' . $event->purchase->id) . '</a>']),
        ]);
    }

    private function setStock($purchase, $reverse = false, $action = 'Syncing')
    {
        foreach ($purchase->items as $item) {
            if ($item->product->type == 'Standard') {
                $this->setProductStock($item->product, $item, $purchase, $reverse, $action);
            } elseif ($item->product == 'Combo') {
                foreach ($item->product->products as $product) {
                    $item->quantity = $item->quantity * $product->pivot->quantity;
                    $this->setProductStock($product, $item, $purchase, $reverse, $action);
                }
            }
        }
    }

    private function setProductStock($product, $item, $purchase, $reverse, $action)
    {
        if ($item->variations->count()) {
            foreach ($item->variations as $variation) {
                $variation->adjustStock($reverse ? 'decrease' : 'increase', $variation->pivot->base_quantity, [
                    'reference'   => $purchase,
                    'store_id'    => $purchase->store_id,
                    'description' => __('{a} {x} quantity for {id} item {i}', [
                        'a'  => $action,
                        'x'  => __('variation'),
                        'id' => '<a class="link" href="' . route('purchases.index', ['id' => $purchase->id], false) . '">' . __('purchase') . ' ' . $purchase->id . '</a>',
                        'i'  => $item->product->name . '), variation ' . $variation->id . ' (' . $variation->code . ')',
                    ]),
                ]);
            }
        }

        $product->adjustStock($reverse ? 'decrease' : 'increase', $item->base_quantity, [
            'reference'   => $purchase,
            'store_id'    => $purchase->store_id,
            'description' => __('{a} {x} quantity for {id} item {i}', [
                'a'  => $action,
                'x'  => __('product'),
                'id' => '<a class="link" href="' . route('purchases.index', ['id' => $purchase->id], false) . '">' . __('purchase') . ' ' . $purchase->id . '</a>',
                'i'  => $item->id . ' (' . $item->product->name . ')',
            ]),
        ]);
    }
}
