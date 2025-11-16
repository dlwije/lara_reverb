<?php

namespace Modules\Checkout\Http\Controllers;

use AllowDynamicProperties;
use App\Actions\Sma\FreeItem;
use App\Http\Controllers\Controller;
use App\Models\Sma\Pos\Order;
use App\Models\Sma\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Modules\Cart\Facades\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Checkout\Actions\AddSale;
use Modules\Checkout\Http\Requests\CheckoutRequest;
use Modules\Checkout\Services\CheckoutOrderService;
use Modules\Ecommerce\Services\CartHelper;
use Modules\Wallet\Services\KYCService;
use Modules\Wallet\Services\WalletCheckoutService;
use Modules\Wallet\Services\WalletService;

#[AllowDynamicProperties]
class CheckoutController extends Controller
{
    public function __construct(
        public WalletService $walletService,
        public KYCService $kycService,
        public CheckoutOrderService $checkoutOrderService,
        public CartHelper $cartHelper,
    ) {
        // For session based binding
        $this->cart = app('cart');
    }

    /**
     * Preview wallet deduction before actual purchase
     */
    public function previewWalletDeduction(Request $request)
    {
        try {
            $user = auth()->user();
            $amount = $request->input('amount', 0);

            if($amount <= 0) return self::error('Invalid amount',400);

            // Check KYC requirements
            $this->kycService->blockIfKycRequired($user, $amount);

            $preview = $this->walletService->previewDeduction($user, $amount);

            return self::success($preview);

        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(),500);
        }
    }

    /**
     * Process payment with wallet deduction
     */
    public function processWalletPayment(Request $request)
    {
        try {
            $user = auth()->user();
            $amount = $request->input('amount', 0);
            $orderId = $request->input('order_id');

            if($amount <= 0) return self::error('Invalid amount',400);

            // Check KYC requirements
            $this->kycService->blockIfKycRequired($user, $amount);

            $result = $this->walletService->deductFromWallet($user, $amount, [
                'type' => 'purchase',
                'ref_type' => 'order',
                'ref_id' => $orderId,
                'description' => $request->get('description','Purchase')
            ]);

            return self::success($result, 'Payment processed successfully');
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(),500);
        }
    }

    public function getCheckoutPage(Request $request)
    {

        $settings = get_settings('general');
        $guest_checkout = $settings['guest_checkout'];
        if (!$guest_checkout && my_unpaid_shop_orders() >= $settings['max_unpaid_orders']) {
            return Inertia::render('e-commerce/public/checkout/page', [
                'errors' => [
                    'message' => __('You have reached maximum unpaid orders limit. Please settle your unpaid orders first by visiting orders menu.')
                ]
            ])->with('error', __('You have reached maximum unpaid orders limit. Please settle your unpaid orders first by visiting orders menu.'));
        }

        $prepareItems = $this->cartHelper->prepareItems();

        if ($request->payment_method && ($request->payment_method == 'Balance' || $request->payment_method == 'Cash on Delivery')) {
            $user = auth()->user();
            if (!$user) {
                return Inertia::render('e-commerce/public/checkout/page', [
                    'errors' => [
                        'payment_method' => __('Please login to use this payment method.')
                    ]
                ])->with('error', __('Please login to use this payment method.'));
            }

            if ($request->payment_method == 'Balance') {
                $balance = $user->customer?->balance ?? 0;
                if ($balance < $prepareItems['data']['grand_total']) {
                    return Inertia::render('e-commerce/public/checkout/page', [
                        'errors' => [
                            'payment_method' => __('You do not have balance to make this payment.')
                        ]
                    ])->with('error', __('You do not have balance to make this payment.'));
                }
            }
        }

        if ($freeItems = $prepareItems['cart_items']->whereNotNull('oId')->where('oId', '!=', '')) {
            foreach ($freeItems as $freeItem) {
                $mainItem = $prepareItems['cart_items']->where('product_id', $freeItem->oId)->first();
                $valid = FreeItem::check($mainItem, $freeItem);

                if (!$valid) {
                    CartItem::where('product_id', $freeItem->product_id)->where('oId', $freeItem->oId)->delete();

                    return Inertia::render('e-commerce/public/checkout/page', [
                        'errors' => [
                            'message' => __('Promotion has expired or changed for free item named :item and removed from cart.', ['item' => $freeItem->item->name])
                        ]
                    ])->with('error', __('Promotion has expired or changed for free item named :item and removed from cart.', ['item' => $freeItem->item->name]));
                }
            }
        }

        $sale = AddSale::fromCart($prepareItems['form'], $prepareItems['cart_items'], $prepareItems['shipping_methods']);

        if ($sale->payment_method == 'PayPal') {
            return Inertia::location(route('payment.paypal', ['sale_id' => $sale->id]));
        } else {
            // For other payment methods, redirect to payment page
            if ($payment = $sale->directPendingPayments()->first()) {
                if (auth()->guest()) {
                    $url = URL::signedRoute('shop.payment.guest', [
                        'type'    => 'pay',
                        'id'      => $payment->id,
                        'hash'    => $payment->hash,
                        'gateway' => $sale->payment_method,
                    ]);
                    return Inertia::location($url);
                }

                return Inertia::location(route('shop.payment', [
                    'type'    => 'pay',
                    'id'      => $payment->id,
                    'gateway' => $sale->payment_method,
                ]));
            }

            // If no direct payment, redirect to order confirmation
            if (auth()->guest()) {
                $url = URL::signedRoute('shop.order.guest', ['id' => $sale->id, 'hash' => $sale->hash]);
                return Inertia::location($url);
            }

            return Inertia::location(route('shop.order.confirmation', ['id' => $sale->id]));
        }
        // First get the checkout data from session if there were

        // If the provided token is not equal to the current session token
//        if($token !== session('order_checkout_token')){
//
//            $order = Order::query()->where(['token' => $token, 'is_finished' => false])->first();

//            if (! $order) {
                // If there is no order on that token will redirect to the home page
//            }
//        }

//        $sessionCheckoutData = $this->checkoutOrderService->getOrderSessionData($token);

        // Here we can check cart has products or not
        // if not redirect to the cart page
        // if there is products, then check the Out of Stock status and redirect with products message which doesn't have qty

//        $sessionCheckoutData = $this->processOrderData($token, $sessionCheckoutData, $request);
//
//        Cart::instance('cart')->refresh();
//
//        $products = Cart::instance('cart')->products();
//
//        if (! $products->count()) {
//            return $this
//                ->httpResponse()
//                ->setNextUrl(get_frontend_url('cart'));
//        }

        return Inertia::render('e-commerce/public/checkout/page',[]);
    }

    public function processOrderData(string $token, array $sessionData, Request $request, bool $finished = false)
    {
        if (! isset($sessionData['created_order'])) {
            $currentUserId = 0;
            if (auth('customer')->check()) {
                $currentUserId = auth('customer')->id();
            }

            $request->merge([
                'amount' => Cart::instance('cart')->rawTotal(),
                'user_id' => $currentUserId,
                'shipping_method' => $request->input('shipping_method', 'default'),
                'shipping_option' => $request->input('shipping_option'),
                'shipping_amount' => 0,
                'tax_amount' => Cart::instance('cart')->rawTax(),
                'sub_total' => Cart::instance('cart')->rawSubTotal(),
                'coupon_code' => session('applied_coupon_code'),
                'discount_amount' => 0,
                'status' => 'pending',
                'is_finished' => false,
                'token' => $token,
            ]);

            $order = Order::query()->where(compact('token'))->first();

            $order = $this->createOrderFromData($request->input(), $order);

            $sessionData['created_order'] = true;
            $sessionData['created_order_id'] = $order->getKey();

        }

        if (! isset($sessionData['created_order_product'])) {
            $weight = Cart::instance('cart')->weight();

            Order::query()->where(['order_id' => $sessionData['created_order_id']])->delete();

            foreach (Cart::instance('cart')->content() as $cartItem) {
                $product = Product::query()->find($cartItem->id);

                if (! $product) {
                    continue;
                }

                $data = [
                    'order_id' => $sessionData['created_order_id'],
                    'product_id' => $cartItem->id,
                    'product_name' => $cartItem->name,
                    'product_image' => $product->original_product->image,
                    'qty' => $cartItem->qty,
                    'weight' => $weight,
                    'price' => $cartItem->price,
                    'tax_amount' => $cartItem->tax,
                    'options' => $cartItem->options,
                    'product_type' => $product?->product_type,
                ];

                if (isset($cartItem->options['options'])) {
                    $data['product_options'] = $cartItem->options['options'];
                }

                Order::query()->create($data);
            }

            $sessionData['created_order_product'] = Cart::instance('cart')->getLastUpdatedAt();
        }

        $this->checkoutOrderService->setOrderSessionData($token, $sessionData);

        return $sessionData;
    }

    protected function createOrderFromData(array $data, ?Order $order): Order|null|false
    {
        $data['is_finished'] = false;

        if ($order) {
            $order->fill($data);
            $order->save();
        } else {
//            protected $fillable = [
//                'status',
//                'user_id',
//                'amount',
//                'tax_amount',
//                'shipping_method',
//                'shipping_option',
//                'shipping_amount',
//                'description',
//                'coupon_code',
//                'discount_amount',
//                'sub_total',
//                'is_confirmed',
//                'discount_description',
//                'is_finished',
//                'token',
//                'completed_at',
//                'proof_file',
//            ];
            $order = Order::query()->create($data);
        }
        return $order;
    }
    /**
     * Split payment (wallet + card)
     */
    public function processSplitPayment(CheckoutRequest $request)
    {
        // The Request

        try {
            // get the order data based on the Token
            $token = $request->input('token');

            $sessionData = $this->checkoutOrderService->getOrderSessionData($token);
            $products = Cart::instance('cart')->products();

            $user = auth()->user();
            $totalAmount = $request->input('total_amount', 0);
            $walletAmount = $request->input('wallet_amount', 0);
            $cardAmount = $request->input('card_amount', 0);
            $orderId = $request->input('order_id');

            $paymentMethod = $request->input('payment_method', 'default');

            if($paymentMethod == WALLET_PAYMENT_METHOD_NAME){
                return app(WalletCheckoutService::class)->processPostCheckoutOrder(
                    $products,
                    $request,
                    $token,
                    $sessionData
                );
            }

            $paymentResult = [
                'wallet_deduction' => $walletResult ?? null,
                'card_payment' => null,
                'total_amount' => $totalAmount,
                'wallet_amount' => $walletAmount,
                'card_amount' => $cardAmount,
            ];
            return self::success($paymentResult, 'Split payment processed successfully');
        }catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(),422);
        }
    }

    public function postCheckout()
    {
        return app(WalletCheckoutService::class)->processPostCheckoutOrder(
            $products,
            $request,
            $token,
            $sessionData,
            $this->httpResponse()
        );
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('checkout::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('checkout::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('checkout::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('checkout::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
