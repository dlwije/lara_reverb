<?php

namespace Botble\Wallet\Services;

use Botble\Ecommerce\Models\Currency;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Wallet\Models\Wallet;
use Botble\Wallet\Models\WalletTransaction;
use Botble\Wallet\Services\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SplitPaymentService
{
    public function __construct(
        public WalletService $walletService,
        public WalletCheckoutPaymentService $walletPaymentService,
    ){}

    /** Process Split payment **/
    public function processSplitPayment($user, array $paymentData, Request $request): array
    {
        Log::info('Step7: processSplitPayment in SplitPaymentService');
        Log::info('Split Payment Started:', [
            'user_id' => $user->id,
            'amount' => $paymentData['amount'],
            'order_id' => $paymentData['order_id'],
            'use_wallet' => $paymentData['use_wallet'] ?? false,
            'payment_data' => $paymentData,
        ]);

        return DB::transaction(function () use ($user, $paymentData, $request) {
            $totalAmount = $paymentData['amount'];
            $orderId = $paymentData['order_id'];
            $useWallet = $paymentData['use_wallet'] ?? true;

            $paymentIntent = app(PaymentInterface::class)->create([
                'amount' => $totalAmount,
                'currency' => strtoupper(get_application_currency()->title),
                'charge_id' => null,
                'order_id' => Arr::first($orderId),
                'customer_id' => Arr::get($paymentData, 'customer_id') ? $paymentData['customer_id'] : $user->id,
                'customer_type' => Arr::get($paymentData, 'customer_type') ? $paymentData['customer_type'] : Customer::class,
                'payment_channel' =>'wallet',
                'use_wallet' => $useWallet,
                'status' => PaymentStatusEnum::PENDING,
            ]);

            if ($paymentIntent && isset($paymentIntent->id)) {
                Order::whereIn('id', (array) $orderId)->update([
                    'payment_id' => $paymentIntent->id,
                    'updated_at' => now(),
                ]);

                Log::info('Order payment_id updated successfully', [
                    'order_ids' => $orderId,
                    'payment_id' => $paymentIntent->id,
                ]);
            } else {
                Log::warning('PaymentIntent not created properly, order payment_id not updated', [
                    'order_ids' => $orderId,
                    'paymentIntent' => $paymentIntent,
                ]);
            }

            $paymentData = array_merge($paymentData, [
                'error' => false,
                'message' => 'Purchase deduction successful',
                'amount' => $totalAmount,
                'currency' => strtoupper(get_application_currency()->title),
                'type' => $request->input('payment_method'),
                'charge_id' => null,
                'is_api' => true,
                'wallettracsaction_id' => null,
                'wallet_lot_id' => null,
                'payment_id' => $paymentIntent->id,   // ✅ attach payment id here
                'user_data' => $user,
                'name' => 'Order Purchase Payment'
            ]);

            Log::info('Step7Data: processSplitPayment in SplitPaymentService', $paymentData);

            session()->put('selected_payment_method', $paymentData['type']);

            $walletApplied = 0;
            $gatewayAmount = $totalAmount;
            $walletTransaction = null;
            $walletResult = null;

            // Step 01: try to deduct from the wallet first
            if($useWallet) {
                $walletResult = $this->applyWalletPayment($user, $totalAmount, $orderId, $paymentData);
                Log::info('Step7.1WalletResult: processSplitPayment in SplitPaymentService', $walletResult);
                $walletApplied = $walletResult['amount_used'];
                $gatewayAmount = $totalAmount - $walletApplied;
                $walletTransaction = $walletResult['transaction'] ?? null;

                Log::info('Wallet payment applied:', [
                    'wallet_applied' => $walletApplied,
                    'gateway_amount' => $gatewayAmount,
                    'transaction_id' => $walletTransaction->id ?? null
                ]);

                // Update payment intent with wallet amount
                $paymentIntent->update([
                    'wallet_applied' => $walletApplied,
                    'wallet_lot_allocation' => $walletResult['lot_allocations'] ?? null,
                    'wallet_transaction_id' => $walletTransaction->id ?? null,
//                    'use_wallet' => (float) $walletApplied > 0 ? true : false,
                ]);
            }else{
                Log::warning('Wallet payment failed, proceeding with gateway only:', [
                    'error' => $walletResult['error'] ?? 'Unknown error',
                    'error_message' => $walletResult['error'] ?? 'Wallet payment bypassed. useWallet: disabled, proceeding with gateway only',
                ]);
            }

            // Step 2: Process the remaining amount via payment gateway
            $gatewayResult = null;
            if($gatewayAmount > 0) {
                $gatewayResult = $this->processGatewayPayment($user, $gatewayAmount, $paymentData, $request);
//                Log::info('Step7.2Response: $gatewayResult in SplitPaymentService', $gatewayResult);

                if(!$gatewayResult['success']) {
                    Log::error('Gateway payment failed:', [
                        'gateway_amount' => $gatewayAmount,
                        'error' => $gatewayResult['message']
                    ]);

                    // If gateway fails, refund the wallet amount
                    if ($walletApplied > 0 && $walletTransaction) {
                        $this->refundWalletPayment($user, $walletApplied, $orderId, $walletTransaction->id);
                    }

                    // Update payment intent as failed
                    $paymentIntent->update([
                        'status' => 'failed',
                        'failure_reason' => $gatewayResult['message']
                    ]);

                    throw new \Exception('Gateway payment failed: ' . $gatewayResult['message']);
                }

                Log::info('Gateway payment successful:', [
                    'gateway_amount' => $gatewayAmount,
                    'intent_id' => $gatewayResult['intent_id'] ?? null
                ]);

                // Update payment intent with gateway amount
                $paymentIntent->update([
                    'card_amount' => $gatewayAmount,
                    'charge_id' => $gatewayResult['intent_id'] ?? null,
                    'payment_channel' => $gatewayResult['gateway'] ?? 'telr',
                    'status' => PaymentStatusEnum::COMPLETED,
                ]);
            }else {
                // Full amount paid by wallet
                Log::info('Full amount paid by wallet');
                $paymentIntent->update(['status' => PaymentStatusEnum::COMPLETED]);
            }

            $result = [
                'success' => true,
                'checkoutUrl' => $gatewayResult['checkoutUrl']['url'] ?? null,
                'order_ref' => $gatewayResult['checkoutUrl']['order_ref'] ?? null,
                'payment_intent_id' => $paymentIntent->id,
                'total_amount' => $totalAmount,
                'wallet_applied' => $walletApplied,
                'gateway_amount' => $gatewayAmount,
                'wallet_transaction' => $walletTransaction,
                'new_balance' => $walletResult['new_balance'] ?? 0,
                'gateway_result' => $gatewayResult,
                'payment_intent' => $paymentIntent
            ];

            Log::info('Split payment completed successfully', $result);

            return $this->formatSplitPaymentResponse($result, $paymentData);
        });
    }

    /**
    * Apply wallet payment
     */
    private function applyWalletPayment($user, float $totalAmount, array $orderId, array $paymentData): array
    {
        try {
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet || $wallet->total_available <= 0) {
                return [
                    'success' => false,
                    'amount_used' => 0,
                    'error' => 'No wallet balance available'
                ];
            }

            // Determine how much to deduct from wallet (up to available balance)
            $walletAmount = min($wallet->total_available, $totalAmount);

            // Prepare transaction data for wallet deduction
            $transactionData = [
                'type' => WalletTransaction::TYPE_PURCHASE,
                'ref_type' => Payment::class,
                'ref_id' => $paymentData['payment_id'] ?? null,
                'description' => 'Split payment - wallet portion',
                'order_id' => json_encode($orderId),
                'currency' => $paymentData['currency'] ?? 'AED'
            ];

            // Add additional metadata if provided
            if (isset($paymentData['metadata'])) {
                $transactionData['metadata'] = $paymentData['metadata'];
            }

            // Deduct from wallet using your existing deductFromWallet function
            $result = $this->walletService->deductFromWallet($user, $walletAmount, $transactionData);

            return [
                'success' => true,
                'amount_used' => $walletAmount,
                'transaction' => $result['transaction'],
                'lot_allocations' => $result['lot_allocations'] ?? null,
                'new_balance' => $result['new_balance'] ?? 0,
            ];
        }catch (\Exception $e) {
            Log::error('Wallet payment application failed on SplitPaymentService:', [
                'user_id' => $user->id,
                'amount' => $totalAmount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'amount_used' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function processGatewayPayment($user, float $amount, array $paymentData, $request): array
    {
        try {

            //FILTER_ECOMMERCE_PROCESS_WALLET_TOPUP_PAYMENT
            session()->put('selected_payment_method', $paymentData['type']);

            // PAYMENT_FILTER_WALLET_TOP_UP_PAYMENT_DATA
            $customer_id = auth('customer')->check() ? auth('customer')->id() : null;
            if($customer_id == null) {
                $customer_id = $request->user() ? $request->user()->id : null;
            }

            $paymentData1 = array_merge($paymentData, [
                'amount' => $this->convertOrderAmount((float)$amount),
                'shipping_amount' => 0,
                'shipping_method' => '',
                'tax_amount' => 0,
                'discount_amount' => 0,
                'currency' => $paymentData['currency'] ?? 'AED',
                'type' => $request->input('payment_method'),
                'wallettracsaction_id' => $paymentData['wallettracsaction_id'] ?? null,
                'wallet_lot_id' => $paymentData['wallet_lot_id'] ?? null,
                'payment_id' => $paymentData['payment_id'],
                'description' => 'Wallet purchase #'.$paymentData['wallettracsaction_id'] ?? null,
                'customer_id' => $customer_id,
                'customer_type' => Customer::class,
                'return_url' => PaymentHelper::getCancelURL(),
                'callback_url' => PaymentHelper::getRedirectURL()
            ]);

            Log::info('Step7.2Response: $paymentData1 in SplitPaymentService', $paymentData1);

            $paymentData = $this->checkoutWalletWithTelr($paymentData1, $request);
            Log::info('Step7.2Response: $paymentData after checkoutWalletWithTelr in SplitPaymentService', $paymentData);

            if ($checkoutUrl = Arr::get($paymentData, 'checkoutUrl')) {
                return [
                    'success' => true,
                    'checkoutUrl' => $checkoutUrl,
                ];
            }

            if ($paymentData['error'] || ! $paymentData['charge_id']) {

                return [
                    'success' => false,
                    'message' => $paymentData['message'] ?: __('Checkout error!')
                ];
            }

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

    protected function convertOrderAmount(float $amount): float
    {
        $currentCurrency = get_application_currency();

        if ($currentCurrency->is_default) {
            return $amount;
        }

        return (float)format_price($amount * $currentCurrency->exchange_rate, $currentCurrency, true);
    }

    /** Checkout wallet payment with Telr **/
    public function checkoutWalletWithTelr(array $data, Request $request){

        Log::info('Step7.2.1: checkoutWalletWithTelr in SplitPaymentService');
        if ($data['type'] !== WALLET_PAYMENT_METHOD_NAME) {
            return $data;
        }

        $currentCurrency = $data['currency'] ? Currency::where('title', $data['currency'])->first() : get_application_currency();

        $currencyModel = $currentCurrency->replicate();

        $telrService = $this->walletPaymentService;

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

        if ($notSupportCurrency) {
            $usdCurrency = $currencyModel->where('title', 'USD')->first();

            $data['currency'] = 'USD';
            if ($currentCurrency->is_default) {
                $data['amount'] = $data['amount'] * $usdCurrency->exchange_rate;
            } else {
                $data['amount'] = format_price(
                    $data['amount'] / $currentCurrency->exchange_rate,
                    $currentCurrency,
                    true
                );
            }
        }

        $result = $this->walletPaymentService->execute($data);
        Log::info('Step7.2.1Response: walletPaymentService->execute SplitPaymentService result:',
            is_array($result) ? $result : ['result' => print_r($result,true)]
        );

        if ($this->walletPaymentService->getErrorMessage()) {
            $data['error'] = true;
            $data['message'] = $this->walletPaymentService->getErrorMessage();
        } elseif ($result) {
            $data['checkoutUrl'] = $result;
        }

        return $data;
    }

    /**
     * Format split payment result to match your existing structure
     */
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

    /** Refund wallet payment if gateway fails **/
    private function refundWalletPayment($user, float $amount, string $orderId, int $transactionId): void
    {
        try {
            Log::info('Refunding wallet payment due to gateway failure:', [
                'user_id' => $user->id,
                'amount' => $amount,
                'order_id' => $orderId,
                'transaction_id' => $transactionId
            ]);

            $this->walletService->refundTransaction($transactionId, 'gateway_failure', [
                'order_id' => $orderId,
                'description' => 'Refund due to payment gateway failure'
            ]);

            Log::info('Wallet refund completed successfully');

        } catch (Exception $e) {
            // Log refund failure but don't throw exception
            Log::error('Wallet refund failed:', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Preview split payment breakdown
     */
    public function previewSplitPayment($user, float $totalAmount): array
    {
        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletBalance = $wallet ? $wallet->total_available : 0;

        $walletApplicable = min($walletBalance, $totalAmount);
        $gatewayAmount = $totalAmount - $walletApplicable;

        $breakdown = [
            'total_amount' => $totalAmount,
            'wallet_balance' => $walletBalance,
            'wallet_applicable' => $walletApplicable,
            'gateway_amount' => $gatewayAmount,
            'can_use_wallet' => $walletBalance > 0,
            'breakdown' => [
                'wallet_percentage' => $totalAmount > 0 ? round(($walletApplicable / $totalAmount) * 100, 2) : 0,
                'gateway_percentage' => $totalAmount > 0 ? round(($gatewayAmount / $totalAmount) * 100, 2) : 0
            ]
        ];

        Log::info('Split payment preview:', $breakdown);

        return $breakdown;
    }
}
