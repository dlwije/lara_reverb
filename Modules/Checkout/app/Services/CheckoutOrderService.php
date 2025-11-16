<?php

namespace Modules\Checkout\Services;

use App\Models\Sma\Order\Payment;
use App\Models\Sma\Pos\Order;
use App\Models\Sma\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Cart\Facades\Cart;

class CheckoutOrderService
{
    public function setOrderSessionData(string|null $token, string|array $data): array
    {
        if (! $token) {
            $token = $this->getOrderSessionToken();
        }

        $data = array_replace_recursive($this->getOrderSessionData($token), $data);

        $data = $this->cleanData($data);

        session([md5('checkout_address_info_' . $token) => $data]);

        return $data;
    }

    public function cleanData(array $data): array
    {
        foreach ($data as $key => $item) {
            if (! is_string($item)) {
                continue;
            }

            $data[$key] = $this->clean($item);
        }

        return $data;
    }

    public function clean(array|string|null $dirty): array|string|null
    {
        if (config('checkout.enable_less_secure_web', false)) {
            return $dirty;
        }

        if (! $dirty && $dirty !== null) {
            return $dirty;
        }

        if (! is_numeric($dirty)) {
            $dirty = (string) $dirty;
        }

        return $dirty;
    }
    public function getOrderSessionData(string|null $token = null) :array
    {
        if (! $token) {
            $token = $this->getOrderSessionToken();
        }

        $data = [];
        $sessionKey = md5('checkout_address_info_' . $token);
        if (session()->has($sessionKey)) {
            $data = session($sessionKey);
        }

        return $this->cleanData($data);
    }

    public function getOrderSessionToken(): string
    {
        if(request()->input('order_checkout_token')) {
            $token = request()->input('order_checkout_token');
            session(['order_checkout_token' => $token]);
        } else if (session()->has('order_checkout_token')) {
            $token = session('order_checkout_token');
        } else {
            $token = md5(Str::random(40));
            session(['order_checkout_token' => $token]);
        }

        return $token;
    }

    public function processOrder(string|array|null $orderIds, string|null $chargeId = null): bool|Collection|array|Model
    {
        $orderIds = (array)$orderIds;

        $orders = Order::query()->whereIn('id', $orderIds)->get();

        if ($orders->isEmpty()) {
            return false;
        }

        if ($chargeId) {
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
                && (
//                    ! empty(PaymentMethods::methods()) &&
                    ! $order->payment_id
                )
            ) {
                continue;
            }

//            event(new OrderPlacedEvent($order));

            $order->is_finished = true;

            $order->is_confirmed = true;

            $order->save();

            $this->decreaseProductQuantity($order);

//            if (EcommerceHelper::isOrderAutoConfirmedEnabled()) {
//                OrderHistory::query()->create([
//                    'action' => 'confirm_order',
//                    'description' => trans('plugins/ecommerce::order.order_was_verified_by'),
//                    'order_id' => $order->id,
//                    'user_id' => 0,
//                ]);
//            }
        }

        Cart::instance('cart')->destroy();
        session()->forget('applied_coupon_code');

        session(['order_id' => Arr::first($orderIds)]);

//        if (is_plugin_active('marketplace')) {
//            apply_filters(SEND_MAIL_AFTER_PROCESS_ORDER_MULTI_DATA, $orders);
//        } else {
//            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);
//            if ($mailer->templateEnabled('admin_new_order')) {
//                $this->setEmailVariables($orders->first());
//                $mailer->sendUsingTemplate('admin_new_order', get_admin_email()->toArray());
//            }
//
//            // Temporarily only send emails with the first order
//            $this->sendOrderConfirmationEmail($orders->first(), true);
//        }

        session(['order_id' => $orders->first()->id]);

//        foreach ($orders as $order) {
//            OrderHistory::query()->create([
//                'action' => 'create_order',
//                'description' => trans('plugins/ecommerce::order.new_order_from', [
//                    'order_id' => $order->code,
//                    'customer' => BaseHelper::clean($order->user->name ?: $order->address->name),
//                ]),
//                'order_id' => $order->id,
//            ]);
//        }

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

                    $this->productQuantityUpdate($orderProduct);
//                    event(new ProductQuantityUpdatedEvent($product));
                }
            }
        }

        return true;
    }

    public function productQuantityUpdate($productEvent)
    {
        $product = $productEvent->product;

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
        $stockStatus = 'out_of_stock';
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
                $stockStatus = 'in_stock';
            }
        }

        $parentProduct->quantity = $quantity;
        $parentProduct->with_storehouse_management = $withStorehouseManagement;
        $parentProduct->stock_status = $stockStatus;
        $parentProduct->allow_checkout_when_out_of_stock = $allowCheckoutWhenOutOfStock;

        $parentProduct->save();
    }
}
