<?php

namespace Modules\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sma\Pos\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Checkout\Http\Requests\CheckoutRequest;
use Modules\Checkout\Services\CheckoutOrderService;
use Modules\Wallet\Services\KYCService;
use Modules\Wallet\Services\WalletService;

class CheckoutController extends Controller
{
    public function __construct(
        public WalletService $walletService,
        public KYCService $kycService,
        public CheckoutOrderService $checkoutOrderService
    ) {}

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

    public function getCheckoutPage(string $token, Request $request)
    {
        // First get the checkout data from session if there were

        // If the provided token is not equal to the current session token
        if($token !== session('order_checkout_token')){

            $order = Order::query()->where(['token' => $token, 'is_finished' => false])->first();

//            if (! $order) {
                // If there is no order on that token will redirect to the home page
//            }
        }

        $sessionCheckoutData = $this->checkoutOrderService->getOrderSessionData($token);

        // Here we can check cart has products or not
        // if not redirect to the cart page
        // if there is products, then check the Out of Stock status and redirect with products message which doesn't have qty


    }

    public function processOrderData(string $token, array $sessionData, Request $request, bool $finished = false)
    {

    }
    /**
     * Split payment (wallet + card)
     */
    public function processSplitPayment(CheckoutRequest $request)
    {
        // The Request

        try {
            // get the order data based on the Token

            $user = auth()->user();
            $totalAmount = $request->input('total_amount', 0);
            $walletAmount = $request->input('wallet_amount', 0);
            $cardAmount = $request->input('card_amount', 0);
            $orderId = $request->input('order_id');

            if($walletAmount > 0){
                // Check KYC requirements for wallet portion
                $this->kycService->blockIfKycRequired($user, $walletAmount);

                // Deduct from wallet
                $walletResult = $this->walletService->deductFromWallet($user, $walletAmount, [
                    'type' => 'purchase',
                    'ref_type' => 'order',
                    'ref_id' => $orderId,
                    'description' => 'Wallet portion of split payment'
                ]);
            }
            // Process card payment here
            // $cardResult = $this->processCardPayment($user, $cardAmount, $request->all());

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
