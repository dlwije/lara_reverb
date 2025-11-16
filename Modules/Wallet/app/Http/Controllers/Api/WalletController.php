<?php

namespace Modules\Wallet\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\GiftCard\Services\GiftCardService;
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
        public GiftCardService   $giftCardService,
    )
    {
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
                return self::errorCustom(
                    trans('plugins/wallet::wallet.wallet_is_temporarily_locked_please_contact_support'),
                    403,
                    [],
                    ['is_locked' => true]
                );
            }

            $wallet = $this->walletService->getUserWallet($user);

            return self::successCustom($wallet);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
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

            return self::successCustom($lots);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }
    }

    public function getTransactions(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['period', 'search_input', 'payment_type', 'pay_method', 'from', 'to', 'min', 'max']);
            $perPage = $request->get('per_page', 15);
            Log::info('filters: ',$filters);

            $transactions = $this->walletService->getUserTransactions($user, $filters, $perPage);

            return self::successCustom($transactions);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }
    }

    public function getExpiringLots(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['period', 'payment_type', 'from', 'to', 'min', 'max']);
            $perPage = $request->get('per_page', 15);

            $expireTransaction = $this->walletService->getExpiringLots($user);

            return self::successCustom($expireTransaction);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }

    }
    public function getWalletSummary()
    {
        try {
            $user = auth()->user();
            $walletSummary = $this->walletService->getWalletSummary($user);
            return self::successCustom($walletSummary);
        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }
    }

    public function getAvailableBalanceWithLots()
    {
        try {
            $user = auth()->user();
            $walletSummary = $this->walletService->getAvailableBalanceWithLots($user);
            return self::successCustom($walletSummary);
        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
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

            return self::successCustom($overview);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
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

            return self::successCustom($stats);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
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

            if ($amount <= 0) return self::errorCustom(trans('plugins/wallet::wallet.invalid_amount'), 400);

            // Check KYC requirements
//            $this->kycService->blockIfKycRequired($user, $amount);

            $result = $this->walletService->deductFromWallet($user, $amount, [
                'type' => 'purchase',
                'ref_type' => 'order',
                'ref_id' => $orderId,
                'description' => $request->get('description', 'Purchase')
            ]);

            return self::successCustom($result, trans('plugins/wallet::wallet.payment_processed_successfully'));
        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }
    }

    public function redeemGiftCard(Request $request)
    {
        try {

            $request->validate(['code' => 'required|string'], ['otp' => 'sometimes|required|string|max:6']);
            $user = auth()->user();
            $result = $this->giftCardService->redeemGiftCard($user, $request->code, $request->otp);

            return self::successCustom($result, trans('plugins/wallet::wallet.gift_card_redeemed_successfully'));
        } catch (\Exception $e) {
            Log::error($e);
            if ($e->getMessage() === 'OTP_REQUIRED') {
                return self::errorCustom(
                    'plugins/wallet::wallet.otp_verification_required', 422, [],
                    [
                        'otp_sent' => true,
                        'message' => 'OTP has been sent to your registered email/phone',
                        'code' => 'OTP_REQUIRED',
                    ]
                );
            }
            return self::errorCustom($e->getMessage(), 422);
        }
    }

    public function releaseFrozenWalletByOrder(Request $request)
    {
        try {
            $request->validate(['order_id' => 'required|string']);

            $user = auth()->user();
            $result = app(SplitPaymentPGatewayFirstService::class)->releaseFrozenAmountByOrder($user, $request->order_id);
            return self::successCustom($result, trans('plugins/wallet::wallet.gift_card_redeemed_successfully'));
        }catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }
    }
    public function exportTransactions(Request $request)
    {
        try {
            $user = auth()->user();
            $filters = $request->only(['period', 'search_input', 'payment_type', 'pay_method', 'from', 'to', 'min', 'max']);
            if(empty($filters)){
                $filters = ['period' => 'current_month'];
            }

            $transactions = $this->walletService->exportTransactions($user, $filters);

//            return $transactions;
            return self::successCustom($transactions);

        } catch (\Exception $e) {
            Log::error($e);
            return self::errorCustom($e->getMessage(), 422);
        }
    }
    public function walletPostCheckout(
        string                       $token,
        CheckoutRequest              $request,
        HandleShippingFeeService     $shippingFeeService,
        HandleApplyCouponService     $applyCouponService,
        HandleRemoveCouponService    $removeCouponService,
        HandleApplyPromotionsService $handleApplyPromotionsService
    )
    {

    }
}
