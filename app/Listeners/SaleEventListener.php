<?php

namespace App\Listeners;

use App\Events\SaleEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SaleEventListener
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
    public function handle(SaleEvent $event): void
    {
        // logger()->info('SaleEvent called.', ['sale' => $event->sale->toArray(), 'oldSale' => $event->oldSale?->toArray()]);

        if ($event->oldSale) {
            $this->setStock($event->oldSale, true, __('Resetting'));

            $event->oldSale->customer->decreaseBalance($event->oldSale->grand_total, [
                'reference'   => $event->oldSale,
                'description' => __('Reset balance for {sale}', ['sale' => '<a class="link" href="' . route('sales.index', ['id' => $event->oldSale->id], false) . '">' . (__('Sale') . ' #' . $event->oldSale->id) . '</a>']),
            ]);

            $event->oldSale->store->account?->increaseBalance($event->oldSale->grand_total, [
                'reference'   => $event->oldSale,
                'description' => __('Reset balance for {sale}', ['sale' => '<a class="link" href="' . route('sales.index', ['id' => $event->oldSale->id], false) . '">' . (__('Sale') . ' #' . $event->oldSale->id) . '</a>']),
            ]);
        }

        $this->setStock($event->sale, false, __('Syncing'));

        $event->sale->customer?->increaseBalance($event->sale->grand_total, [
            'reference'   => $event->sale,
            'description' => __('Sync balance for {sale}', ['sale' => '<a class="link" href="' . route('sales.index', ['id' => $event->sale->id], false) . '">' . (__('Sale') . ' #' . $event->sale->id) . '</a>']),
        ]);

        $event->sale->store->account?->decreaseBalance($event->sale->grand_total, [
            'reference'   => $event->sale,
            'description' => __('Sync balance for {sale}', ['sale' => '<a class="link" href="' . route('sales.index', ['id' => $event->sale->id], false) . '">' . (__('Sale') . ' #' . $event->sale->id) . '</a>']),
        ]);

        if ($event->sale?->id) {
            $this->grandAwardPoints($event->sale);
        }
    }

    private function setStock($sale, $reverse = false, $action = 'Syncing')
    {
        foreach ($sale->items as $item) {
            if ($item->product->type == 'Standard') {
                $this->setProductStock($item->product, $item, $sale, $reverse, $action);
            } elseif ($item->product == 'Combo') {
                foreach ($item->product->products as $product) {
                    $item->quantity = $item->quantity * $product->pivot->quantity;
                    $this->setProductStock($product, $item, $sale, $reverse, $action);
                }
            }
        }
    }

    private function setProductStock($product, $item, $sale, $reverse, $action)
    {
        if ($item->variations->count()) {
            foreach ($item->variations as $variation) {
                $variation->adjustStock($reverse ? 'increase' : 'decrease', $variation->pivot->base_quantity, [
                    'reference'   => $sale,
                    'store_id'    => $sale->store_id,
                    'description' => __('{a} {x} quantity for {id} item {i}', [
                        'a'  => $action,
                        'x'  => __('variation'),
                        'id' => '<a class="link" href="' . route('sales.index', ['id' => $sale->id], false) . '">' . __('sale') . ' ' . $sale->id . '</a>',
                        'i'  => $item->product->name . '), variation ' . $variation->id . ' (' . $variation->code . ')',
                    ]),
                ]);
            }
        }

        $product->adjustStock($reverse ? 'increase' : 'decrease', $item->base_quantity, [
            'reference'   => $sale,
            'store_id'    => $sale->store_id,
            'description' => __('{a} {x} quantity for {id} item {i}', [
                'a'  => $action,
                'x'  => __('product'),
                'id' => '<a class="link" href="' . route('sales.index', ['id' => $sale->id], false) . '">' . __('sale') . ' ' . $sale->id . '</a>',
                'i'  => $item->id . ' (' . $item->product->name . ')',
            ]),
        ]);
    }

    private function grandAwardPoints($sale)
    {
        $sale->customer->grantPoints($sale->grand_total, [
            'sale_id' => $sale->id,
            'details' => __('Awarded points for {sale}', ['sale' => '<a class="link" href="' . route('sales.index', ['id' => $sale->id], false) . '">' . (__('Sale') . ' #' . $sale->id) . '</a>']),
        ]);

        $sale->user->grantPoints($sale->grand_total, [
            'sale_id' => $sale->id,
            'details' => __('Awarded points for {sale}', ['sale' => '<a class="link" href="' . route('sales.index', ['id' => $sale->id], false) . '">' . (__('Sale') . ' #' . $sale->id) . '</a>']),
        ]);
    }
}
