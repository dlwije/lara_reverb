<?php

namespace App\Observers;

use App\Models\Sma\Order\GiftCard;
use App\Models\Sma\Order\Payment;
use App\Models\Sma\Order\Purchase;
use App\Models\Sma\Order\Sale;

class PaymentObserver
{
    /**
     * Handle the Payment "saved" event.
     */
    public function saved(Payment $payment): void
    {
        if ($payment->method == 'Gift Card' && ($payment->method_data['gift_card_no'] ?? null)) {
            $gift_card = GiftCard::where('number', $payment->method_data['gift_card_no'])->first();
            $gift_card->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Gift card used to make payment'),
            ]);
        }

        if ($payment->payment_for == 'Customer') {
            $payment->customer->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Sync balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            $payment->store->account?->increaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Sync balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);

            // Sync payment with sales
            if ($payment->sale_id) {
                $sale = Sale::withoutGlobalScopes()->find($payment->sale_id);
                $sale->payments()->attach($payment, ['amount' => $payment->amount]);
                $sale->updateQuietly([
                    'paid' => $sale->paid + $payment->amount,
                    // 'paid' => $sale->payments()->get()->sum(fn ($payment) => $payment->pivot->amount),
                ]);
            } else {
                $sales = $payment->customer->sales()->where('paid', '<', 'grand_total')->oldest()->get();
                $balance = $payment->amount;
                foreach ($sales as $sale) {
                    if ($balance <= 0) {
                        break;
                    } elseif ($sale->grand_total - $sale->paid <= $balance) {
                        $sale->payments()->attach($payment, ['amount' => $sale->grand_total - $sale->paid]);
                        // $sale->updateQuietly(['paid' => $sale->grand_total - $sale->paid]);
                        $balance -= $sale->grand_total - $sale->paid;
                    } else {
                        $sale->payments()->attach($payment, ['amount' => $balance]);
                        // $sale->updateQuietly(['paid' => $sale->paid + $balance]);
                        $balance = 0;
                    }
                    $sale->updateQuietly([
                        'paid' => $sale->payments()->get()->sum(fn ($payment) => $payment->pivot->amount),
                    ]);
                }
            }
        } elseif ($payment->payment_for == 'Supplier') {
            $payment->supplier->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Sync balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            $payment->store->account?->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Sync balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            // Sync payment with purchases
            if ($payment->purchase_id) {
                $purchase = Purchase::withoutGlobalScopes()->find($payment->purchase_id);
                $purchase->payments()->attach($payment, ['amount' => $payment->amount]);
                $purchase->updateQuietly([
                    'paid' => $purchase->payments()->get()->sum(fn ($payment) => $payment->pivot->amount),
                ]);
            } else {
                $purchases = $payment->supplier->purchases()->where('paid', '<', 'grand_total')->oldest()->get();
                $balance = $payment->amount;
                foreach ($purchases as $purchase) {
                    if ($balance <= 0) {
                        break;
                    } elseif ($purchase->grand_total - $purchase->paid <= $balance) {
                        $purchase->payments()->attach($payment, ['amount' => $purchase->grand_total - $purchase->paid]);
                        // $purchase->updateQuietly(['paid' => $purchase->grand_total - $purchase->paid]);
                        $balance -= $purchase->grand_total - $purchase->paid;
                    } else {
                        $purchase->payments()->attach($payment, ['amount' => $balance]);
                        // $purchase->updateQuietly(['paid' => $purchase->paid + $balance]);
                        $balance = 0;
                    }
                    $purchase->updateQuietly([
                        'paid' => $purchase->payments()->get()->sum(fn ($payment) => $payment->pivot->amount),
                    ]);
                }
            }
        }
    }

    /**
     * Handle the Payment "updating" event.
     */
    public function updating(Payment $payment): void
    {
        if ($payment->isDirty(['amount', 'method']) && $payment->method == 'Gift Card' && ($payment->method_data['gift_card_no'] ?? null)) {
            $gift_card = GiftCard::where('number', $payment->method_data['gift_card_no'])->first();
            $gift_card->increaseBalance($payment->getOriginal('amount'), [
                'reference'   => $payment,
                'description' => __('Editing payment'),
            ]);
        }

        if ($payment->getOriginal('payment_for') == 'Customer') {
            foreach ($payment->sales as $sale) {
                $sale->payments()->detach($payment);
                $sale->updateQuietly(['paid' => $sale->paid - $sale->pivot->amount]);
            }
            $payment->customer->increaseBalance($payment->getOriginal('amount'), [
                'reference'   => $payment,
                'description' => __('Reset balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            $payment->store->account?->decreaseBalance($payment->getOriginal('amount'), [
                'reference'   => $payment,
                'description' => __('Reset balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
        } elseif ($payment->getOriginal('payment_for') == 'Supplier') {
            foreach ($payment->purchases as $purchase) {
                $purchase->payments()->detach($payment);
                $purchase->updateQuietly(['paid' => $purchase->paid - $purchase->pivot->amount]);
            }
            $payment->supplier->increaseBalance($payment->getOriginal('amount'), [
                'reference'   => $payment,
                'description' => __('Reset balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            $payment->store->account?->increaseBalance($payment->getOriginal('amount'), [
                'reference'   => $payment,
                'description' => __('Reset balance for {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        if ($payment->method == 'Gift Card' && ($payment->method_data['gift_card_no'] ?? null)) {
            $gift_card = GiftCard::where('number', $payment->method_data['gift_card_no'])->first();
            $gift_card->increaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Payment deleted!'),
            ]);
        }

        if (! $payment->isForceDeleting()) {
            if ($payment->payment_for == 'Customer') {
                foreach ($payment->sales as $sale) {
                    $sale->payments()->detach($payment);
                    $sale->updateQuietly(['paid' => $sale->paid - $sale->pivot->amount]);
                }
                $payment->customer->increaseBalance($payment->amount, [
                    'reference'   => $payment,
                    'description' => __('Reset balance for deleting {payment}', ['payment' => '<a  class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
                ]);
                $payment->store->account?->decreaseBalance($payment->amount, [
                    'reference'   => $payment,
                    'description' => __('Reset balance for deleting {payment}', ['payment' => '<a  class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
                ]);
            } elseif ($payment->payment_for == 'Supplier') {
                $payment->supplier->increaseBalance($payment->amount, [
                    'reference'   => $payment,
                    'description' => __('Reset balance for deleting {payment}', ['payment' => '<a  class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
                ]);
                $payment->store->account?->increaseBalance($payment->amount, [
                    'reference'   => $payment,
                    'description' => __('Reset balance for deleting {payment}', ['payment' => '<a  class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
                ]);
            }
        }
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        if ($payment->method == 'Gift Card' && ($payment->method_data['gift_card_no'] ?? null)) {
            $gift_card = GiftCard::where('number', $payment->method_data['gift_card_no'])->first();
            $gift_card->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Payment restored!'),
            ]);
        }

        if ($payment->payment_for == 'Customer') {
            $payment->customer->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Reset balance for restoring {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            $payment->store->account?->increaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Reset balance for restoring {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            // Sync payment with sales
            $sales = $payment->customer->sales()->where('paid', '<', 'grand_total')->oldest()->get();
            $balance = $payment->amount;
            foreach ($sales as $sale) {
                if ($balance <= 0) {
                    break;
                } elseif ($sale->grand_total - $sale->paid <= $balance) {
                    $sale->payments()->attach($payment, ['amount' => $sale->grand_total - $sale->paid]);
                    // $sale->updateQuietly(['paid' => $sale->grand_total - $sale->paid]);
                    $balance -= $sale->grand_total - $sale->paid;
                } else {
                    $sale->payments()->attach($payment, ['amount' => $balance]);
                    // $sale->updateQuietly(['paid' => $sale->paid + $balance]);
                    $balance = 0;
                }
                $sale->updateQuietly([
                    'paid' => $sale->payments()->get()->sum(fn ($payment) => $payment->pivot->amount),
                ]);
            }
        } elseif ($payment->payment_for == 'Supplier') {
            $payment->supplier->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Reset balance for restoring {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
            $payment->store->account?->decreaseBalance($payment->amount, [
                'reference'   => $payment,
                'description' => __('Reset balance for restoring {payment}', ['payment' => '<a class="link" href="' . route('payments.index', ['id' => $payment->id], false) . '">' . (__('Payment') . ' #' . $payment->id) . '</a>']),
            ]);
        }
    }
}
