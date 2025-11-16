<?php

namespace App\Listeners;

use App\Events\ReturnOrderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReturnOrderEventListener
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
    public function handle(ReturnOrderEvent $event): void
    {
        // logger()->info('ReturnOrderEvent called.', ['return_order' => $event->return_order->toArray(), 'oldReturnOrder' => $event->oldReturnOrder?->toArray()]);

        if ($event->oldReturnOrder) {
            $this->setStock($event->oldReturnOrder, $event->oldReturnOrder->type == 'Sale', __('Resetting'));

            if ($event->oldReturnOrder->type == 'Sale') {
                $event->oldReturnOrder->customer?->increaseBalance($event->oldReturnOrder->grand_total, [
                    'reference'   => $event->oldReturnOrder,
                    'description' => __('Reset balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->oldReturnOrder->id], false) . '">' . (__('Return Order') . ' #' . $event->oldReturnOrder->id) . '</a>']),
                ]);
                $event->oldReturnOrder->store->account?->decreaseBalance($event->oldReturnOrder->grand_total, [
                    'reference'   => $event->oldReturnOrder,
                    'description' => __('Reset balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->oldReturnOrder->id], false) . '">' . (__('Return Order') . ' #' . $event->oldReturnOrder->id) . '</a>']),
                ]);
            } elseif ($event->oldReturnOrder->type == 'Purchase') {
                $event->oldReturnOrder->supplier?->increaseBalance($event->oldReturnOrder->grand_total, [
                    'reference'   => $event->oldReturnOrder,
                    'description' => __('Reset balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->oldReturnOrder->id], false) . '">' . (__('Return Order') . ' #' . $event->oldReturnOrder->id) . '</a>']),
                ]);
                $event->oldReturnOrder->store->account?->increaseBalance($event->oldReturnOrder->grand_total, [
                    'reference'   => $event->oldReturnOrder,
                    'description' => __('Reset balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->oldReturnOrder->id], false) . '">' . (__('Return Order') . ' #' . $event->oldReturnOrder->id) . '</a>']),
                ]);
            }
        }

        $this->setStock($event->return_order, $event->return_order->type == 'Purchase', __('Syncing'));

        if ($event->return_order->type == 'Sale') {
            $event->return_order->customer?->decreaseBalance($event->return_order->grand_total, [
                'reference'   => $event->return_order,
                'description' => __('Sync balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->return_order->id], false) . '">' . (__('Return Order') . ' #' . $event->return_order->id) . '</a>']),
            ]);
            $event->return_order->store->account?->increaseBalance($event->return_order->grand_total, [
                'reference'   => $event->return_order,
                'description' => __('Sync balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->return_order->id], false) . '">' . (__('Return Order') . ' #' . $event->return_order->id) . '</a>']),
            ]);
        } elseif ($event->return_order->type == 'Purchase') {
            $event->return_order->supplier?->decreaseBalance($event->return_order->grand_total, [
                'reference'   => $event->return_order,
                'description' => __('Sync balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->return_order->id], false) . '">' . (__('Return Order') . ' #' . $event->return_order->id) . '</a>']),
            ]);
            $event->return_order->store->account?->decreaseBalance($event->return_order->grand_total, [
                'reference'   => $event->return_order,
                'description' => __('Sync balance for {return_order}', ['sale' => '<a class="link" href="' . route('return_orders.index', ['id' => $event->return_order->id], false) . '">' . (__('Return Order') . ' #' . $event->return_order->id) . '</a>']),
            ]);
        }
    }

    private function setStock($return_order, $reverse = false, $action = 'Syncing')
    {
        foreach ($return_order->items as $item) {
            if ($item->product->type == 'Standard') {
                $this->setProductStock($item->product, $item, $return_order, $reverse, $action);
            } elseif ($item->product == 'Combo') {
                foreach ($item->product->products as $product) {
                    $item->quantity = $item->quantity * $product->pivot->quantity;
                    $this->setProductStock($product, $item, $return_order, $reverse, $action);
                }
            }
        }
    }

    private function setProductStock($product, $item, $return_order, $reverse, $action)
    {
        if ($item->variations->count()) {
            foreach ($item->variations as $variation) {
                $variation->adjustStock($reverse ? 'decrease' : 'increase', $variation->pivot->base_quantity, [
                    'reference'   => $return_order,
                    'store_id'    => $return_order->store_id,
                    'description' => __('{a} {x} quantity for {id} item {i}', [
                        'a'  => $action,
                        'x'  => __('variation'),
                        'id' => '<a class="link" href="' . route('return_orders.index', ['id' => $return_order->id], false) . '">' . __('Return Order') . ' ' . $return_order->id . '</a>',
                        'i'  => $item->product->name . '), variation ' . $variation->id . ' (' . $variation->code . ')',
                    ]),
                ]);
            }
        }

        $product->adjustStock($reverse ? 'decrease' : 'increase', $item->base_quantity, [
            'reference'   => $return_order,
            'store_id'    => $return_order->store_id,
            'description' => __('{a} {x} quantity for {id} item {i}', [
                'a'  => $action,
                'x'  => __('product'),
                'id' => '<a class="link" href="' . route('return_orders.index', ['id' => $return_order->id], false) . '">' . __('Return Order') . ' ' . $return_order->id . '</a>',
                'i'  => $item->id . ' (' . $item->product->name . ')',
            ]),
        ]);
    }
}
