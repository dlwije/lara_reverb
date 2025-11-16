<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\GiftCard\Services\GiftCardService;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Services\KYCService;
use Modules\Wallet\Services\WalletLockService;
use Modules\Wallet\Services\WalletService;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService  $walletService,
        public KYCService        $kycService,
        public WalletLockService $lockService,
        public GiftCardService $giftCardService,
    )
    {
    }

    public function index()
    {
        return Inertia::render('wallet/Index', []);
    }

    public function walletStatement()
    {
        return Inertia::render('wallet/statement', []);
    }

    /**
     * Get wallet balance and details
     */
    public function getWallet()
    {
        try {
            $user = auth()->user();

            // Check if wallet is locked
            if ($this->lockService->isWalletFrozen($user->id)) {
                return self::error(
                    'Wallet is temporarily locked. Please contact support.',
                    403,
                    [],
                    ['is_locked' => true]
                );
            }

            $wallet = $this->walletService->getUserWallet($user);

            return self::success($wallet);

        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    public function getLots(Request $request)
    {
        try {

            $user = auth()->user();
            $status = $request->get('status', 'active');
            $perPage = $request->get('per_page', 15);

            $lots = WalletLot::where('user_id', $user->id)
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->orderBy('expires_at', 'asc')
                ->paginate($perPage);

            return self::success($lots);

        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    public function getTransactions(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['period', 'search_input', 'payment_type', 'pay_method', 'from', 'to', 'min', 'max']);
            $perPage = $request->get('per_page', 15);

            $transactions = $this->walletService->getUserTransactions($user, $filters, $perPage);

            return self::success($transactions);

        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    public function getExpiringLots(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['period', 'payment_type', 'from', 'to', 'min', 'max']);
            $perPage = $request->get('per_page', 15);

            $expireTransaction = $this->walletService->getExpiringLots($user);

            return self::success($expireTransaction);

        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }

    }
    public function getWalletSummary()
    {
        try {
            $user = auth()->user();
            $walletSummary = $this->walletService->getWalletSummary($user);
            return self::success($walletSummary);
        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    public function getAvailableBalanceWithLots()
    {
        try {
            $user = auth()->user();
            $walletSummary = $this->walletService->getAvailableBalanceWithLots($user);
            return self::success($walletSummary);
        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Get wallet overview for dashboard
     */
    public function getWalletOverview(Request $request)
    {
        try {
            $user = auth()->user();
            $overview = $this->walletService->getWalletOverview($user);

            return self::success($overview);

        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Get monthly spending statistics
     */
    public function getMonthlyStats(Request $request)
    {
        try {
            $user = auth()->user();
            $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

            $stats = [
                'current_month' => [
                    'spent' => $wallet->current_month_spent,
                    'formatted_spent' => number_format($wallet->current_month_spent, 2) . ' AED',
                    'deposited' => $wallet->current_month_deposited,
                    'formatted_deposited' => number_format($wallet->current_month_deposited, 2) . ' AED',
                    'net_flow' => $wallet->current_month_deposited - $wallet->current_month_spent,
                    'formatted_net_flow' => number_format($wallet->current_month_deposited - $wallet->current_month_spent, 2) . ' AED',
                ],
                'spending_trend' => $wallet->monthly_spending_trend,
            ];

            return self::success($stats);

        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    public function processWalletPayment(Request $request)
    {
        try {

            $request->validate(
                ['order_id' => 'required|exists:ec_orders,id'],
                ['amount' => 'required|numeric|min:0']
            );

            $user = auth()->user();
            $amount = $request->input('amount', 0);
            $orderId = $request->input('order_id');

            if ($amount <= 0) return self::error('Invalid amount', 400);

            // Check KYC requirements
//            $this->kycService->blockIfKycRequired($user, $amount);

            $result = $this->walletService->deductFromWallet($user, $amount, [
                'type' => 'purchase',
                'ref_type' => 'order',
                'ref_id' => $orderId,
                'description' => $request->get('description', 'Purchase')
            ]);

            return self::success($result, 'Payment processed successfully');
        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    /**
     * Split payment (wallet + card)
     */
    public function processSplitPayment(Request $request)
    {
        try {
            $user = auth()->user();
            $totalAmount = $request->input('total_amount', 0);
            $walletAmount = $request->input('wallet_amount', 0);
            $cardAmount = $request->input('card_amount', 0);
            $orderId = $request->input('order_id');

            if ($walletAmount > 0) {
                // Check KYC requirements for wallet portion
//                $this->kycService->blockIfKycRequired($user, $walletAmount);

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
        } catch (\Exception $e) {
            Log::error($e);
            return self::error($e->getMessage(), 422);
        }
    }

    public function redeemGiftCard(Request $request)
    {
        try {

            $request->validate(['code' => 'required|string'], ['otp' => 'sometimes|required|string|max:6']);
            $user = auth()->user();
            $result = $this->giftCardService->redeemGiftCard($user, $request->code, $request->otp);

            return self::success($result, 'Gift Card Redeemed successfully!');
        } catch (\Exception $e) {
            Log::error($e);
            if ($e->getMessage() === 'OTP_REQUIRED') {
                return self::error(
                    'OTP verification required', 422, [],
                    [
                        'otp_sent' => true,
                        'message' => 'OTP has been sent to your registered email/phone',
                        'code' => 'OTP_REQUIRED',
                    ]
                );
            }
            return self::error($e->getMessage(), 422);
        }
    }
}
