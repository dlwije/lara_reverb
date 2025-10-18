<?php

namespace Botble\Wallet\Services;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\ShippingCodStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Enums\ShippingStatusEnum;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\Discount as DiscountFacade;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\Ecommerce\Models\Shipment;
use Botble\Ecommerce\Services\HandleApplyCouponService;
use Botble\Ecommerce\Services\HandleApplyPromotionsService;
use Botble\Ecommerce\Services\HandleShippingFeeService;
use Botble\Media\Facades\RvMedia;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Services\Gateways\ApplePayPaymentService;
use Botble\Payment\Services\Gateways\BankTransferPaymentService;
use Botble\Payment\Services\Gateways\CodPaymentService;
use Botble\Payment\Supports\PaymentHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WalletCheckoutService
{
    function __construct(
        public HandleApplyPromotionsService $promotionService,
        public HandleShippingFeeService $shippingFeeService,
        public HandleApplyCouponService $applyCouponService
    ){ }

    public function processPostCheckoutOrder(
        array|EloquentCollection $products,
        Request $request,
        string $token,
        array $sessionCheckoutData,
        BaseHttpResponse $response
    )
    {
        Log::info('Step2: processPostCheckoutOrder in WalletCheckoutService');
        $groupedProducts = $this->cartGroupByStore($products);
        $currentUserId = 0;
        if (auth('customer')->check()) {
            $currentUserId = auth('customer')->id();
        } else if (auth('sanctum')->check()) {
            $currentUserId = auth('sanctum')->id();
        }

        if(request()->input('coupon_code')) {
            session()->put('applied_coupon_code', request()->input('coupon_code'));
            $couponCode = request()->input('coupon_code');
        } else {
            $couponCode = session('applied_coupon_code');
        }

        $orders = collect();

        $discounts = collect();

        $preOrders = collect();

        $mpSessionData = Arr::get($sessionCheckoutData, 'marketplace', []);

        if ($couponCode) {
            $this->processApplyCouponCode([], $request);
            $sessionCheckoutData = OrderHelper::getOrderSessionData($token);
            $couponCode = session('applied_coupon_code');
        } else {
            foreach ($mpSessionData as &$storeCheckoutData) {
                Arr::set($storeCheckoutData, 'coupon_discount_amount', 0);
                Arr::set($storeCheckoutData, 'applied_coupon_code', null);
                Arr::set($storeCheckoutData, 'is_free_shipping', false);
            }
            $sessionCheckoutData = OrderHelper::setOrderSessionData($token, ['marketplace' => $mpSessionData]);
        }

        $is_apiSession = (bool) ((isset($sessionCheckoutData['api_session'])) ? $sessionCheckoutData['api_session'] : false);
        $mpSessionData = Arr::get($sessionCheckoutData, 'marketplace', []);

        $orderIds = collect($mpSessionData ?: [])->pluck('created_order_id');

        if ($orderIds) {
            $preOrders = Order::query()->whereIn('id', $orderIds)->get();
        }

        $foundOrderIds = [];
        $promotionService = $this->promotionService;
        $shippingFeeService = $this->shippingFeeService;
        $applyCouponService = $this->applyCouponService;

        foreach ($groupedProducts as $storeId => $productsInStore) {
            $sessionStoreData = Arr::get($mpSessionData, $storeId, []);

            $order = $preOrders->firstWhere('store_id', $storeId);
            if ($order) {
                $foundOrderIds[] = $storeId;
            }

            $orders[$storeId] = $this->handleCheckoutOrderByStore(
                $sessionCheckoutData,
                $productsInStore,
                $token,
                $sessionStoreData,
                $request,
                $currentUserId,
                $order,
                $storeId,
                $discounts,
                $promotionService,
                $shippingFeeService,
                $applyCouponService
            );
        }

        // Remove orders not exists pre checkout
        if ($preOrders) {
            foreach ($preOrders as $order) {
                if (! in_array($order->store_id, $foundOrderIds)) {
                    $order->delete();
                    if ($order->address && $order->address->id) {
                        $order->address->delete();
                    }
                }
            }
        }

        if ($couponCode && $discounts->count()) {
            DiscountFacade::getFacadeRoot()->afterOrderPlaced($couponCode);
        }

        if (! is_plugin_active('payment') || ! $orders->pluck('amount')->sum()) {
            OrderHelper::processOrder($orders->pluck('id')->all());


            if($is_apiSession) {
                return $response
                    ->setData(['url' => route('public.checkout.success', $token)])
                    ->setMessage(__('Checkout successfully!'));
            } else {
                return $response
                    ->setNextUrl(route('public.checkout.success', $token))
                    ->setMessage(__('Checkout successfully!'));
            }
        }

        $totalAmount = format_price($orders->pluck('amount')->sum(), null, true);

        do_action('ecommerce_before_processing_payment', $products, $request, $token, $mpSessionData);

        $paymentData = $this->processPaymentMethodPostCheckout($request, (float)$totalAmount);
        Log::info('inside WalletCheckoutService PaymentData', $paymentData);
        $paymentDataMessage = Arr::get($paymentData, 'message');

        if ($checkoutUrl = Arr::get($paymentData, 'checkoutUrl')) {
            Log::info('inside WalletCheckoutService checkoutUrl');
            if($is_apiSession) {
                Log::info('inside if $is_apiSession');
                return $response
                    ->setData(['checkoutUrl' => $checkoutUrl])
                    ->withInput()
                    ->setMessage($paymentDataMessage);
            } else {
                Log::info('inside else $is_apiSession');
                return $response
                    ->setError($paymentData['error'])
                    ->setNextUrl($checkoutUrl)
                    ->setData(['checkoutUrl' => $checkoutUrl])
                    ->withInput()
                    ->setMessage($paymentDataMessage);
            }
        }
        $isWalletOnly = $paymentData['type'] === 'split_payment' && $paymentData['error'] === false;

        if (! $isWalletOnly && ($paymentData['error'] || empty($paymentData['charge_id']))) {
            Log::info('inside WalletCheckoutService paymentData[error] || invalid charge_id');
            if ($is_apiSession) {
                return $response
                    ->setError()
                    ->setData(['url' => PaymentHelper::getCancelURL($token)])
                    ->setMessage($paymentDataMessage ?: __('Checkout error!'))
                    ->setCode(422);
            } else {
                return $response
                    ->setError()
                    ->setNextUrl(PaymentHelper::getCancelURL($token))
                    ->withInput()
                    ->setMessage($paymentDataMessage ?: __('Checkout error!'));
            }
        }

        if($is_apiSession) {
            Log::info('inside global if $is_apiSession');
            //For Wallet
            return $response
                ->setData(['url' => PaymentHelper::getRedirectURL($token)])
                ->setMessage(__('Checkout successfully!'));
        } else {
            Log::info('inside global else $is_apiSession');
            return $response
                ->setNextUrl(PaymentHelper::getRedirectURL($token))
                ->setMessage(__('Checkout successfully!'));
        }
    }



    protected function cartGroupByStore(EloquentCollection $products): array|Collection
    {
        if ($products->isEmpty()) {
            return $products;
        }

        $products->loadMissing([
            'variationInfo',
            'variationInfo.configurableProduct',
            'variationInfo.configurableProduct.store',
        ]);

        $groupedProducts = collect();
        foreach ($products as $product) {
            $storeId = ($product->original_product && $product->original_product->store_id) ? $product->original_product->store_id : 0;
            if (! Arr::has($groupedProducts, $storeId)) {
                $groupedProducts[$storeId] = collect([
                    'store' => $product->original_product->store,
                    'products' => collect([$product]),
                ]);
            } else {
                $groupedProducts[$storeId]['products'][] = $product;
            }
        }

        return $groupedProducts;
    }

    public function processApplyCouponCode(array $result, Request $request): array
    {
        /**
         * @var EloquentCollection $products
         */
        $products = Cart::instance('cart')->products();
        $groupedProducts = $this->cartGroupByStore($products);
        $token = OrderHelper::getOrderSessionToken();

        if (! $token) {
            $token = OrderHelper::getOrderSessionToken();
        }

        $sessionCheckoutData = OrderHelper::getOrderSessionData($token);
        $sessionMarketplaceData = Arr::get($sessionCheckoutData, 'marketplace', []);
        $results = collect();
        $couponCode = $request->input('coupon_code');

        if (! $couponCode) {
            $couponCode = session('applied_coupon_code');
        }

        foreach ($groupedProducts as $storeId => $groupedProduct) {
            $productItems = $groupedProduct['products'];
            $cartItems = $productItems->pluck('cartItem');
            $rawTotal = Cart::instance('cart')->rawTotalByItems($cartItems);
            $countCart = Cart::instance('cart')->countByItems($cartItems);
            $sessionData = Arr::get($sessionMarketplaceData, $storeId, []);
            $prefix = "marketplace.$storeId.";
            $result = $this->applyCouponService
                ->execute(
                    $couponCode,
                    $sessionData,
                    compact('cartItems', 'rawTotal', 'countCart', 'productItems'),
                    $prefix
                );
            $results[$storeId] = $result;
        }

        $error = 0;
        $message = '';
        $successData = [
            'error' => true,
            'data' => [],
        ];

        $sessionCheckoutData = OrderHelper::getOrderSessionData($token);
        $sessionMarketplaceData = Arr::get($sessionCheckoutData, 'marketplace', []);

        $isGiftCard = false;
        $pendingDiscount = 0;
        foreach ($results as $storeId => $result) {
            $sessionData = Arr::get($sessionMarketplaceData, $storeId, []);

            if (Arr::get($result, 'error')) {
                $error += 1;
                $message = Arr::get($result, 'message');

                Arr::set($sessionData, 'coupon_discount_amount', 0);
                Arr::set($sessionData, 'applied_coupon_code', null);
                Arr::set($sessionData, 'is_free_shipping', false);
            } else {
                $discount = Arr::get($result, 'data.discount');
                if ((! $discount->store_id || $discount->store_id == $storeId) &&
                    (Arr::get($result, 'data.is_free_shipping', false) || Arr::get($result, 'data.discount_amount'))) {
                    $successData = $result;
                    if($isGiftCard) {
                        if($pendingDiscount <= 0) {
                            Arr::set($sessionData, 'coupon_discount_amount', 0);
                            Arr::set($sessionData, 'applied_coupon_code', null);
                            Arr::set($sessionData, 'is_free_shipping', false);
                        } else {
                            Arr::set($sessionData, 'applied_coupon_code', $couponCode);
                            Arr::set($sessionData, 'coupon_discount_amount', $pendingDiscount);
                        }

                    } else {
                        $pendingDiscount = $discount->value;
                        Arr::set($sessionData, 'applied_coupon_code', $couponCode);
                        Arr::set($sessionData, 'coupon_discount_amount', Arr::get($result, 'data.discount_amount'));
                    }
                } else {
                    Arr::set($sessionData, 'coupon_discount_amount', 0);
                    Arr::set($sessionData, 'applied_coupon_code', null);
                    Arr::set($sessionData, 'is_free_shipping', false);
                    $message = __('Coupon code is not valid or does not apply to the products');
                    $error += 1;
                }

                if($discount->type == 'gift-card') { $isGiftCard = true; }
            }


            Arr::set($sessionMarketplaceData, $storeId, $sessionData);

            /** check if gift voucher START **/
            if($isGiftCard) {
                if(isset($sessionMarketplaceData[$storeId])) {
                    $tdiscounted = Arr::get($sessionMarketplaceData[$storeId], 'coupon_discount_amount');
                    $pendingDiscount = ($pendingDiscount - $tdiscounted);

                    if($pendingDiscount <= 0) { $pendingDiscount = 0; }
                }
            }
            /** END **/
        }

        // return if all are error
        if ($results->count() == $error) {
            session()->forget('applied_coupon_code');

            return compact('error', 'message');
        }

        $couponDiscountAmount = collect($sessionMarketplaceData)->sum('coupon_discount_amount');

        OrderHelper::setOrderSessionData($token, [
            'marketplace' => $sessionMarketplaceData,
            'coupon_discount_amount' => $couponDiscountAmount,
        ]);

        return $successData;
    }

    public function handleCheckoutOrderByStore(
        array $sessionCheckoutData,
        array|Collection $products,
        string $token,
        array $sessionStoreData,
        Request $request,
        int|string|null $currentUserId,
        Order|null $order,
        int|string|null $storeId,
        array|Collection &$discounts,
        HandleApplyPromotionsService $promotionService,
        HandleShippingFeeService $shippingFeeService,
        HandleApplyCouponService $applyCouponService
    ) {
        Log::info('Step3: before handleCheckoutOrderByStore in WalletCheckoutService');
        $shippingAmount = 0;

        $cartItems = $products['products']->pluck('cartItem');
        $rawTotal = Cart::instance('cart')->rawTotalByItems($cartItems);
        $countCart = Cart::instance('cart')->countByItems($cartItems);
        $couponCode = Arr::get($sessionStoreData, 'applied_coupon_code');

        $isAvailableShipping = EcommerceHelper::isAvailableShipping($products['products']);
        $shippingMethodInput = $request->input("shipping_method.$storeId", ShippingMethodEnum::DEFAULT);

        $promotionDiscountAmount = $promotionService
            ->execute($token, compact('cartItems', 'rawTotal', 'countCart'), "marketplace.$storeId.");

        $couponDiscountAmount = 0;
        if ($couponCode) {
            $couponDiscountAmount = Arr::get($sessionStoreData, 'coupon_discount_amount', 0);
        }

        $paymentMethod = session('selected_payment_method');
        $orderAmount = max($rawTotal - $promotionDiscountAmount - $couponDiscountAmount, 0);

        $shippingData = [];
        $shippingMethod = [];
        if ($isAvailableShipping) {
            $shippingData = $this->getShippingData($sessionStoreData, $orderAmount, $products, $paymentMethod);

            $shippingMethodData = $shippingFeeService
                ->execute(
                    $shippingData,
                    $shippingMethodInput,
                    $request->input("shipping_option.$storeId")
                );

            $shippingMethod = Arr::first($shippingMethodData);
            if (! $shippingMethod) {
                throw ValidationException::withMessages([
                    'shipping_method.' . $storeId => trans(
                        'validation.exists',
                        ['attribute' => trans('plugins/ecommerce::shipping.shipping_method')]
                    ),
                ]);
            }

            $shippingAmount = Arr::get($shippingMethod, 'price', 0);

            if (get_shipping_setting('free_ship', $shippingMethodInput)) {
                $shippingAmount = 0;
            }
        }

        if ($couponCode) {
            $discount = $applyCouponService->getCouponData($couponCode, $sessionStoreData);
            if ($discount) {
                if (! $discount->store_id || $discount->store_id == $storeId) {
                    $discounts->push($discount);
                    $shippingAmount = Arr::get($sessionStoreData, 'is_free_shipping') ? 0 : $shippingAmount;
                }
            }
        }

        $orderAmount += (float)$shippingAmount;

        $data = array_merge($request->input(), [
            'amount' => $orderAmount,
            'currency' => $request->input('currency', strtoupper(get_application_currency()->title)),
            'user_id' => $currentUserId,
            'shipping_method' => $isAvailableShipping ? $shippingMethodInput : '',
            'shipping_option' => $isAvailableShipping ? $request->input("shipping_option.$storeId") : null,
            'shipping_amount' => (float)$shippingAmount,
            'tax_amount' => Cart::instance('cart')->rawTaxByItems($cartItems),
            'sub_total' => Cart::instance('cart')->rawSubTotalByItems($cartItems),
            'coupon_code' => $couponCode,
            'discount_amount' => $promotionDiscountAmount + $couponDiscountAmount,
            'status' => OrderStatusEnum::PENDING,
            'token' => $token,
        ]);

        if ($order) {
            $order->fill($data);
            $order->save();
        } else {
            $order = Order::query()->create($data);
        }

        if ($isAvailableShipping) {
            Shipment::query()->create([
                'order_id' => $order->id,
                'user_id' => 0,
                'weight' => $shippingData ? Arr::get($shippingData, 'weight') : 0,
                'cod_amount' => (is_plugin_active('payment') && $order->payment->id && $order->payment->status != PaymentStatusEnum::COMPLETED) ? $order->amount : 0,
                'cod_status' => ShippingCodStatusEnum::PENDING,
                'type' => $order->shipping_method,
                'status' => ShippingStatusEnum::PENDING,
                'price' => $order->shipping_amount,
                'store_id' => $order->store_id,
                'rate_id' => $shippingData ? Arr::get($shippingMethod, 'id', '') : '',
                'shipment_id' => $shippingData ? Arr::get($shippingMethod, 'shipment_id', '') : '',
                'shipping_company_name' => $shippingData ? Arr::get($shippingMethod, 'company_name') : '',
            ]);
        }

        if (
            EcommerceHelper::isDisplayTaxFieldsAtCheckoutPage() &&
            $request->boolean('with_tax_information')
        ) {
            $order->taxInformation()->create($request->input('tax_information'));
        }

        // Address Order in here
        $addressKeys = [
            'name',
            'phone',
            'email',
            'country',
            'state',
            'city',
            'address',
            'zip_code',
            'address_id',
            'billing_address_same_as_shipping_address',
            'billing_address',
        ];
        $addressData = Arr::only($sessionCheckoutData, $addressKeys);
        $sessionStoreData = array_merge($sessionStoreData, $addressData);
        $sessionStoreData['created_order_id'] = $order->id;
        OrderHelper::processAddressOrder($currentUserId, $sessionStoreData, $request);

        OrderHistory::query()->create([
            'action' => 'create_order_from_payment_page',
            'description' => __('Order is created from checkout page'),
            'order_id' => $order->id,
        ]);

        OrderHelper::processOrderProductData($products, $sessionStoreData);

        $request->merge([
            'order_id' => array_merge($request->input('order_id', []), [$order->id]),
        ]);

        return $order;
    }

    public function getShippingData(
        array $session,
        int|float $orderTotal,
        array|Collection $products,
        ?string $paymentMethod = null
    ): array {
        if ($products['store'] && $products['store']->id) {
            $keys = ['name', 'company', 'address', 'country', 'state', 'city', 'zip_code', 'email', 'phone'];
            $origin = Arr::only($products['store']->toArray(), $keys);
            if (! EcommerceHelper::isUsingInMultipleCountries()) {
                $origin['country'] = EcommerceHelper::getFirstCountryId();
            }
        } else {
            $origin = EcommerceHelper::getOriginAddress();
        }

        return EcommerceHelper::getShippingData($products['products'], $session, $origin, $orderTotal, $paymentMethod);
    }

    public function processPaymentMethodPostCheckout(Request $request, int|float $totalAmount): array
    {
        Log::info('Step4: processPaymentMethodPostCheckout in WalletCheckoutService');
        $paymentData = [
            'error' => false,
            'message' => false,
            'amount' => round((float)$totalAmount, 2),
            'currency' => $request->input('currency', strtoupper(cms_currency()->getDefaultCurrency()->title)),
            'type' => $request->input('payment_method'),
            'charge_id' => null,
        ];

        return $this->filterEcommerceProcessPayment($paymentData, $request);
    }

    public function filterEcommerceProcessPayment(array $data, Request $request)
    {
        Log::info('Step5: filterEcommerceProcessPayment in WalletCheckoutService');
        session()->put('selected_payment_method', $data['type']);

        $orderIds = (array)$request->input('order_id', []);

        $request->merge([
            'name' => trans('plugins/payment::payment.payment_description', [
                'order_id' => implode(', #', $orderIds),
                'site_url' => $request->getHost(),
            ]),
            'amount' => $data['amount'],
        ]);

        $paymentData = $this->paymentFilterPaymentData($data, $request);
        Log::info('Step6Response: paymentFilterPaymentData in WalletCheckoutService $paymentData: ',$paymentData);
        // above function $paymentData will return below data
//            $paymentData = [
//                'amount' => $this->convertOrderAmount((float)$orders->sum('amount')),
//                'amount_without_any_rule' => $this->convertOrderAmount((float)$orders->sum('sub_total')),
//                'shipping_amount' => $this->convertOrderAmount((float)$orders->sum('shipping_amount')),
//                'shipping_method' => $firstOrder->shipping_method->label(),
//                'tax_amount' => $this->convertOrderAmount((float)$orders->sum('tax_amount')),
//                'discount_code' => $couponCode,
//                'discount_amount' => $this->convertOrderAmount((float)$orders->sum('discount_amount')),
//                'currency' => strtoupper(get_application_currency()->title),
//                'order_id' => $orderIds,
//                'description' => trans('plugins/payment::payment.payment_description', [
//                    'order_id' => implode(', #', $orderIds),
//                    'site_url' => $request->getHost(),
//                ]),
//                'customer_id' => auth('customer')->check() ? auth('customer')->id() : null,
//                'customer_type' => Customer::class,
//                'return_url' => PaymentHelper::getCancelURL(),
//                'callback_url' => PaymentHelper::getRedirectURL(),
//                'products' => $products,
//                'orders' => $orders,
//                'address' => [
//                    'name' => $address->name ?: $firstOrder->user->name,
//                    'email' => $address->email ?: $firstOrder->user->email,
//                    'phone' => $address->phone ?: $firstOrder->user->phone,
//                    'country' => $address->country_name,
//                    'state' => $address->state_name,
//                    'city' => $address->city_name,
//                    'address' => $address->address,
//                    'zip_code' => $address->zip_code,
//                ],
//                'billing_address' => [
//                    'name' => $billingAddress->name ?: ($address->name ?: $firstOrder->user->name),
//                    'email' => $billingAddress->email ?: ($address->email ?: $firstOrder->user->email),
//                    'phone' => $billingAddress->phone ?: ($address->phone ?: $firstOrder->user->phone),
//                    'country' => $billingAddress->country_name ?: $address->country_name,
//                    'state' => $billingAddress->state_name ?: $address->state_name,
//                    'city' => $billingAddress->city_name ?: $address->city_name,
//                    'address' => $billingAddress->address ?: $address->address,
//                    'zip_code' => $billingAddress->zip_code ?: $address->zip_code,
//                ],
//                'checkout_token' => OrderHelper::getOrderSessionToken(),
//            ];

        switch ($request->input('payment_method')) {
            case PaymentMethodEnum::COD:

                $minimumOrderAmount = setting('payment_cod_minimum_amount', 0);

                if ($minimumOrderAmount > Cart::instance('cart')->rawSubTotal()) {
                    $data['error'] = true;
                    $data['message'] = __('Minimum order amount to use COD (Cash On Delivery) payment method is :amount, you need to buy more :more to place an order!', ['amount' => format_price($minimumOrderAmount), 'more' => format_price($minimumOrderAmount - Cart::instance('cart')->rawSubTotal())]);

                    break;
                }

                $data['charge_id'] = app(CodPaymentService::class)->execute($paymentData);

                break;

            case PaymentMethodEnum::BANK_TRANSFER:

                $data['charge_id'] = app(BankTransferPaymentService::class)->execute($paymentData);

                break;

            case PaymentMethodEnum::APPLE_PAY:
                $data['charge_id'] = app(ApplePayPaymentService::class)->execute($paymentData);

                break;
            default:
                $data = $this->paymentFilterAfterPostCheckout($data, $request);
                Log::info('Step7Response: paymentFilterAfterPostCheckout in WalletCheckoutService $data: ',$data);
                // above function $data param will receive the below data
//                    $data = [
//                        'error' => false,
//                        'message' => false,
//                        'amount' => round((float)$totalAmount, 2),
//                        'currency' => $request->input('currency', strtoupper(cms_currency()->getDefaultCurrency()->title)),
//                        'type' => $request->input('payment_method'),
//                        'charge_id' => null,
//                    ];

                break;
        }

        return $data;
    }

    public function paymentFilterPaymentData(array $data, Request $request)
    {
        Log::info('Step6: paymentFilterPaymentData in WalletCheckoutService');
        $orderIds = (array)$request->input('order_id', []);

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->with(['address', 'products'])
            ->get();

        $products = [];

        $couponCode  = '';
        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $products[] = [
                    'id' => $product->product_id,
                    'name' => $product->product_name,
                    'image' => RvMedia::getImageUrl($product->product_image),
                    'price' => $this->convertOrderAmount($product->price),
                    'price_per_order' => $this->convertOrderAmount(
                        ($product->price * $product->qty)
                        + ($order->tax_amount / $order->products->count())
                        - ($order->discount_amount / $order->products->count())
                    ),
                    'total_price' => $this->convertOrderAmount((float)$product->price*$product->qty),
                    'tax_amount' => $this->convertOrderAmount((float)$product->tax_amount),
                    'qty' => $product->qty,
                    'sku' => isset($product->options['sku']) ? $product->options['sku'] : ''
                ];
            }

            $code = trim($order->sum('coupon_code'));
            if(!empty($code) && !empty($couponCode)) { $couponCode  = $code; }
        }

        $firstOrder = $orders->sortByDesc('created_at')->first();

        $address = $firstOrder->address;
        $billingAddress = $firstOrder->billingAddress;
        if(!$address->name) {$address = $billingAddress;}
        return [
            'amount' => $this->convertOrderAmount((float)$orders->sum('amount')),
            'amount_without_any_rule' => $this->convertOrderAmount((float)$orders->sum('sub_total')),
            'shipping_amount' => $this->convertOrderAmount((float)$orders->sum('shipping_amount')),
            'shipping_method' => $firstOrder->shipping_method->label(),
            'tax_amount' => $this->convertOrderAmount((float)$orders->sum('tax_amount')),
            'discount_code' => $couponCode,
            'discount_amount' => $this->convertOrderAmount((float)$orders->sum('discount_amount')),
            'currency' => strtoupper(get_application_currency()->title),
            'order_id' => $orderIds,
            'description' => trans('plugins/payment::payment.payment_description', [
                'order_id' => implode(', #', $orderIds),
                'site_url' => $request->getHost(),
            ]),
            'customer_id' => auth('customer')->check() ? auth('customer')->id() : null,
            'customer_type' => Customer::class,
            'return_url' => PaymentHelper::getCancelURL(),
            'callback_url' => PaymentHelper::getRedirectURL(),
            'products' => $products,
            'orders' => $orders,
            'address' => [
                'name' => $address->name ?: $firstOrder->user->name,
                'email' => $address->email ?: $firstOrder->user->email,
                'phone' => $address->phone ?: $firstOrder->user->phone,
                'country' => $address->country_name,
                'state' => $address->state_name,
                'city' => $address->city_name,
                'address' => $address->address,
                'zip_code' => $address->zip_code,
            ],
            'billing_address' => [
                'name' => $billingAddress->name ?: ($address->name ?: $firstOrder->user->name),
                'email' => $billingAddress->email ?: ($address->email ?: $firstOrder->user->email),
                'phone' => $billingAddress->phone ?: ($address->phone ?: $firstOrder->user->phone),
                'country' => $billingAddress->country_name ?: $address->country_name,
                'state' => $billingAddress->state_name ?: $address->state_name,
                'city' => $billingAddress->city_name ?: $address->city_name,
                'address' => $billingAddress->address ?: $address->address,
                'zip_code' => $billingAddress->zip_code ?: $address->zip_code,
            ],
            'checkout_token' => OrderHelper::getOrderSessionToken(),
        ];
    }

    protected function convertOrderAmount(float $amount): float
    {
        $currentCurrency = get_application_currency();

        if ($currentCurrency->is_default) {
            return $amount;
        }

        return (float)format_price($amount * $currentCurrency->exchange_rate, $currentCurrency, true);
    }

    public function paymentFilterAfterPostCheckout(array $data, Request $request)
    {
        Log::info('Step7: paymentFilterAfterPostCheckout in WalletCheckoutService');
        if ($data['type'] !== WALLET_PAYMENT_METHOD_NAME) {
            return $data;
        }

        //$user = auth('customer')->user();

        $guards = ['sanctum', 'customer'];

        $user = null;

        foreach ($guards as $guard) {

            if (auth($guard)->check()) {
                $user = auth($guard)->user();
                break; // stop after finding the first logged-in user
            }
        }

        if (! $user) {
            $data['error'] = true;
            $data['message'] = __('You must be logged in to use Wallet.');
            return $data;
        }

        $currentCurrency = get_application_currency();
        $currencyModel = $currentCurrency->replicate();
        $currency = strtoupper($currentCurrency->title);

        $walletService = app(WalletCheckoutPaymentService::class);
        $supportedCurrencies = $walletService->supportedCurrencyCodes();

//        $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);
        $paymentData = $this->paymentFilterPaymentData($data, $request);
        $paymentData['customer_id']=$user->id; //added line
        $notSupportCurrency = false;

        if (! in_array($currency, $supportedCurrencies)) {
            $notSupportCurrency = true;

            if (! $currencyModel->where('title', 'USD')->exists()) {
                $data['error'] = true;
                $data['message'] = __(
                    ":name doesn't support :currency. List of currencies supported by :name: :currencies.",
                    [
                        'name' => 'Wallet',
                        'currency' => $currency,
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]
                );

                return $data;
            }
        }

        if ($notSupportCurrency) {
            $usdCurrency = $currencyModel->where('title', 'USD')->first();

            $paymentData['currency'] = 'USD';
            if ($currentCurrency->is_default) {
                $paymentData['amount'] = $paymentData['amount'] * $usdCurrency->exchange_rate;
            } else {
                $paymentData['amount'] = format_price(
                    $paymentData['amount'] / $currentCurrency->exchange_rate,
                    $currentCurrency,
                    true
                );
            }
        }

        //Check is user has Enough Money or not in wallet

        LOG::info('total money to deduct on walletCheckoutService',['tot_money'=>$paymentData['amount']]);
//        if ($user->wallet->total_available < $paymentData['amount']) {
//            return [
//                'error' => true,
//                'message' => __('Insufficient wallet balance.'),
//                'balance' => $user->wallet->balance
//            ];
//        }

        $splitPaymentService = app(SplitPaymentService::class);
        $result = $splitPaymentService->processSplitPayment($user, $paymentData, $request);
        Log::info('Split payment result on walletCheckoutService from SplitPaymentService:', $result);

        return $result;
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

//            PaymentHelper::storeLocalPayment($data);

            Cart::instance('cart')->destroy();
            $customerId = (int) $order->user_id;
            if($customerId > 0) { Cart::instance('cart')->storeQuietly($customerId); Cart::instance('cart')->restoreQuietly($customerId); }
        }

        OrderHelper::processOrder($orders->pluck('id')->all(), $data['charge_id']);
    }
}
