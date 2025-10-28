<?php

namespace Modules\GiftCard\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\GiftCard\Models\GiftCard;
use Modules\PromoRules\Models\GiftCardBatch;
use Modules\PromoRules\Models\PromoRule;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Services\KYCService;
use Modules\Wallet\Services\WalletService;

class GiftCardService
{
    public function __construct(
        public WalletService $walletService,
        public KYCService $kycService,
        public NotificationService $notificationService,
//        public OTPService $otpService,
    ) {}
    /**
     * Redeem a gift card
     */
    public function redeemGiftCard($user, string $code, ?string $otp = null, array $sessionData = []) {
        return DB::transaction(function () use ($user, $code, $otp, $sessionData) {
            // Validate gift card
            $giftCard = Discount::query()
                ->where('code', $code)
                ->where(function (Builder $query) {
                    $query->where('type', DiscountTypeEnum::GIFT_CARD);
                })
                ->where('start_date', '<=', Carbon::now())
                ->where(function (Builder $query) use ($sessionData) {
                    $query
                        ->where(function (Builder $sub) {
                            return $sub
                                ->whereIn('type_option', [DiscountTypeOptionEnum::AMOUNT, DiscountTypeOptionEnum::PERCENTAGE])
                                ->where(function (Builder $subSub) {
                                    return $subSub
                                        ->whereNull('end_date')
                                        ->orWhere('end_date', '>=', Carbon::now());
                                });
                        });
                })
                ->lockForUpdate()
                ->first();

//            print_r($giftCard->toArray());exit('sd');

            if(!$giftCard){
                throw new \Exception(__('plugins/wallet::wallet.gift_card_not_found_invalid_gift_card_code'));
            }

            // Ensure it's a gift card
            if ($giftCard->type !== 'gift-card') {
                throw new \Exception(__('Gift card is not a gift card.'));
            }
            if ($giftCard->status == Discount::STATUS_REDEEMED) {
                throw new \Exception(__('plugins/wallet::wallet.gift_card_has_already_been_redeemed'));
            }

            if ($giftCard->quantity !== null && $giftCard->total_used >= $giftCard->quantity) {
                $giftCard->update(['status' => Discount::STATUS_EXPIRED]);
                throw new \Exception(__('plugins/wallet::wallet.gift_card_has_expired'));
            }
            // ✅ Update usage count
            $giftCard->total_used = ($giftCard->total_used ?? 0) + 1;
            $giftCard->save();

            $finalCredit = str_replace(',', '', $this->calculateFinalCredit($giftCard, $user));
            // Check if OTP is required
//            $requiresOtp = $this->otpService->isOtpRequired($user, $finalCredit);
//
//            if ($requiresOtp) {
//                if (!$otp) {
//                    // Generate and send OTP
//                    $this->otpService->generateAndSendOtp($user, 'gift_card_redeem');
//                    throw new \Exception('OTP_REQUIRED');
//                }
//
//                // Verify OTP
//                if (!$this->otpService->verifyOtp($user, $otp, 'gift_card_redeem')) {
//                    throw new \Exception('Invalid or expired OTP');
//                }
//            }

            // Check KYC requirements
//            $this->kycService->blockIfKycRequired($user, $giftCard->final_credit);

            //Apply promo multiplier if available
            $bonusValue = $finalCredit - str_replace(',', '', $giftCard->value);

            // Add funds to wallet
            $result = $this->addToWallet(
                $user,
                str_replace(',', '', $finalCredit),
                'gift_card',
                str_replace(',','',$giftCard->value),
                str_replace(',', '', $bonusValue),
                $giftCard->id
            );

            // Mark the gift card as redeemed
            $giftCard->update([
                'status' => Discount::STATUS_REDEEMED,
                'redeemed_by' => $user->id,
                'redeemed_at' => now(),
                'final_credit' => str_replace(',', '', $finalCredit),
                'bonus_value' => str_replace(',', '', $bonusValue),
            ]);

            return array_merge($result, [
                'gift_card' => $giftCard,
                'base_value' => $giftCard->original_value,
                'bonus_value' => $bonusValue,
                'total_credit' => $finalCredit,
//                'otp_required' => $requiresOtp
            ]);
        });
    }

    /**
     * Add funds to wallet with proper lot creation
     */
    public function addToWallet($user, float $amount, string $source, float $baseValue, float $bonusValue, ?int $giftCardId = null, string $currency = 'AED', ?int $promoRuleId = null)
    {
        $result = DB::transaction(function () use ($user, $amount, $source, $baseValue, $bonusValue, $giftCardId, $currency, $promoRuleId) {
            $walletLot = WalletLot::create([
                'user_id' => $user->id,
                'source' => $source,
                'amount' => $amount,
                'base_value' => $baseValue,
                'bonus_value' => $bonusValue,
                'remaining' => $amount,
                'currency' => $currency,
                'acquired_at' => now(),
                'expires_at' => now()->addDays(360),
                'status' => 'active',
                'gift_card_id' => $giftCardId,
                'promo_rule_id' => $promoRuleId,
            ]);

            // Create a transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'direction' => 'CR',
                'amount' => $amount,
                'base_value' => $baseValue,
                'bonus_value' => $bonusValue,
                'currency' => $currency,
                'type' => 'gift_card_redeem',
                'status' => 'completed',
                'ref_type' => Discount::class,
                'ref_id' => $giftCardId,
                'gift_card_id' => $giftCardId,
                'promo_rule_id' => $promoRuleId,
                'lot_allocation' => [['lot_id' => $walletLot->id, 'amount' => $amount]],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            $walletLot->update(['transaction_id' => $transaction->id]);
            // Update wallet balance
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['total_available' => 0, 'total_pending' => 0, 'status' => 'active']
            );

            $wallet->increment('total_available', $amount);

            return [
                'lot' => $walletLot,
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->total_available,
            ];
        });

        // Send notification after transaction is committed
        $this->notificationService->sendTransactionNotification($result['transaction']);
        return $result;
    }

    /**
     * Calculate final credit with promo multipliers
     */
    public function calculateFinalCredit($giftCard, $user): float
    {
        $baseValue = str_replace(',', '', $giftCard->value);

        //Check for gift card specific multiplier first
        if($giftCard->promo_rule_id){
            $multiplier = $giftCard->promoRule->multiplier;
            return $baseValue * $multiplier;
        }

        //Check for applicable promo rules
        $applicableRule = '';//$this->findApplicablePromoRule($user, $baseValue);
        if($applicableRule) {
            return $baseValue * $applicableRule->multiplier;
        }

        return $baseValue;
    }

    /**
     * Find applicable promo rule for user
     */
    private function findApplicablePromoRule(User $user, float $amount)
    {
        // Implementation to find matching promo rules based on:
        // - User segments
        // - Date ranges
        // - Minimum amount conditions
        // - etc.

//        return PromoRule::where('is_active', true)
//            ->where('start_date', '<=', now())
//            ->where('end_date', '>=', now())
//            ->orderBy('multiplier', 'desc')
//            ->first();
    }

    /**
     * Validate gift card without redeeming
     */
    public function validateGiftCard(string $code): array
    {
        $giftCard = Discount::where('code', $code)->first();

        if(!$giftCard){
            throw new \Exception(__('Gift card not found. Invalid gift card code.'));
        }

        return [
            'valid' => $giftCard->isActive(),
            'gift_card' => $giftCard,
            'status' => $giftCard->status,
            'is_expired' => $giftCard->isExpired(),
            'is_redeemed' => $giftCard->isRedeemed(),
            'original_value' => $giftCard->original_value,
            'final_credit' => $giftCard->final_credit ?? $giftCard->original_value,
            'expires_at' => $giftCard->expires_at,
            'days_until_expiry' => $giftCard->daysUntilExpiry(),
        ];
    }

    /**
     * Preview gift card redemption
     */
    public function previewRedemption(User $user, string $code): array
    {
        $validation = $this->validateGiftCard($code);

        if(!$validation['valid']){
            throw new \Exception(__('Gift card is not valid for redemption.'));
        }

        $giftCard = $validation['gift_card'];
        $finalCredit = $this->calculateFinalCredit($giftCard, $user);
        $bonusValue = $finalCredit - $giftCard->original_value;
        $wallet_available = Wallet::where('user_id', $user->id)->value('total_available');

        $requiresOtp = $this->otpService->isOtpRequired($user, $finalCredit);

        return [
            'gift_card' => $giftCard,
            'original_value' => $giftCard->original_value,
            'final_credit' => $finalCredit,
            'bonus_value' => $bonusValue,
            'bonus_percentage' => $bonusValue > 0 ? round(($bonusValue / $giftCard->original_value) * 100, 2) : 0,
            'requires_otp' => $requiresOtp,
            'otp_threshold' => config('giftcard.otp.amount_threshold', 1000),
            'expires_at' => $giftCard->expires_at,
            'will_expire' => now()->addDays(360)->toISOString(),
            'current_balance' => $wallet_available ?? 0,
            'new_balance' => ($wallet_available ?? 0) + $finalCredit,
        ];
    }
    /**
     * Generate unique gift card code
     */
    public function generateUniqueCode(?string $prefix = null, int $length = 32): string
    {
        $prefix = $prefix ? strtoupper($prefix) : 'GCE-';
        $code = '';

        do {
            $random = Str::random($length - strlen($prefix));
            $code = $prefix . $random;
        } while (Discount::where('code', $code)->exists());

        return $code;
    }

    /**
     * Update gift card
     */
    public function updateGiftCard(int $id, array $data)
    {
        $giftCard = Discount::findOrFail($id);

        if($giftCard->isRedeemed()){
            throw new \Exception(__('Gift card has already been redeemed! Cannot Update it.'));
        }

        return DB::transaction(function () use ($giftCard, $data) {
            if(isset($data['promo_rule_id']) || isset($data['original_value'])){
                $originalValue = $data['original_value'] ?? $giftCard->original_value;
                $promoRuleId = $data['promo_rule_id'] ?? $giftCard->promo_rule_id;

                if($promoRuleId){
//                    $promoRule = PromoRule::find($promoRuleId);
//                    $data['final_credit'] = $originalValue * $promoRule->multiplier;
//                    $data['bonus_value'] = $data['final_credit'] - $originalValue;
                }else {
                    $data['final_credit'] = $originalValue;
                    $data['bonus_value'] = 0;
                }
            }
            $giftCard->update($data);
            return $giftCard->fresh();
        });
    }

    /**
     * Delete gift card
     */
    public function deleteGiftCard(int $id): void
    {
        $giftCard = Discount::findOrFail($id);

        if($giftCard->isRedeemed()){
            throw new \Exception(__('Gift card has already been redeemed! Cannot Delete it.'));
        }

        $giftCard->delete();
    }

    /**
     * Change gift card status
     */
    public function changeStatus(int $id, string $status)
    {
        $giftCard = GiftCard::findOrFail($id);

        if($giftCard->isRedeemed() && $status !== 'void'){
            throw new \Exception('Redeemed gift cards can only be voided');
        }

        $giftCard->update(['status' => $status]);

        return $giftCard->fresh();
    }

    /**
     * Get filtered gift cards
     */
    public function getGiftCards(array $filters, int $perPage = 15)
    {
        $query = GiftCard::with(['batch', 'promoRule', 'redeemedBy']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if(!empty($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        if(!empty($filters['redeemed_by'])) {
            $query->where('redeemed_by', $filters['redeemed_by']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
