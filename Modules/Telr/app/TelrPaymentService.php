<?php
namespace Modules\Telr\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TelrPaymentService extends WalletPaymentAbstract
{
    public function afterMakePayment(array $data): string|null
    {
        $request = request();
        $status = PaymentStatusEnum::COMPLETED;

        Log::info('afterMakePayment came from telrPaymentService');
        Log::info('afterMakePayment data: ' . print_r($data, true));
        //$chargeId = session('telr_payment_id');
        $chargeId = trim($request->get('OrderRef'));

        $orderIds = (array)Arr::get($data, 'order_id', []);

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'trans_freeze_id' => Arr::get($data, 'trans_freeze_id'),
            'wallet_applied_amount' => Arr::get($data, 'wallet_applied_amount'),
            'wallet_pay_id' => Arr::get($data, 'wallet_pay_id'),
            'charge_id' => $chargeId,
            'order_id' => $orderIds,
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => TELR_PAYMENT_METHOD_NAME,
            'status' => $status,
        ]);

        $gatewayAmount = (float) Arr::get($data, 'amount', 0);
        // Fetch the total of all orders
        $totalOrderAmount = Order::whereIn('id', $orderIds)->sum('amount');

        $customer_id = (int) Arr::get($data, 'customer_id', null);
        $trans_freeze_id = (int) Arr::get($data, 'trans_freeze_id',null);
        $wallet_applied_amount = (float) ($totalOrderAmount - $gatewayAmount) ?? 0;
        $wallet_pay_id = (int) Arr::get($data, 'wallet_pay_id', null);

        $customerRes = Customer::query()->where('id',$customer_id)->first();
        $wallet_trans = WalletTransaction::query()->where('id', $trans_freeze_id)->first();

        Log::info('trans_freeze_id on afterMakePaymentTelrPayService: '.$trans_freeze_id);
        Log::info('wallet_applied_amount on afterMakePaymentTelrPayService: '.$wallet_applied_amount);
        Log::info('wallet_pay_id on afterMakePaymentTelrPayService: '.$wallet_pay_id);

        Log::info('wallet_trans on afterMakePaymentTelrPayService: '.json_encode($wallet_trans));
        Log::info('customerRes on afterMakePaymentTelrPayService: '.json_encode($customerRes));

        // update wallet freeze amount
        if(!empty($trans_freeze_id)) {
            Log::info("Wallet Payment Hit TelrPaymentService and calling to deductReleaseWalletFreeze", $data);
            app(SplitPaymentPGatewayFirstService::class)->deductReleaseWalletFreeze($customerRes, $wallet_applied_amount, Arr::first($orderIds) ?? null, $wallet_trans);
            // Step 4: Update payment intent as completed
            Log::info('SplitPaymentPGatewayFirstService:Step4_status:');

            $payment = app(PaymentInterface::class)->findById($wallet_pay_id);

            if ($payment) {
                $payment->update([
                    'wallet_applied' => $wallet_applied_amount ?? 0,
                    'card_amount' => $data['amount'] ?? 0,
//                    'charge_id' => $data['charge_id'] ?? null,
//                    'payment_channel' => 'split_payment',
                    'status' => PaymentStatusEnum::COMPLETED,
                ]);
            }
        }

        session()->forget('telr_payment_id');

        return $chargeId;
    }

    public function paymentActionPaymentProcessed(array $data)
    {
        $orderIds = (array)$data['order_id'];

        if (! $orderIds) {
            return;
        }

        $orders = Order::query()->whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            $data['amount'] = $order->amount;
            $data['order_id'] = $order->id;
            $data['currency'] = strtoupper(cms_currency()->getDefaultCurrency()->title);

            PaymentHelper::storeLocalPayment($data);

            Cart::instance('cart')->destroy();
            $customerId = (int) $order->user_id;
            if($customerId > 0) { Cart::instance('cart')->storeQuietly($customerId); Cart::instance('cart')->restoreQuietly($customerId); }
        }

        OrderHelper::processOrder($orders->pluck('id')->all(), $data['charge_id']);
    }

    public function storeLocalPayment(array $args = [])
    {
        $data = [
            'user_id' => Auth::id() ?: 0,
            ...$args,
        ];

        $orderIds = (array) $data['order_id'];

        $payment = app(PaymentInterface::class)->getFirstBy([
            'charge_id' => $data['charge_id'],
            ['order_id', 'IN', $orderIds],
        ]);

        if ($payment) {
            return false;
        }

        $paymentChannel = Arr::get($data, 'payment_channel', PaymentMethodEnum::COD);

        return app(PaymentInterface::class)->create([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $data['charge_id'],
            'order_id' => Arr::first($orderIds),
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => $paymentChannel,
            'status' => Arr::get($data, 'status', PaymentStatusEnum::PENDING),
        ]);
    }

    public function processOrder(string|array|null $orderIds, string|null $chargeId = null): bool|Collection|array|Model
    {
        $orderIds = (array)$orderIds;

        $orders = Order::query()->whereIn('id', $orderIds)->get();

        if ($orders->isEmpty()) {
            return false;
        }

        if (is_plugin_active('payment') && $chargeId) {
            $payments = Payment::query()
                ->where('charge_id', $chargeId)
                ->whereIn('order_id', $orderIds)
                ->get();

            if ($payments->isNotEmpty()) {
                foreach ($orders as $order) {
                    $payment = $payments->firstWhere('order_id', $order->getKey());
                    if ($payment) {
                        $order->payment_id = $payment->getKey();
                        $order->save();
                    }
                }
            }
        }

        foreach ($orders as $order) {
            if (
                (float)$order->amount
                && (is_plugin_active('payment') && ! empty(PaymentMethods::methods()) && ! $order->payment_id)
            ) {
                continue;
            }

            event(new OrderPlacedEvent($order));

            $order->is_finished = true;

            if (EcommerceHelper::isOrderAutoConfirmedEnabled()) {
                $order->is_confirmed = true;
            }

            $order->save();

            $this->decreaseProductQuantity($order);

            if (EcommerceHelper::isOrderAutoConfirmedEnabled()) {
                OrderHistory::query()->create([
                    'action' => 'confirm_order',
                    'description' => trans('plugins/ecommerce::order.order_was_verified_by'),
                    'order_id' => $order->id,
                    'user_id' => 0,
                ]);
            }
        }

        Cart::instance('cart')->destroy();
        session()->forget('applied_coupon_code');

        session(['order_id' => Arr::first($orderIds)]);

        if (is_plugin_active('marketplace')) {
            apply_filters(SEND_MAIL_AFTER_PROCESS_ORDER_MULTI_DATA, $orders);
        } else {
            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);
            if ($mailer->templateEnabled('admin_new_order')) {
                $this->setEmailVariables($orders->first());
                $mailer->sendUsingTemplate('admin_new_order', get_admin_email()->toArray());
            }

            // Temporarily only send emails with the first order
            $this->sendOrderConfirmationEmail($orders->first(), true);
        }

        session(['order_id' => $orders->first()->id]);

        foreach ($orders as $order) {
            OrderHistory::query()->create([
                'action' => 'create_order',
                'description' => trans('plugins/ecommerce::order.new_order_from', [
                    'order_id' => $order->code,
                    'customer' => BaseHelper::clean($order->user->name ?: $order->address->name),
                ]),
                'order_id' => $order->id,
            ]);

            if (
                (
                    is_plugin_active('payment')
                    && $order->amount
                    && $order->payment
                    && $order->payment->status == PaymentStatusEnum::COMPLETED
                )
                || $order->amount == 0
            ) {
                $this->sendEmailForDigitalProducts($order);
            }
        }

        if (FlashSale::isEnabled()) {
            foreach ($orders as $order) {
                foreach ($order->products as $orderProduct) {
                    $product = $orderProduct->product->original_product;

                    $flashSale = $product->latestFlashSales()->first();
                    if (! $flashSale) {
                        continue;
                    }

                    $flashSale->products()->detach([$product->id]);
                    $flashSale->products()->attach([
                        $product->id => [
                            'price' => $flashSale->pivot->price,
                            'quantity' => (int)$flashSale->pivot->quantity,
                            'sold' => (int)$flashSale->pivot->sold + $orderProduct->qty,
                        ],
                    ]);
                }
            }
        }

        return $orders;
    }

    public function decreaseProductQuantity(Order $order): bool
    {
        foreach ($order->products as $orderProduct) {
            $product = Product::query()->find($orderProduct->product_id);

            if ($product) {
                if ($product->with_storehouse_management || $product->quantity >= $orderProduct->qty) {
                    $product->quantity = $product->quantity >= $orderProduct->qty ? $product->quantity - $orderProduct->qty : 0;
                    $product->save();

                    $this->ProductQuantityUpdatedEvent($product);
                }
            }
        }

        return true;
    }
    public function ProductQuantityUpdatedEvent($event)
    {
        $product = $event->product;

        if (! $product->is_variation) {
            return;
        }

        $parentProduct = $product->original_product;

        if (! $parentProduct || ! $parentProduct->id || $parentProduct->is_variation) {
            return;
        }

        $variations = $parentProduct->variations()->with('product')->get();

        $quantity = 0;
        $withStorehouseManagement = false;
        $stockStatus = StockStatusEnum::OUT_OF_STOCK;
        $allowCheckoutWhenOutOfStock = false;

        foreach ($variations as $variation) {
            $product = $variation->product;

            if (! $product || ! $product->is_variation) {
                continue;
            }

            if ($product->with_storehouse_management) {
                $quantity += $product->quantity;
                $withStorehouseManagement = true;
            }

            if ($product->allow_checkout_when_out_of_stock) {
                $allowCheckoutWhenOutOfStock = true;
            }

            if (! $product->isOutOfStock()) {
                $stockStatus = StockStatusEnum::IN_STOCK;
            }
        }

        $parentProduct->quantity = $quantity;
        $parentProduct->with_storehouse_management = $withStorehouseManagement;
        $parentProduct->stock_status = $stockStatus;
        $parentProduct->allow_checkout_when_out_of_stock = $allowCheckoutWhenOutOfStock;

        $parentProduct->save();
    }
}
