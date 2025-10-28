<?php

namespace Botble\Wallet\Services;

use Botble\Ecommerce\Models\Currency;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Wallet\Models\Wallet;
use Botble\Wallet\Models\WalletLot;
use Botble\Wallet\Models\WalletLotFreeze;
use Botble\Wallet\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SplitPaymentPGatewayFirstService
{
    public function __construct(
        public WalletService $walletService,
        public WalletCheckoutPaymentService $walletCheckoutPaymentService,
    ){}


    public function processSplitPayment($user, array $paymentData, Request $request): array
    {
//        Log::info('Step7: processSplitPayment in SplitPaymentPGatewayFirstService - Gateway First Approach');
//        Log::info('Split Payment Started:', [
//            'user_id' => $user->id,
//            'amount' => $paymentData['amount'],
//            'order_id' => $paymentData['order_id'],
//            'use_wallet' => $paymentData['use_wallet'] ?? true,
//            'payment_data' => $paymentData,
//        ]);

        return DB::transaction(function () use ($user, $paymentData, $request) {
            $totalAmount = $paymentData['amount'];
            $orderId = $paymentData['order_id'];

            // Extract the actual order ID if it's an array
            $actualOrderId = is_array($orderId) ? Arr::first($orderId) : $orderId;

            $useWallet = $paymentData['use_wallet'] ?? true;

            // Create payment intent first with initial values
            $paymentIntent = null;

            $walletTransaction = null;
            $walletApplied = 0;
            $frozenLots = [];
            $gatewayAmount = $totalAmount; // Start with full amount for gateway

            $paymentData = array_merge($paymentData, [
                'error' => false,
                'message' => 'Purchase deduction successful',
                'amount' => $totalAmount,
                'currency' => strtoupper(get_application_currency()->title),
//                'type' => $request->input('payment_method'),
//                'charge_id' => null,
                'is_api' => true,
                'wallettracsaction_id' => null,
                'wallet_lot_id' => null,
                'user_data' => $user,
                'name' => 'Order Purchase Payment'
            ]);
            try {
                // Step 1: Freeze whatever wallet amount is available
//                Log::info('wallet_status:', [
//                    'use_wallet' => $useWallet,
//                    'total_amount' => $totalAmount,
//                ]);

                if ($useWallet) {
                    $freezeResult = $this->freezeWalletAmount($user, $totalAmount, $actualOrderId);

                    if (!$freezeResult['success']) {
                        Log::warning('Wallet freeze failed', ['freeze_result' => $freezeResult, ]);

                        throw new \Exception('Wallet freeze failed: ' . $freezeResult['message']);
                    }

                    $walletApplied = $freezeResult['amount_frozen'];
                    $gatewayAmount = $freezeResult['remaining_amount']; // Update gateway amount based on actual frozen amount

                    if ($walletApplied > 0) {
                        $walletTransaction = $freezeResult['transaction'];
                        $frozenLots = $freezeResult['frozen_lots'];

                        $paymentIntent = $this->createPayment($user, $walletApplied,$totalAmount, $orderId, $useWallet, $paymentData, $walletTransaction ? $walletTransaction->id : null,$frozenLots);
                        $paymentData['payment_id'] = $paymentIntent->id; // ✅ attach payment id here
                        $paymentData['charge_id'] = $paymentIntent->charge_id; // ✅ attach charge id here


                        $walletTransaction->update(['payment_id' => $paymentIntent->id]);


                        $paymentData['trans_freeze_id'] = $walletTransaction ? $walletTransaction->id : null;

//                        Log::info('Wallet amount frozen with lots:', [
//                            'amount' => $walletApplied,
//                            'gateway_amount' => $gatewayAmount,
//                            'transaction_id' => $walletTransaction->id,
//                            'frozen_lots_count' => count($frozenLots)
//                        ]);
                    } else {
                        Log::info('No wallet amount frozen, proceeding with full gateway payment');
                    }
                }

                // Step 2: Process gateway payment for remaining amount
//                Log::info('SplitPaymentPGatewayFirstService:Step2_status:', [
//                    'gatewayAmount' => $gatewayAmount,
//                    'walletApplied' => $walletApplied,
//                    'totalAmount' => $totalAmount,
//                ]);

                $gatewayResult = null;
                if ($gatewayAmount > 0) {
                    // will receive either 'checkoutUrl('order_ref and url')' or 'paid_data'
                    $gatewayResult = $this->processGatewayPayment($user, $gatewayAmount, $paymentData, $request);

//                    Log::info('Sent to Gateway payment successfully:', [
//                        'gateway_amount' => $gatewayAmount,
//                        'intent_id' => $gatewayResult['intent_id'] ?? null,
//                        'charge_id' => $gatewayResult['checkoutUrl']['order_ref'] ?? null,
//                    ]);
                } else {
                    // Full amount paid by wallet
//                    Log::info('Full amount paid by wallet only');
                    if ($walletApplied > 0 && $walletTransaction) {
                        $this->processWalletPayment($user, $walletApplied, $gatewayAmount, $orderId, $paymentIntent, $walletTransaction);
                    }
                }

                $result = [
                    'success' => true,
                    'checkoutUrl' => $gatewayResult['checkoutUrl']['url'] ?? null,
                    'order_ref' => $gatewayResult['checkoutUrl']['order_ref'] ?? null,
                    'payment_intent_id' => $paymentIntent->id ?? null, // this is null if the amount completed by payment gateway only
                    'total_amount' => $totalAmount,
                    'wallet_applied' => $walletApplied,
                    'gateway_amount' => $gatewayAmount,
                    'wallet_transaction' => $walletTransaction,
                    'frozen_lots' => $frozenLots,
                    'new_balance' => $deductResult['new_balance'] ?? ($walletApplied > 0 ? $user->wallet->total_available : null),
                    'gateway_result' => $gatewayResult,
                    'payment_intent' => $paymentIntent
                ];

                return $this->formatSplitPaymentResponse($result, $paymentData);

            } catch (\Exception $e) {
                Log::error('Split payment failed:', [
                    'user_id' => $user->id,
                    'order_id' => $actualOrderId,
                    'error' => $e->getMessage()
                ]);

                // Ensure any frozen amounts are released on failure
                if ($walletApplied > 0 && $walletTransaction) {
                    $this->releaseFrozenWallet($user, $walletApplied, $actualOrderId, $walletTransaction->id);
                }

                throw $e;
            }
        });
    }

    public function processWalletPayment($user, $walletApplied, $gatewayAmount, $orderIds, $paymentIntent, $walletTransaction){

        $actualOrderId = is_array($orderIds) ? Arr::first($orderIds) : $orderIds;
        $deductResult = $this->deductFrozenWallet($user, $walletApplied, $actualOrderId, $walletTransaction->id);

        if (!$deductResult['success']) {
            throw new \Exception('Wallet deduction failed: ' . $deductResult['message']);
        }

        $orderIds = (array)$orderIds;

        if (! $orderIds) return;

        $orders = Order::query()->whereIn('id', $orderIds)->get();

        // transfer gift-card remaining amount to the customer wallet
        $this->transferCouponRemainingToWallet($actualOrderId, $user);
        foreach ($orders as $order) {
//            $data['amount'] = $order->amount;
//            $data['order_id'] = $order->id;
//            $data['currency'] = strtoupper(cms_currency()->getDefaultCurrency()->title);
//            PaymentHelper::storeLocalPayment($data);

            Cart::instance('cart')->destroy();
            $customerId = (int) $order->user_id;
            if($customerId > 0) { Cart::instance('cart')->storeQuietly($customerId); Cart::instance('cart')->restoreQuietly($customerId); }
        }
        $data['charge_id'] = $paymentIntent->charge_id ?? null;
        OrderHelper::processOrder($orders->pluck('id')->all(), $data['charge_id']);

        // Step 4: Update payment intent as completed
//                        Log::info('SplitPaymentPGatewayFirstService:Step3FullWallet_status:');
        $paymentIntent->update([
            'wallet_applied' => $walletApplied,
            'card_amount' => $gatewayAmount,
            'payment_channel' => $gatewayAmount > 0 ? ($gatewayResult['gateway'] ?? 'telr') : 'wallet',
            'status' => PaymentStatusEnum::COMPLETED,
        ]);

        return $deductResult;
    }

    public function transferCouponRemainingToWallet($orderId, $customer)
    {
        $order = Order::where('id', $orderId)->first();
        $gift_card = Discount::where('type', 'gift-card')->where('code', $order->coupon_code)->first();
        Log::info('gift_card on afterMakePaymentTelrPayService: '.json_encode($gift_card));
        $gift_card_amount = 0;
        if($gift_card) {
            $gift_card_amount = $gift_card->value ?? 0;
            Log::info('gift_card_amount on afterMakePaymentTelrPayService: '.$gift_card_amount);
        }
        if($gift_card_amount > 0) {
            $remainingAmount = max(0, $gift_card_amount - ($order->sub_total + $order->tax_amount));
            Log::info('remainingAmount on afterMakePaymentTelrPayService: '.$remainingAmount);
            if($remainingAmount > 0) {
                app(WalletService::class)->addToWallet(
                    $customer,
                    (float)$remainingAmount,                     // amount
                    WalletLot::SOURCE_GIFT_CARD,               // source
                    (float)$remainingAmount,                     // baseValue
                    0.0,                                         // bonusValue
                    WalletTransaction::STATUS_COMPLETED,           // wTStatus
                    WalletLot::STATUS_ACTIVE,                    // wLStatus
                    [
                        'ref_type' => Discount::class,
                        'ref_id' => $gift_card->id,
                    ],
                    null,                                        // validityDays
                    strtoupper(get_application_currency()->title) // currency
                );
            }
        }
    }

    public function previewSplitPayment($user, float $totalAmount): array
    {
        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletBalance = $wallet ? $wallet->total_available : 0;

        // Calculate available amount from non-expired lots
        $activeLots = WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->get(['id', 'remaining']);

        $availableLotsAmount = $activeLots->sum('remaining');

//        Log::info('Available lots info:', [
//            'user_id' => $user->id,
//            'lot_ids' => $activeLots->pluck('id'),
//            'lots' => $activeLots,
//            'total_remaining' => $availableLotsAmount,
//        ]);

        $walletApplicable = min($availableLotsAmount, $totalAmount);
        $gatewayAmount = $totalAmount - $walletApplicable;

        //        Log::info('Split payment preview with lot consideration:', $breakdown);

        return [
            'total_amount' => $totalAmount,
            'wallet_balance' => (float) $walletBalance,
            'available_lots_amount' => (float) $availableLotsAmount,
            'wallet_applicable' => (float) $walletApplicable,
            'gateway_amount' => $gatewayAmount,
            'can_use_wallet' => (float) $availableLotsAmount > 0,
            'breakdown' => [
                'wallet_percentage' => $totalAmount > 0 ? round(($walletApplicable / $totalAmount) * 100, 2) : 0,
                'gateway_percentage' => $totalAmount > 0 ? round(($gatewayAmount / $totalAmount) * 100, 2) : 0
            ]
        ];
    }

    public function getChargeId($wallet_pay_id)
    {
        // Create a unique, timestamped charge ID
        $chargeId = sprintf(
            'WALLET%s%d%s',
            now()->format('Ymd'),
            $wallet_pay_id,
            Str::upper(Str::random(10))
        );

        return $chargeId;
    }
    private function createPayment($user, $wallet_amount_applied,$totalAmount, $orderId, $useWallet, $paymentData, $wal_trans_id, $frozenLots = []){

        $paymentIntent = app(PaymentInterface::class)->create([
            'amount' => $totalAmount,
            'wallet_applied' => $wallet_amount_applied,
            'currency' => strtoupper(get_application_currency()->title),
            'charge_id' => $this->getChargeId($wal_trans_id),
            'order_id' => Arr::first($orderId),
            'customer_id' => Arr::get($paymentData, 'customer_id') ? $paymentData['customer_id'] : $user->id,
            'customer_type' => Arr::get($paymentData, 'customer_type') ? $paymentData['customer_type'] : Customer::class,
            'payment_channel' =>'wallet',
            'use_wallet' => $useWallet,
            'wallet_lot_allocation' => $frozenLots,
            'wallet_transaction_id' => $wal_trans_id,
            'status' => PaymentStatusEnum::PENDING,
        ]);

//        Log::info('PaymentIntent created successfully');

        if ($paymentIntent && isset($paymentIntent->id)) {
            Order::whereIn('id', (array) $orderId)->update([
                'payment_id' => $paymentIntent->id,
                'updated_at' => now(),
            ]);

//            Log::info('Order payment_id updated successfully', [
//                'order_ids' => $orderId,
//                'payment_id' => $paymentIntent->id,
//            ]);
        } else {
            Log::warning('PaymentIntent not created properly, order payment_id not updated', [
                'order_ids' => $orderId,
                'paymentIntent' => $paymentIntent,
            ]);
        }
        return $paymentIntent;
    }

    public function deductReleaseWalletFreeze($user, $walletApplied, $actualOrderId, $walletTransaction, $gatewayAmount = 0.00):void
    {
        // This function will be called after a successful gateway payment as well. not only after wallet payment
        $deductResult = $this->deductFrozenWallet($user, $walletApplied, $actualOrderId, $walletTransaction->id);
//        Log::info('SplitPaymentPGatewayFirstService:Step3_deductResult:', [
//            'deduct_result' => $deductResult,
//        ]);

        if (!$deductResult['success']) {

            // Release the frozen lots since deduction failed
            $releaseRes = $this->releaseFrozenWallet($user, $walletApplied, $actualOrderId, $walletTransaction->id);
            if(!$releaseRes['success']){
                Log::error('Release Frozen Wallet failed after successful gateway payment:', $releaseRes);
            }

            throw new \Exception('Wallet deduction failed after successful gateway payment');
        }
    }
    /**
     * Freeze wallet amount with lot tracking
     */
    public function freezeWalletAmount($user, float $amount, $orderId): array
    {
        DB::beginTransaction();
        try {
            // Extract the actual order ID if it's an array
            $actualOrderId = $orderId;

            // If it's still an array or null, handle the error
            if (is_array($actualOrderId) || $actualOrderId === null) {
                DB::rollBack();
//                Log::error('Invalid order ID provided:', [$actualOrderId]);
                return [
                    'success' => false,
                    'message' => 'Invalid order ID provided'
                ];
            }

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
//                DB::rollBack();
                return [
                    'message' => 'No wallet found',
                    'success' => true,
                    'amount_frozen' => 0,
                    'transaction' => null,
                    'frozen_lots' => null,
                    'new_balance' => $wallet->total_available ?? 0,
                    'remaining_amount' => 0 // Amount that needs gateway payment
                ];
            }

            // Get available lots to freeze (FIFO - First In First Out)
            $availableLots = WalletLot::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('remaining', '>', 0)
                ->where('expires_at', '>', now())
                ->orderBy('acquired_at', 'asc')
                ->lockForUpdate()
                ->get();

            $amountToFreeze = min($amount, $wallet->total_available); // Only freeze what's available
            $originalAmountToFreeze = $amountToFreeze;
            $lotAllocations = [];
            $frozenLots = [];

            foreach ($availableLots as $lot) {
                if ($amountToFreeze <= 0) break;

                $allocatedAmount = min($lot->remaining, $amountToFreeze);

                // Create lot freeze record
                $lotFreeze = WalletLotFreeze::create([
                    'wallet_lot_id' => $lot->id,
                    'user_id' => $user->id,
                    'order_id' => $actualOrderId,
//                    'payment_id' => $paymentIntentId,
                    'amount' => $allocatedAmount,
                    'status' => 'frozen',
                    'expires_at' => now()->addMinutes(15),
                ]);

                // Update lot remaining
                $lot->decrement('remaining', $allocatedAmount);

                $lotAllocations[] = [
                    'lot_id' => $lot->id,
                    'amount' => $allocatedAmount,
                    'freeze_id' => $lotFreeze->id,
                    'source' => $lot->source,
                    'base_value' => $allocatedAmount, // Use actual allocated amount
                    'bonus_value' => 0,
                ];

                $frozenLots[] = $lotFreeze;
                $amountToFreeze -= $allocatedAmount;
            }

            $actualFrozenAmount = $originalAmountToFreeze - $amountToFreeze;

            // Only create transaction and update wallet if we actually froze some amount
            $transaction = null;
            if ($actualFrozenAmount > 0) {
                // Create wallet transaction for freeze
                $transaction = WalletTransaction::create([
                    'user_id' => $user->id,
                    'direction' => 'DR',
                    'amount' => $actualFrozenAmount,
                    'currency' => 'AED',
                    'type' => WalletTransaction::TYPE_PURCHASE,
                    'status' => WalletTransaction::STATUS_PENDING,
                    'ref_type' => Order::class,
                    'ref_id' => $actualOrderId,
                    'lot_allocation' => $lotAllocations,
                    'description' => "Amount frozen for order #{$actualOrderId}",
                ]);

                // Update wallet balances
                $wallet->decrement('total_available', $actualFrozenAmount);
                $wallet->increment('total_frozen', $actualFrozenAmount);

                // Update lots
                foreach ($frozenLots as $lot) {
                    $lot->transaction_id = $transaction->id;
                    $lot->save();
                }
            }

            DB::commit();

//            Log::info('Wallet freeze completed:', [
//                'requested_amount' => $amount,
//                'actual_frozen_amount' => $actualFrozenAmount,
//                'remaining_to_gateway' => bcsub($amount, $actualFrozenAmount, 2),
//                'wallet_balance_before' => $wallet->total_available + $actualFrozenAmount,
//                'wallet_balance_after' => $wallet->total_available
//            ]);

            return [
                'success' => true,
                'amount_frozen' => $actualFrozenAmount,
                'transaction' => $transaction,
                'frozen_lots' => $frozenLots,
                'new_balance' => $wallet->total_available,
                'remaining_amount' => bcsub($amount, $actualFrozenAmount, 2) // Amount that needs gateway payment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet freeze with lots failed:', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function processGatewayPayment($user, float $amount, array $paymentData, $request): array
    {
        DB::beginTransaction();
        try {

            //FILTER_ECOMMERCE_PROCESS_WALLET_TOPUP_PAYMENT
            session()->put('selected_payment_method', $paymentData['type']);

            // PAYMENT_FILTER_WALLET_TOP_UP_PAYMENT_DATA
            $customer_id = $user->id;//auth('customer')->check() ? auth('customer')->id() : null;
            $paymentData['customer_id'] = $customer_id;
            $paymentData = $this->checkoutWalletWithTelr($amount, $paymentData, $request);
//            Log::info('Step7.2Response: $paymentData after checkoutWalletWithTelr in SplitPaymentService', $paymentData);

            if ($checkoutUrl = Arr::get($paymentData, 'checkoutUrl')) {
                DB::commit();
                // will get 'order_ref' and 'url' from $paymentData as $checkoutUrl
                return [
                    'success' => true,
                    'checkoutUrl' => $checkoutUrl,
                ];
            }

            if ($paymentData['error'] || ! $paymentData['charge_id']) {

                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $paymentData['message'] ?: __('Checkout error!')
                ];
            }

            DB::commit();
            return [
                'success' => true,
                'paid_data' => $paymentData,
                'intent_id' => $gatewayResult['id'] ?? null,
                'client_secret' => $gatewayResult['client_secret'] ?? null,
                'gateway' => 'telr',
                'amount' => $amount,
                'status' => $gatewayResult['status'] ?? 'requires_payment_method'
            ];
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gateway payment processing failed:', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function checkoutWalletWithTelr($amount, array $data, Request $request){

//        Log::info('Step7.2.1: checkoutWalletWithTelr in SplitPaymentService');
        if ($data['type'] !== WALLET_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $currentCurrency = $data['currency'] ? Currency::where('title', $data['currency'])->first() : get_application_currency();

        $currencyModel = $currentCurrency->replicate();

        $telrService = $this->walletCheckoutPaymentService;

        $supportedCurrencies = $telrService->supportedCurrencyCodes();

        $currency = strtoupper($currentCurrency->title ?? 'AED');

        $notSupportCurrency = false;

        if (! in_array($currency, $supportedCurrencies)) {
            $notSupportCurrency = true;

            if (! $currencyModel->where('title', 'USD')->exists()) {
                $data['error'] = true;
                $data['message'] = __(
                    ":name doesn't support :currency. List of currencies supported by :name: :currencies.",
                    [
                        'name' => 'Telr',
                        'currency' => $currency,
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]
                );

                return $data;
            }
        }

        $data['amount'] = $amount;
        if ($notSupportCurrency) {
            $usdCurrency = $currencyModel->where('title', 'USD')->first();

            $data['currency'] = 'USD';
            if ($currentCurrency->is_default) {
                $data['amount'] = $amount * $usdCurrency->exchange_rate;
            } else {
                $data['amount'] = format_price(
                    $amount / $currentCurrency->exchange_rate,
                    $currentCurrency,
                    true
                );
            }
        }

//        Log::info('Step7.2.1: checkoutWalletWithTelr in SplitPaymentPGatewayFirstService Data:', $data);
        $result = $this->walletCheckoutPaymentService->execute($data);
//        Log::info('Step7.2.1Response: walletCheckoutPaymentService->execute SplitPaymentService result:',
//            is_array($result) ? $result : ['result' => print_r($result,true)]
//        );

        if ($this->walletCheckoutPaymentService->getErrorMessage()) {
            $data['error'] = true;
            $data['message'] = $this->walletCheckoutPaymentService->getErrorMessage();
        } elseif ($result) {
            // This result contains 'order_ref' and 'url'
            $data['checkoutUrl'] = $result;
        }

        return $data;
    }

    public function releaseFrozenWallet($user, float $amount, $orderId, $freezeTransactionId): array
    {
        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            $freezeTransaction = WalletTransaction::find($freezeTransactionId);

            if (!$freezeTransaction || $freezeTransaction->status !== 'pending') {
                DB::rollBack();
                return ['success' => false, 'message' => 'Invalid freeze transaction'];
            }

            $lotAllocations = $freezeTransaction->lot_allocation ?? [];

            // Release frozen amounts back to lots
            foreach ($lotAllocations as $allocation) {
                $lotFreeze = WalletLotFreeze::find($allocation['freeze_id']);
                if ($lotFreeze) {
                    $lotFreeze->update([
                        'status' => 'released',
                        'released_at' => now(),
                    ]);

                    // Return amount back to lot
                    $lot = WalletLot::find($allocation['lot_id']);
                    if ($lot) {
                        $lot->increment('remaining', $allocation['amount']);

                        // Reactivate lot if it was marked as expired
                        if ($lot->status === 'expired' && $lot->remaining > 0) {
                            $lot->update(['status' => WalletLot::STATUS_ACTIVE]);
                        }
                    }
                }
            }

            // Update freeze transaction to failed
            $freezeTransaction->update([
                'status' => 'failed',
                'description' => "Frozen amount released for failed order #{$orderId}",
            ]);

            // Return frozen amount to available balance
            $wallet->increment('total_available', $amount);
            $wallet->decrement('total_frozen', $amount);

            DB::commit();

//            Log::info('Frozen wallet amount released with lot restoration:', [
//                'amount' => $amount,
//                'user_id' => $user->id,
//                'order_id' => json_encode($orderId)
//            ]);

            return [
                'success' => true,
                'new_balance' => $wallet->total_available
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet release with lot restoration failed:', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function releaseFrozenWalletByOrder($user, $orderId): array
    {
        DB::beginTransaction();
        try {
            // Lock the wallet for update to prevent race conditions
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Wallet not found'];
            }

            // Release only frozen amounts for the specific order
            $frozenLots = WalletLotFreeze::with(['lot', 'transaction'])
                ->where('user_id', $user->id)
                ->where('order_id', $orderId)
                ->where('status', 'frozen')
                ->get();

            if ($frozenLots->isEmpty()) {
                DB::rollBack();
                return ['success' => false, 'message' => 'No frozen lots found for this order'];
            }

            $totalReleasedAmount = 0;
            $processedTransactions = [];
            $processedLots = [];

            foreach ($frozenLots as $lotFreeze) {
                $lotFreeze->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);

                if ($lotFreeze->lot) {
                    $lotFreeze->lot->increment('remaining', $lotFreeze->amount);
                    $totalReleasedAmount += $lotFreeze->amount;
                    $processedLots[] = $lotFreeze->wallet_lot_id;

                    // Reactivate lot if needed
                    if ($lotFreeze->lot->status === 'expired' && $lotFreeze->lot->remaining > 0) {
                        $lotFreeze->lot->update(['status' => WalletLot::STATUS_ACTIVE]);
                    }
                }

                if ($lotFreeze->transaction_id) {
                    $processedTransactions[] = $lotFreeze->transaction_id;
                }
            }

            // Update transactions
            if (!empty($processedTransactions)) {
                WalletTransaction::whereIn('id', $processedTransactions)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'description' => "Frozen amount released for failed order #{$orderId}",
                    ]);
            }

            // Update wallet balances
            $wallet->increment('total_available', $totalReleasedAmount);
            $wallet->decrement('total_frozen', $totalReleasedAmount);
            $wallet->refresh();

            DB::commit();

            return [
                'success' => true,
                'new_balance' => $wallet->total_available,
                'released_amount' => $totalReleasedAmount,
                'released_transactions' => array_unique($processedTransactions),
                'affected_lots' => array_unique($processedLots),
                'message' => 'Frozen amounts for order released successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order wallet release failed:', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function deductFrozenWallet($user, float $amount, $orderId, $freezeTransactionId): array
    {
        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            $freezeTransaction = WalletTransaction::find($freezeTransactionId);

            if (!$freezeTransaction || $freezeTransaction->status !== 'pending') {
                DB::rollBack();
                return ['success' => false, 'message' => 'Invalid freeze transaction'];
            }

            $lotAllocations = $freezeTransaction->lot_allocation ?? [];

            // Update lot freezes to consumed
            foreach ($lotAllocations as $allocation) {
                $lotFreeze = WalletLotFreeze::find($allocation['freeze_id']);
                if ($lotFreeze) {
                    $lotFreeze->update([
                        'status' => 'consumed',
                        'consumed_at' => now(),
                    ]);

                    // Update the actual wallet lot (remaining already decreased during freeze)
                    $lot = WalletLot::find($allocation['lot_id']);
                    if ($lot && $lot->remaining == 0) {
                        $lot->update(['status' => WalletLot::STATUS_EXPIRED]);;
                    }
                }
            }

            // Update freeze transaction to completed
            $freezeTransaction->update([
                'status' => 'completed',
                'description' => "Amount deducted for order #{$orderId}",
            ]);

            // Update wallet balances (remove from frozen)
            $wallet->decrement('total_frozen', $amount);

            DB::commit();

            return [
                'success' => true,
                'new_balance' => (float) $wallet->total_available,
                'transaction' => $freezeTransaction
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet deduction from frozen amount failed:', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function formatSplitPaymentResponse(array $result, array $paymentData): array
    {
        $responseData = [
            'success' => $result['success'],
            'error' => !$result['success'],
            'message' => $result['success'] ? 'Payment processed successfully' : 'Payment failed',
            'type' => 'split_payment',
            'payment_id' => $result['payment_intent_id'] ?? null,
            'charge_id' => null,
            'checkoutUrl' => $result['gateway_amount'] > 0 ? $result['checkoutUrl'] : '',
        ];

        // Add wallet-specific data
        if ($result['wallet_applied'] > 0) {
            $responseData['wallet'] = [
                'applied' => $result['wallet_applied'],
                'transaction_id' => $result['wallet_transaction']['id'] ?? null,
                'new_balance' => $result['new_balance'] ?? null,
            ];
        }

        // Add gateway-specific data
        if ($result['gateway_amount'] > 0 && $result['gateway_result']['success']) {
            $gatewayResult = $result['gateway_result'];

            $responseData['gateway'] = [
                'amount' => $result['gateway_amount'],
                'intent_id' => $gatewayResult['intent_id'] ?? null,
                'gateway' => $gatewayResult['gateway'] ?? 'telr',
            ];

            // If payment requires further action (3D Secure, etc.)
            if (($gatewayResult['status'] ?? '') === 'requires_action') {
                $responseData['checkoutUrl'] = $result['checkoutUrl'];//$this->generateCheckoutUrl($gatewayResult);
                $responseData['message'] = 'Payment requires authentication';
                $responseData['requires_action'] = true;
            } else if (($gatewayResult['status'] ?? '') === 'succeeded') {
                $responseData['charge_id'] = $gatewayResult['intent_id'] ?? null;
                $responseData['message'] = 'Payment completed successfully';
            }
        }

        // If full amount paid by wallet
        if ($result['gateway_amount'] === 0) {
            $responseData['type'] = 'wallet';
            $responseData['message'] = 'Payment completed using wallet';
            $responseData['charge_id'] = $result['wallet_transaction']['id'] ?? null;
        }

        // Add order information
        $responseData['order'] = [
            'id' => $paymentData['order_id'],
            'amount' => $result['total_amount'],
            'currency' => $paymentData['currency'] ?? 'AED',
        ];

        // Add next URL for success
        if ($result['success'] && !isset($responseData['checkoutUrl'])) {
            $responseData['nextUrl'] = '';//$this->getSuccessURL($paymentData['order_id']);
        }

        return $responseData;
    }
}
