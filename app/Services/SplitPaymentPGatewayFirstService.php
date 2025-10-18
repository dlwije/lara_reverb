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

class SplitPaymentPGatewayFirstService
{
    public function __construct(
        public WalletService $walletService,
        public WalletCheckoutPaymentService $walletPaymentService,
    ){}


    public function processSplitPayment($user, array $paymentData, Request $request): array
    {
        Log::info('Step7: processSplitPayment in SplitPaymentService - Gateway First Approach');
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

            $previewSplit = $this->previewSplitPayment($user, $totalAmount);
            $walletBalance = $previewSplit['wallet_balance'];
            $availableLotsAmount = $previewSplit['available_lots_amount'];
            $walletApplicable = $previewSplit['wallet_applicable'];
            $gatewayAmount = $previewSplit['gateway_amount'];
            $canUseWallet = $previewSplit['can_use_wallet'];

            $paymentIntent = $this->createPayment($user, $totalAmount, $orderId, $useWallet, $paymentData);

            $walletTransaction = null;
            $walletApplied = 0;
            $frozenLots = [];

            //FILTER_ECOMMERCE_PROCESS_WALLET_TOPUP_PAYMENT
            session()->put('selected_payment_method', $paymentData['type']);

            try {
                // Step 1: Freeze wallet amount with lot tracking if applicable
                if ($canUseWallet && $useWallet && $walletApplicable > 0) {
                    $freezeResult = $this->freezeWalletAmount($user, $walletApplicable, $orderId, $paymentIntent->id);

                    if (!$freezeResult['success']) {
                        throw new \Exception('Wallet freeze failed: ' . $freezeResult['message']);
                    }

                    $walletTransaction = $freezeResult['transaction'];
                    $frozenLots = $freezeResult['frozen_lots'];
                    $walletApplied = $walletApplicable;

                    Log::info('Wallet amount frozen with lots:', [
                        'amount' => $walletApplied,
                        'transaction_id' => $walletTransaction->id,
                        'frozen_lots_count' => count($frozenLots)
                    ]);
                }

                // Step 2: Process gateway payment first
                $gatewayResult = null;
                if ($gatewayAmount > 0) {
                    $gatewayResult = $this->processGatewayPayment($user, $gatewayAmount, $paymentData, $request);

                    if (!$gatewayResult['success']) {
                        Log::error('Gateway payment failed:', [
                            'gateway_amount' => $gatewayAmount,
                            'error' => $gatewayResult['message']
                        ]);

                        // If gateway fails, release frozen wallet amount and lots
                        if ($walletApplied > 0 && $walletTransaction) {
                            $this->releaseFrozenWallet($user, $walletApplied, $orderId, $walletTransaction->id);
                        }

                        // Update payment intent as failed
                        $paymentIntent->update([
                            'status' => PaymentStatusEnum::FAILED,
                            'failure_reason' => $gatewayResult['message']
                        ]);

                        throw new \Exception('Gateway payment failed: ' . $gatewayResult['message']);
                    }

                    Log::info('Gateway payment successful:', [
                        'gateway_amount' => $gatewayAmount,
                        'intent_id' => $gatewayResult['intent_id'] ?? null
                    ]);
                }
            }catch (\Exception $e) {

            }
        });
    }

    private function previewSplitPayment($user, float $totalAmount): array
    {
        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletBalance = $wallet ? $wallet->total_available : 0;

        // Calculate available amount from non-expired lots
        $availableLotsAmount = WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->sum('remaining');

        $walletApplicable = min($availableLotsAmount, $totalAmount);
        $gatewayAmount = $totalAmount - $walletApplicable;

        $breakdown = [
            'total_amount' => $totalAmount,
            'wallet_balance' => $walletBalance,
            'available_lots_amount' => $availableLotsAmount,
            'wallet_applicable' => $walletApplicable,
            'gateway_amount' => $gatewayAmount,
            'can_use_wallet' => $availableLotsAmount > 0,
            'breakdown' => [
                'wallet_percentage' => $totalAmount > 0 ? round(($walletApplicable / $totalAmount) * 100, 2) : 0,
                'gateway_percentage' => $totalAmount > 0 ? round(($gatewayAmount / $totalAmount) * 100, 2) : 0
            ]
        ];

        Log::info('Split payment preview with lot consideration:', $breakdown);

        return $breakdown;
    }

    private function createPayment($user, $totalAmount, $orderId, $useWallet, $paymentData){

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
        return $paymentIntent;
    }

    /**
     * Freeze wallet amount with lot tracking
     */
    private function freezeWalletAmount($user, float $amount, $orderId, $paymentIntentId): array
    {
        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet || $wallet->total_available < $amount) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
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

            $amountToFreeze = $amount;
            $lotAllocations = [];
            $frozenLots = [];

            foreach ($availableLots as $lot) {
                if ($amountToFreeze <= 0) break;

                $allocatedAmount = min($lot->remaining, $amountToFreeze);

                // Create lot freeze record
                $lotFreeze = WalletLotFreeze::create([
                    'wallet_lot_id' => $lot->id,
                    'user_id' => $user->id,
                    'order_id' => $orderId,
                    'payment_intent_id' => $paymentIntentId,
                    'amount' => $allocatedAmount,
                    'status' => 'frozen',
                    'expires_at' => now()->addMinutes(15), // Freeze expiry (e.g., 15 minutes, 24 hours)
                ]);

                // Update lot remaining
                $lot->decrement('remaining', $allocatedAmount);

                $lotAllocations[] = [
                    'lot_id' => $lot->id,
                    'amount' => $allocatedAmount,
                    'freeze_id' => $lotFreeze->id,
                    'source' => $lot->source,
                    'base_value' => $amount, //$this->calculateProportionalAmount($lot->base_value, $lot->amount, $allocatedAmount),
                    'bonus_value' => 0, //$this->calculateProportionalAmount($lot->bonus_value, $lot->amount, $allocatedAmount),
                ];

                $frozenLots[] = $lotFreeze;
                $amountToFreeze -= $allocatedAmount;
            }

            if ($amountToFreeze > 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Insufficient wallet balance after lot allocation'
                ];
            }

            // Create wallet transaction for freeze
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'direction' => 'DR',
                'amount' => $amount,
                'currency' => 'AED',
                'type' => 'purchase',
                'status' => 'pending', // Will be completed after gateway success
                'ref_type' => 'order',
                'ref_id' => $orderId,
                'lot_allocation' => $lotAllocations,
                'description' => "Amount frozen for order #{$orderId}",
            ]);

            // Update wallet balances
            $wallet->decrement('total_available', $amount);
            $wallet->increment('total_frozen', $amount);

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
                'frozen_lots' => $frozenLots,
                'new_balance' => $wallet->total_available
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet freeze with lots failed:', [
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

    private function processGatewayPayment($user, float $amount, array $paymentData, $request): array
    {
        DB::beginTransaction();
        try {

            // PAYMENT_FILTER_WALLET_TOP_UP_PAYMENT_DATA
            $customer_id = $user->id;//auth('customer')->check() ? auth('customer')->id() : null;

            $paymentData = $this->checkoutWalletWithTelr($paymentData, $request);
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

    private function releaseFrozenWallet($user, float $amount, $orderId, $freezeTransactionId): array
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
                            $lot->update(['status' => 'active']);
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

            Log::info('Frozen wallet amount released with lot restoration:', [
                'amount' => $amount,
                'user_id' => $user->id,
                'order_id' => $orderId
            ]);

            return [
                'success' => true,
                'new_balance' => $wallet->total_available
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet release with lot restoration failed:', [
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
                        $lot->update(['status' => 'expired']);
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
                'new_balance' => $wallet->total_available,
                'transaction' => $freezeTransaction
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet deduction from frozen amount failed:', [
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
}
