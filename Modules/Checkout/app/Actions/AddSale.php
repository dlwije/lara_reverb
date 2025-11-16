<?php

namespace Modules\Checkout\Actions;

use App\Actions\Sma\SaveSale;
use App\Http\Requests\Sma\Order\SaleRequest;
use App\Models\Sma\People\Address;
use Illuminate\Support\Facades\Validator;
use Modules\Cart\Models\CartItem;
use Modules\Ecommerce\Models\ShopCoupon;

class AddSale
{
    public static function fromCart($form, $cart_items, $shipping_methods)
    {
        $user = auth()->user();
        $coupon = session('coupon', null);
        $general = get_settings('general');

        $shipping_method = $shipping_methods->where('id', $form['shop_shipping_method_id'])->first();

        if ($form['address_id'] ?? null) {
            $address = Address::find($form['address_id']);
        } elseif (! empty($form['address']) && ($general['guest_checkout'] ?? null)) {
            $address = Address::create($form['address']);
        }

        session(['store_id' => $general['store_id']]);

        $saleForm = [
            'shop'                    => 1,
            'items'                   => [],
            'taxes'                   => [],
            'extra_attributes'        => null,
            'user_id'                 => $user?->id,
            'address_id'              => $address->id,
            'details'                 => $form['details'],
            'total_item'              => $cart_items->count(),
            'date'                    => now()->toDateString(),
            'shipping'                => $shipping_method->rate,
            'payment_method'          => $form['payment_method'],
            'order_number'            => request()->cookie('cart_id'),
            'shop_coupon_id'          => $coupon ? $coupon->id : null,
            'total_quantity'          => $cart_items->sum('quantity'),
            'shop_shipping_method_id' => $form['shop_shipping_method_id'],
            'customer_id'             => $user ? $user->customer_id : ($general['default_customer_id'] ?? 1),
        ];

        foreach ($cart_items as $cart_item) {
            $item = [
                'quantity'     => $cart_item->quantity,
                'product_id'   => $cart_item->product->id,
                'cost'         => $cart_item->product->cost,
                'price'        => $cart_item->product->price,
                'tax_included' => $cart_item->product->tax_included,
                'discount'     => $coupon ? $coupon->discount . '%' : null,
                'price'        => $cart_item->oId ? 0 : $cart_item->product->price,
                'promotions'   => $cart_item->product->validPromotions->pluck('id')->all(),
                'taxes'        => $cart_item->product->applicableTaxes($address)->pluck('id')->all(),

                'variations' => $cart_item->selected['variations'] ?? [],
            ];

            if ($cart_item->product->variations->isNotEmpty()) {
                $item['variations'] = $cart_item->selected['variations'];
                foreach ($item['variations'] as &$variation) {
                    $variation['price'] = $cart_item->oId ? 0 : $variation['price'];
                }
            }

            if ($cart_item->product->has_serials) {
                $item['serials'] = $cart_item->product->serials()->take($cart_item->quantity)->get()->pluck('number');
            }

            $saleForm['items'][] = $item;
        }

        $request = new SaleRequest;
        $rules = $request->rules();
        $validator = Validator::make($saleForm, $rules);

        $request->setValidator($validator);
        $validated = $request->validated();

        $sale = (new SaveSale)->execute($validated);
        $payment = $sale->customer->payments()->create([
            'method'      => null,
            'received'    => false,
            'register_id' => null,
            'sale_id'     => $sale->id,
            'payment_for' => 'Customer',
            'amount'      => $sale->grand_total,
            'customer_id' => $sale->customer_id,
            'date'        => now()->toDateString(),
        ]);
        // $sale->paymentRequests()->attach($payment, ['amount' => $sale->grand_total]);

        CartItem::where('cart_id', $sale->order_number)->delete();

        session()->forget('shipping_method');
        session()->forget('shop_shipping_method_id');

        if ($sale->coupon_id || session('coupon_code')) {
            if ($sale->coupon_id) {
                ShopCoupon::where('id', $sale->coupon_id)->increment('used');
            }
            session()->forget('coupon');
            session()->forget('coupon_code');
        }

//        $sale->customer->notify(new SaleNotification($sale));

        return $sale;
    }
}
