<?php

namespace Modules\Ecommerce\Services;

use AllowDynamicProperties;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Models\CartItem;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\Ecommerce\Models\ShopCoupon;
use Modules\Product\Models\Product;
#[AllowDynamicProperties] class CartHelper
{
    const cartId = '';
    const coupon = '';
    const available_shipping = '';
    const form = '';
    const shipping_methods = '';
    const shipping_method = '';
    public function __construct()
    {
        $this->coupon = session('coupon', []);
        $this->available_shipping = collect([]);
        $this->cartId = request()->input('cart_id');
        $this->form['coupon_code'] = session('coupon_code', null);
        $this->form['shop_shipping_method_id'] = session('shop_shipping_method_id', null);
        $this->shipping_methods = cache()->rememberForever('shipping_methods', fn() => ShippingMethod::all());
        $this->shipping_method = $this->shipping_methods->where('id', request()->input('shop_shipping_method_id'))->first();
        $this->available_shipping = $this->shipping_methods;

        $this->cart_items = CartItem::where('cart_id', $this->cartId)->with(['product', 'product.variations'])->get();
    }

    public function prepare()
    {
        $this->cart_items = collect($this->cart_items);
        if (request()->input('cart_id')) {
            $this->cartId = request()->input('cart_id');
        }

        $this->prepareItems();
    }

    public function prepareItems()
    {
//        $coupon = session('coupon', []);
//        $available_shipping = collect([]);
//        $cartId = request()->input('cart_id');
//        $form['coupon_code'] = session('coupon_code', null);
//        $form['shop_shipping_method_id'] = session('shop_shipping_method_id', null);
//        $shipping_methods = cache()->rememberForever('shipping_methods', fn() => ShippingMethod::all());
//        $shipping_method = $shipping_methods->where('id', request()->input('shop_shipping_method_id'))->first();
//        $available_shipping = $shipping_methods;

        $cart_items = CartItem::where('cart_id', self::cartId)->with(['product', 'product.variations'])->get();
        if (auth()->user() && self::cartId) {
            CartItem::ofUser()->update(['cart_id' => self::cartId]);
        }
        $cart_items = CartItem::select(['product_id', 'cart_id', 'qty', 'user_id', 'selected', 'oId'])
            ->with('product:id,code,name,slug,price,photo,tax_included', 'product.taxes', 'product.priceGroup')->ofCart($this->cartId)->get();
        foreach ($cart_items as $cart_item) {
            $selected = ['variations' => []];
            if ($cart_item->product->variations->isNotEmpty()) {
                if ($cart_item->selected['variations'] ?? []) {
                    foreach ($cart_item->selected['variations'] as $sv) {
                        $variation = $cart_item->product->variations->where('id', $sv['id'])->first();
                        $variation->available = $variation->quantity;
                        $variation->quantity = $sv['quantity'];
                        $variation->price = $sv['price'] ?? 0;
                        $selected['variations'][] = $variation;
                    }
                }
            }
            $cart_item->product->oId = $cart_item->oId;
        }

        $items = $cart_items->map(function ($cart_item) {
            $item = $cart_item->product->toArray();
            $item['taxes'] = $cart_item->product->taxes?->pluck('id');
            $item['selected'] = $cart_item->selected;
            $item['product_id'] = $cart_item->product_id;
            $item['quantity'] = $cart_item->quantity;
            $item['cart_id'] = $cart_item->cart_id;
            $item['variations'] = $cart_item->selected['variations'] ?? [];
            $item['cost'] = 0;

            return $item;
        })->all();

        $this->data = OrderCalculator::calculate(['items' => $items, 'coupon' => $this->coupon]);

        return ['data' => $this->data,'cart_items' => $cart_items, 'shipping_methods' => $this->shipping_methods, 'form' => $this->form];
    }

    public function applyCoupon($remove = false)
    {
        if ($remove) {
            $this->form['coupon_code'] = '';
            session()->forget('coupon');
            session()->forget('coupon_code');

            return true;
        } else {
            if ($this->form['coupon_code']) {
                $this->coupon = ShopCoupon::where('code', $this->form['coupon_code'])->first();
                if ($this->coupon && ($this->coupon->expiry_date ? now()->parse($this->coupon->expiry_date)?->isPast() : false)) {
                    $this->form['coupon_code'] = '';
                    return [
                        'status' => false,
                        'type' => 'error',
                        'message' => __('Coupon has expired.')
                    ];
//                    $this->dispatch('notify',
//                        type: 'error',
//                        content: __('Coupon has expired.')
//                    );
                } elseif ($this->coupon) {
                    session(['coupon' => $this->coupon]);
                    // cache()->forget('cart' . $this->cartId);
                    session(['coupon_code' => $this->form['coupon_code']]);
                    $this->prepareItems();
                    return [
                        'status' => true,
                        'type' => 'success',
                        'message' => __('Coupon has been applied.')
                    ];
//                    $this->dispatch('notify',
//                        type: 'success',
//                        content: __('Coupon has been applied.')
//                    );
                } else {
                    $this->form['coupon_code'] = '';
                    return [
                        'status' => false,
                        'type' => 'error',
                        'message' => __('Coupon not found, please try again with correct code.')
                    ];
//                    $this->dispatch('notify',
//                        type: 'error',
//                        content: __('Coupon not found, please try again with correct code.')
//                    );
                }
            } else {
                session()->forget('coupon');
                session()->forget('coupon_code');

                return [
                    'status' => false,
                    'type' => 'error',
                    'message' => __('Please provide coupon code.')
                ];
//                $this->dispatch('notify',
//                    type: 'error',
//                    content: __('Please provide coupon code.'),
//                );
            }
        }
    }

    public function add($product_id, $quantity = 1, $oId = null)
    {
        $this->product = Product::selectColumns()->find($product_id);
        if ($this->product->has_variants && $this->product->variants) {
            $this->variation = $this->product->variations->first();
        }
        $this->oId = $oId;
        $this->quantity = $quantity;

        return $this;
    }
}
