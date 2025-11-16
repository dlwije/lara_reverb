<?php

namespace Modules\Telr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sma\Order\Payment;
use App\Models\Sma\People\Customer;
use App\Models\User;
use App\Services\ControllerService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Services\PaymentStatusEnum;
use Modules\Wallet\Services\SplitPaymentPGatewayFirstService;
use Modules\Wallet\Services\WalletService;

class TelrController extends Controller
{

    public function __construct(public ControllerService $controllerService, public SplitPaymentPGatewayFirstService $splitPaymentPGatewayFirstService)
    {

    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Log::info('telr.index');
        return Inertia::render('telr/process-form');
    }

    public function payment(Request $request)
    {
        Log::info('Telr Payment Request', ['request' => $request->all()]);
        exit('sd');
    }
    public function process(Request $request)
    {
        $request->validate([
            'store_id' => 'required|string',
            'auth_key' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'bill_fname' => 'required|string',
            'bill_sname' => 'required|string',
            'bill_email' => 'required|email',
            'bill_tel' => 'required|string',
            'telr_token' => 'required|string',
            'repeat_amount' => 'required|numeric',
            'repeat_period' => 'required|string|in:M,W',
            'repeat_interval' => 'required|integer',
            'repeat_term' => 'required|integer',
            'repeat_final' => 'required|numeric',
        ]);

        try {
            // Use Laravel route URLs
            $returnAuth = URL::route('telr.auth');
            $returnCan = URL::route('telr.cancel');
            $returnDecl = URL::route('telr.decline');

            $data = [
                'ivp_method' => 'create',
                'ivp_source' => 'Laravel Transparent JS SDK',
                'ivp_store' => $request->store_id,
                'ivp_authkey' => $request->auth_key,
                'ivp_cart' => rand(100, 999) . rand(100, 999) . rand(100, 999),
                'ivp_test' => 1, // Set to 0 for production
                'ivp_framed' => 2,
                'ivp_amount' => $request->amount,
                'ivp_lang' => 'en',
                'ivp_currency' => $request->currency,
                'ivp_desc' => 'Transaction from Laravel Transparent SDK',
                'return_auth' => $returnAuth,
                'return_can' => $returnCan,
                'return_decl' => $returnDecl,
                'bill_fname' => $request->bill_fname,
                'bill_sname' => $request->bill_sname,
                'bill_addr1' => $request->bill_addr1,
                'bill_addr2' => $request->bill_addr2,
                'bill_city' => $request->bill_city,
                'bill_region' => $request->bill_region,
                'bill_zip' => $request->bill_zip,
                'bill_country' => $request->bill_country,
                'bill_email' => $request->bill_email,
                'bill_tel' => $request->bill_tel,
                'ivp_paymethod' => 'card',
                'card_token' => $request->telr_token,
                'repeat_amount' => $request->repeat_amount,
                'repeat_period' => $request->repeat_period,
                'repeat_interval' => $request->repeat_interval,
                'repeat_start' => 'next',
                'repeat_term' => $request->repeat_term,
                'repeat_final' => $request->repeat_final,
            ];

            $results = $this->apiRequest($data);

            if (isset($results['order']['ref']) && isset($results['order']['url'])) {
                $ref = trim($results['order']['ref']);
                $url = trim($results['order']['url']);

                Session::put([
                    'telr_ref' => $ref,
                    'telr_store_id' => $request->store_id,
                    'telr_auth_key' => $request->auth_key,
                ]);

                return response()->json([
                    'redirect_link' => $url,
                    'success' => true,
                ]);
            }

            Log::error('Telr API Error: Invalid response', ['response' => $results]);
            return response()->json([
                'error' => 'Error occurred in processing transaction',
                'details' => $results['error']['message'] ?? 'Unknown error',
            ], 422);

        } catch (\Exception $e) {
            Log::error('Telr Payment Processing Error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function auth(Request $request)
    {
//        Log::info('Telr Request', ['request' => $request->all()]);
        // Handle successful authentication callback
        $telrRef = Session::get('telr_ref');
        $telrStoreId = Session::get('telr_store_id');
        $telrAuthKey = Session::get('telr_auth_key');

        if (!$telrRef || !$telrStoreId || !$telrAuthKey) {
            return inertia('PaymentError', [
                'message' => 'Invalid session data',
            ]);
        }

        $params = [
            'ivp_method' => 'check',
            'ivp_store' => $telrStoreId,
            'ivp_authkey' => $telrAuthKey,
            'order_ref' => $telrRef
        ];

        $results = $this->apiRequest($params);

        Log::info('Telr Auth Response', ['results' => $results]);;
        $objOrder = $results['order'] ?? null;
        $objError = $results['error'] ?? null;

        // Check for errors
        if (is_array($objError)) {
            return Inertia::render('telr/payment-error', [
                'message' => 'Transaction failed',
                'details' => $objError,
            ]);
        }

        // Validate required fields
        if (!isset(
            $objOrder['cartid'],
            $objOrder['status']['code'],
            $objOrder['transaction']['status'],
            $objOrder['transaction']['ref']
        )) {
            return Inertia::render('telr/payment-error', [
                'message' => 'Invalid transaction response',
                'details' => $results,
            ]);
        }

        //Order Status
        $ordStatus = $objOrder['status']['code'];
        $ordStatusText = $objOrder['status']['text'];

        // Transaction status
        $newTx = $objOrder['transaction']['ref'];
        $txStatus = $objOrder['transaction']['status'];
        $txMessage = $objOrder['transaction']['message'];
        $cartId = $objOrder['cartid'];

        // Handle different order statuses
        if (in_array($ordStatus, [-1, -2, -3, -4])) {
            return Inertia::render('telr/payment-cancel', [
                'message' => 'Transaction was cancelled or expired',
                'reference' => $newTx,
                'status' => $ordStatus,
            ]);
        }

        if ($ordStatus == 4) {
            return Inertia::render('telr/payment-pending', [
                'message' => 'Transaction is pending',
                'reference' => $newTx,
            ]);
        }

        if ($ordStatus == 1) {
            return Inertia::render('telr/payment-pending', [
                'message' => 'Payment pending',
                'reference' => $newTx,
            ]);
        }

        if ($ordStatus == 2) {

            return Inertia::render('telr/payment-success', [
                'message' => 'Transaction authorized successfully',
                'reference' => $newTx,
                'transaction_ref' => $newTx,
            ]);
        }

        if ($ordStatus == 3) {
            if ($txStatus == 'P') {
                return Inertia::render('telr/payment-pending', [
                    'message' => 'Transaction is pending',
                    'reference' => $newTx,
                ]);
            }

            if ($txStatus == 'H') {
                return Inertia::render('telr/payment-pending', [
                    'message' => 'Transaction is on hold',
                    'reference' => $newTx,
                ]);
            }

            if ($txStatus == 'A') {
                try {
                    $chargeId = $objOrder['ref'];
                    $orderId = $objOrder['cartid'];
                    $status = PaymentStatusEnum::COMPLETED;

                    $data['amount'] = $objOrder['amount'];
                    $data['currency'] = $objOrder['currency'];

                    $paymentData = [
                        'amount' => $data['amount'],
                        'currency' => $data['currency'],
                        'trans_freeze_id' => Arr::get($data, 'trans_freeze_id'),
                        'wallet_applied_amount' => Arr::get($data, 'wallet_applied_amount'),
                        'wallet_pay_id' => Arr::get($data, 'wallet_pay_id'),
                        'charge_id' => $chargeId,
                        'order_id' => $orderId,
                        'customer_id' => auth()->user()->id,
                        'customer_type' => Arr::get($data, 'customer_type'),
                        'payment_channel' => TELR_PAYMENT_METHOD_NAME,
                        'status' => $status,
                    ];

                    Log::info('Telr Payment Response', ['paymentData' => $paymentData]);
                    $payment = $this->afterMakePayment($paymentData);
                }catch (\Exception $e) {
                    return Inertia::render('telr/payment-error', [
                        'message' => $e->getMessage(),
                        'details' => $results,
                    ]);
                }
                return Inertia::render('telr/payment-success', [
                    'message' => 'Transaction authorized successfully',
                    'reference' => $newTx,
                    'transaction_ref' => $newTx,
                ]);
            }
        }

        // Default case for unhandled status
        return Inertia::render('telr/payment-pending', [
            'message' => 'Transaction status is being processed',
            'reference' => $newTx,
            'status' => $ordStatus,
            'transaction_status' => $txStatus,
        ]);
    }

    public function afterMakePayment(array $data): string|null
    {
        $status = PaymentStatusEnum::COMPLETED;

        //$chargeId = session('telr_payment_id');
        $chargeId = $data['charge_id'];
        $currency = $data['currency'] ?: $this->controllerService->getDefaultValues()['default_currency'];

        $orderIds = (array)Arr::get($data, 'order_id', []);
        $actualOrderId = is_array($orderIds) ? Arr::first($orderIds) : $orderIds;

        $gatewayAmount = (float) Arr::get($data, 'amount', 0);

        $customer_id = (int) Arr::get($data, 'customer_id', null);
        $customer = User::query()->find($customer_id);

        DB::beginTransaction();
        try {

            $walletData = app(WalletService::class)->addToWallet(
                $customer,
                (float) $gatewayAmount,                     // amount
                WalletLot::SOURCE_CREDIT_CARD,               // source
                (float) $gatewayAmount,                     // baseValue
                0.0,                                         // bonusValue
                WalletTransaction::STATUS_COMPLETED,           // wTStatus
                WalletLot::STATUS_ACTIVE,                    // wLStatus
                [
                    'ref_type' => User::class,
                    'ref_id' => $customer_id,
                ],
                null,                                        // validityDays
                $currency,//strtoupper(get_application_currency()->title) // currency
            );


            $paymentData['trans_id'] = $walletData['transaction']->id;
            $paymentData['charge_id'] = $data['charge_id']; // ✅ attach charge id here
            $paymentData['customer_id'] = $customer_id;
            $paymentData['amount'] = $gatewayAmount;
            $paymentData['order_id'] = $actualOrderId;
            $paymentData['currency'] = $currency;
            $paymentData['payment_channel'] = $data['payment_channel'];

            $this->splitPaymentPGatewayFirstService->createWalletTopUpPayment($paymentData);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Log::info("Wallet Payment Error: ".$e);
            throw new \Exception("Wallet Payment Error: ".$e->getMessage());
        }

        session()->forget('telr_payment_id');

        return $chargeId;
    }

    public function cancel(Request $request)
    {
        // Handle cancellation callback
        return Inertia::render('telr/payment-cancel', [
            'message' => 'Payment was cancelled by user',
        ]);
    }

    public function decline(Request $request)
    {
        // Handle declined payment callback
        $telrRef = Session::get('telr_ref');
        $telrStoreId = Session::get('telr_store_id');
        $telrAuthKey = Session::get('telr_auth_key');

        if (!$telrRef || !$telrStoreId || !$telrAuthKey) {
            return Inertia::render('telr/payment-error', [
                'message' => 'Invalid session data',
            ]);
        }

        $params = [
            'ivp_method' => 'check',
            'ivp_store' => $telrStoreId,
            'ivp_authkey' => $telrAuthKey,
            'order_ref' => $telrRef
        ];

        $results = $this->apiRequest($params);

        Log::info('Telr Decline Response', ['results' => $results]);

        return Inertia::render('telr/payment-decline', [
            'message' => 'Payment was declined',
            'details' => $results['error'] ?? $results,
            'reference' => $telrRef,
        ]);
    }

    private function apiRequest(array $data): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->retry(3, 100)
            ->post('https://secure.telr.com/gateway/order.json', $data);

        if ($response->failed()) {
            throw new \Exception('Telr API request failed: ' . $response->status());
        }

        return $response->json();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('telr::create');
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
        return view('telr::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('telr::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}


    public function successs(
        Request $request,
        TelrPaymentService $telrPaymentService,
        SplitPaymentPGatewayFirstService $splitPaymentPGatewayFirstService,
        BaseHttpResponse $response
    ) {
        try {
            //$chargeId = session('telr_payment_id');
            $chargeId = trim($request->get('OrderRef'));
            $orderDetail = $telrPaymentService->getPaymentDetails($chargeId);
            $isPaid = false;
            if(isset($orderDetail->order)) {
                if($orderDetail->order->status) {
                    $status = strtolower(trim($orderDetail->order->status->text));
                    if($status == 'paid') { $isPaid = true; }
                }
            }
            Log::info('TelrController::redirectURL', ['RedirectURL' => PaymentHelper::getRedirectURL()]);
            Log::info('TelrController::success', ['orderDetail' => $orderDetail]);
            Log::info('TelrController::response', ['isPaid' => $isPaid, 'response' => $response]);
            if($isPaid) {
                $telrPaymentService->afterMakePayment($request->input());

//                $splitPaymentPGatewayFirstService->deductReleaseWalletFreeze();
                return $response
                    ->setNextUrl(PaymentHelper::getRedirectURL())
                    ->setMessage(__('Checkout successfully!'));
            }

            return $this->redirectOnError($response);
        } catch (Exception $exception) {
            Log::error('TelrController::success:trycatch', ['exception' => $exception]);
            return $this->redirectOnError($response);
        }
    }

    public function errors(Request $data,BaseHttpResponse $response)
    {

        $token = OrderHelper::getOrderSessionToken();
        $orders = Order::query()->where('token', $token)->get();

        foreach ($orders as $order) {
            if (!$order->payment_id) {
                continue;
            }

            // 🔹 Find the related payment
            $payment = Payment::find($order->payment_id);

            if ($payment) {

                // 🔹 Update payment status to failed
                $payment->update([
                    'status' => PaymentStatusEnum::FAILED,
                    'failure_reason' => 'Payment failed or cancelled by user',
                ]);

                Log::info("Payment #{$payment->id} marked as FAILED for Order #{$order->id}");

                // 🔹 Get related wallet transaction if any
                if ($payment->wallet_transaction_id) {
                    $walletTransaction = WalletTransaction::find($payment->wallet_transaction_id);

                    if($payment->payment_channel == WALLET_PAYMENT_METHOD_NAME){
                        $wallet_applied_amount = $payment->wallet_applied;
                        $customerRes = Customer::query()->where('id',$order->user_id)->first();

                        if ($wallet_applied_amount > 0 && $walletTransaction) {
                            Log::info('TelrController::error'.$order->id, ['wallet_applied_amount' => $wallet_applied_amount]);
                            Log::info('TelrController::error'.$order->id, ['wallet_trans' => $walletTransaction]);
                            app(SplitPaymentPGatewayFirstService::class)->releaseFrozenWallet($customerRes, $wallet_applied_amount, $order->id, $walletTransaction->id);
                        }
                    }
                    if ($walletTransaction) {
                        Log::info("WalletTransaction #{$walletTransaction->id} marked as FAILED for Order #{$order->id}");
                    }
                }
            }
        }

        /** re-available coupon if order cancel START **/

        if($token) {
            $order = Order::query()->where(['token' => $token, 'is_finished' => false])->where('coupon_code', '!=', null)->first();
            if ($order) {
                $appliedCouponCode = ($order->coupon_code != null ? $order->coupon_code : '');
                if(!empty($appliedCouponCode)){
                    Discount::getFacadeRoot()->afterOrderCancelled($appliedCouponCode);
                }
            }
        }
        /** END **/

        return $this->redirectOnError($response);
    }

    private function redirectOnError($response) {
        if((bool) request()->isApp) {
            $token = OrderHelper::getOrderSessionToken();
            $tUrl = route('public.checkout.cancel', [$token] + ['error' => true, 'error_type' => 'payment']);
            return $response
                ->setError()
                ->setNextUrl($tUrl)
                ->withInput()
                ->setMessage(__('Payment failed!'));
        } else {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->withInput()
                ->setMessage(__('Payment failed!'));
        }
    }

    public function subSuccess(
        Request $request,
        TelrPaymentService $telrPaymentService,
        BaseHttpResponse $response
    ) {
        try {
            //$chargeId = session('telr_subscription_id');
            $chargeId = trim($request->get('OrderRef'));
            $orderDetail = $telrPaymentService->getPaymentDetails($chargeId);
            $isPaid = false;

            if(isset($orderDetail->order)) {
                if($orderDetail->order->status) {
                    $status = strtolower(trim($orderDetail->order->status->text));
                    if($status == 'paid') { $isPaid = true; }
                }
            }

            if($isPaid) {
                $telrPaymentService->afterMakeSubscriptionPayment($request->input());
                $sub = Subscription::query()->with('user')->where('id', $request->input('subscriber_id'))->first();
                if ($sub != null) {
                    $sub->status = SubscriptionStatusEnum::ACTIVE;
                    $sub->save();

                    $currenctStep = (int) $sub->user->getMeta('step');
                    $currenctStep = ($currenctStep > 6) ? $currenctStep : 6;
                    $sub->user->setMeta('step', $currenctStep);
                }

                return $response
                    ->setNextUrl(get_frontend_url('join-us-as-seller'))
                    ->setMessage(__('Checkout successfully!'));
            }

            return $response
                ->setError()
                ->setNextUrl(get_frontend_url('join-us-as-seller').'?type=error')
                ->setMessage(__('Payment failed!'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setNextUrl(get_frontend_url('join-us-as-seller').'?type=error')
                ->withInput()
                ->setMessage($exception->getMessage() ?: __('Payment failed!'));
        }
    }

    public function subError(BaseHttpResponse $response)
    {
        return $response
            ->setError()
            ->setNextUrl(get_frontend_url('join-us-as-seller').'?type=errorr&message='.__('Payment failed!'))
            ->withInput();
    }

    public function giftCardSuccess(
        Request $request,
        TelrPaymentService $telrPaymentService,
        BaseHttpResponse $response
    ) {
        try {
            //$chargeId = session('telr_giftcard_id');
            $chargeId = trim($request->get('OrderRef'));
            $orderDetail = $telrPaymentService->getPaymentDetails($chargeId);
            $isPaid = false;

            if(isset($orderDetail->order)) {
                if($orderDetail->order->status) {
                    $status = strtolower(trim($orderDetail->order->status->text));
                    if($status == 'paid') { $isPaid = true; }
                }
            }

            if($isPaid) {
                $telrPaymentService->afterMakeGiftCardPayment($request->input());

                return $response
                    ->setNextUrl(get_frontend_url('customer/gift-cards').'?type=success&message='.__('Gift Card created successfully!'))
                    ->setMessage(__('Checkout successfully!'));
            }

            return $response
                ->setError()
                ->setNextUrl(get_frontend_url('customer/gift-cards/create').'?type=error&message='.__('Payment failed!'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setNextUrl(get_frontend_url('customer/gift-cards/create').'?type=error&message='.($exception->getMessage() ?: __('Payment failed!')))
                ->withInput();
        }
    }

    public function giftCardError(Request $request, BaseHttpResponse $response)
    {
        /** delete gift card entry **/
        if($request->input('giftcard_id')) {
            GiftCard::query()->where('id', $request->input('giftcard_id'))->delete();
        }
        /** END **/

        return $response
            ->setError()
            ->setNextUrl(get_frontend_url('customer/gift-cards/create').'?type=error&message='.__('Payment failed!'))
            ->withInput();
    }
}
