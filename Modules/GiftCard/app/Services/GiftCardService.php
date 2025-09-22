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
        public OTPService $otpService,
    ) {}
    /**
     * Redeem a gift card
     */
    public function redeemGiftCard(User $user, string $code, ?string $otp = null) {
        return DB::transaction(function () use ($user, $code, $otp) {
            // Validate gift card
            $giftCard = GiftCard::where('code', $code)->where('status', 'active')->first();

            if(!$giftCard){
                throw new \Exception(__('Gift card not found. Invalid gift card code.'));
            }

            if($giftCard->isExpired()) {
                throw new \Exception(__('Gift card has expired'));
            }

            if($giftCard->isRedeemed()) {
                throw new \Exception(__('Gift card has already been redeemed'));
            }

            $finalCredit = $this->calculateFinalCredit($giftCard, $user);
            // Check if OTP is required
            $requiresOtp = $this->otpService->isOtpRequired($user, $finalCredit);

            if ($requiresOtp) {
                if (!$otp) {
                    // Generate and send OTP
                    $this->otpService->generateAndSendOtp($user, 'gift_card_redeem');
                    throw new \Exception('OTP_REQUIRED');
                }

                // Verify OTP
                if (!$this->otpService->verifyOtp($user, $otp, 'gift_card_redeem')) {
                    throw new \Exception('Invalid or expired OTP');
                }
            }

            // Check KYC requirements
//            $this->kycService->blockIfKycRequired($user, $giftCard->final_credit);

            //Apply promo multiplier if available
            $bonusValue = $finalCredit - $giftCard->original_value;

            // Add funds to wallet
            $result = $this->addToWallet(
                $user,
                $finalCredit,
                'gift_card',
                $giftCard->original_value,
                $bonusValue,
                $giftCard->id,
                $giftCard->currency,
                $giftCard->promo_rule_id
            );

            // Mark the gift card as redeemed
            $giftCard->update([
                'status' => 'redeemed',
                'redeemed_by' => $user->id,
                'redeemed_at' => now(),
                'final_credit' => $finalCredit,
                'bonus_value' => $bonusValue,
            ]);

            return array_merge($result, [
                'gift_card' => $giftCard,
                'base_value' => $giftCard->original_value,
                'bonus_value' => $bonusValue,
                'total_credit' => $finalCredit,
                'otp_required' => $requiresOtp
            ]);
        });
    }

    /**
     * Add funds to wallet with proper lot creation
     */
    public function addToWallet(User $user, float $amount, string $source, float $baseValue, float $bonusValue, ?int $giftCardId = null, string $currency = 'AED', ?int $promoRuleId = null)
    {
        return DB::transaction(function () use ($user, $amount, $source, $baseValue, $bonusValue, $giftCardId, $currency, $promoRuleId) {
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
                'ref_type' => GiftCard::class,
                'ref_id' => $giftCardId,
                'gift_card_id' => $giftCardId,
                'promo_rule_id' => $promoRuleId,
                'lot_allocation' => [['lot_id' => $walletLot->id, 'amount' => $amount]],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

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
    }

    /**
     * Calculate final credit with promo multipliers
     */
    public function calculateFinalCredit(GiftCard $giftCard, User $user): float
    {
        $baseValue = $giftCard->original_value;

        //Check for gift card specific multiplier first
        if($giftCard->promo_rule_id){
            $multiplier = $giftCard->promoRule->multiplier;
            return $baseValue * $multiplier;
        }

        //Check for applicable promo rules
        $applicableRule = $this->findApplicablePromoRule($user, $baseValue);
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

        return PromoRule::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('multiplier', 'desc')
            ->first();
    }

    /**
     * Bulk redeem gift cards (admin function)
     */
    public function bulkRedeemGiftCards(array $codes, int $userId): array
    {
        $results = [];

        foreach ($codes as $code) {
            try {
                $user = User::findOrFail($userId);
                $result = $this->redeemGiftCard($user, $code);
                $results[] = [
                    'code' => $code,
                    'success' => true,
                    'data' => $result,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'code' => $code,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        return $results;
    }

    /**
     * Validate gift card without redeeming
     */
    public function validateGiftCard(string $code): array
    {
        $giftCard = GiftCard::where('code', $code)->first();

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
     * Create a single gift card
     */
    public function createGiftCard(array $data): GiftCard
    {
        return DB::transaction(function () use ($data) {
            $data['code'] = $data['code'] ?? $this->generateUniqueCode();
            $data['base_value'] = $data['original_value'] ?? 0;

            // Calculate final credit if promo rule is provided
            if(!empty($data['promo_rule_id'])){
                $promoRule = PromoRule::find($data['promo_rule_id']);
                $data['final_credit'] = $data['original_value'] * $promoRule->multiplier;
                $data['bonus_value'] = $data['final_credit'] - $data['original_value'];
            }else{
                $data['final_credit'] = $data['original_value'];
                $data['bonus_value'] = 0;
            }

            return GiftCard::create($data);
        });
    }

    /**
     * Create gift cards from CSV upload
     */
    public function createGiftCardsFromCsv($file, string $batchName, float $value, string $expiresAt, ?int $promoRuleId = null): GiftCardBatch
    {
        return DB::transaction(function () use ($file, $batchName, $value, $expiresAt, $promoRuleId) {
            // Create Batch
            $batch = GiftCardBatch::create([
                'name' => $batchName,
                'batch_code' => Str::uuid(),
                'original_value' => $value,
                'expires_at' => $expiresAt,
                'promo_rule_id' => $promoRuleId,
                'status' => 'draft',
            ]);

            // Process CSV
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();

            $giftCards = [];
            foreach ($records as $record) {
                $code = $record['code'] ?? $this->generateUniqueCode();
                $issuedTo = $record['email'] ?? $record['issued_to'] ?? null;

                $giftCards[] = [
                    'code' => $code,
                    'original_value' => $value,
                    'base_value' => $value,
                    'final_credit' => $promoRuleId ? $value * PromoRule::find($promoRuleId)->multiplier : $value,
                    'bonus_value' => $promoRuleId ? ($value * PromoRule::find($promoRuleId)->multiplier) - $value : 0,
                    'currency' => 'AED',
                    'batch_id' => $batch->id,
                    'promo_rule_id' => $promoRuleId,
                    'issued_to' => $issuedTo,
                    'expires_at' => $expiresAt,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Bulk insert
            GiftCard::insert($giftCards);

            // Update batch quantity
            $batch->update(['quantity' => count($giftCards)]);

            return $batch->load('giftCards');
        });
    }

    /**
     * Generate unique gift card codes for a batch
     */
    public function generateBatchGiftCards(int $batchId, int $quantity, ?string $prefix = null, int $codeLength = 12): GiftCardBatch
    {
        return DB::transaction(function () use ($batchId, $quantity, $prefix, $codeLength){
            $batch = GiftCardBatch::findOrFail($batchId);

            $giftCards = [];
            for ($i = 0; $i < $quantity; $i++) {
                $giftCards[] = [
                    'code' => $this->generateUniqueCode($prefix, $codeLength),
                    'original_value' => $batch->original_value,
                    'base_value' => $batch->original_value,
                    'final_credit' => $batch->promo_rule_id ? $batch->original_value * $batch->promoRule->multiplier : $batch->original_value,
                    'bonus_value' => $batch->promo_rule_id ? ($batch->original_value * $batch->promoRule->multiplier) - $batch->original_value : 0,
                    'currency' => 'AED',
                    'batch_id' => $batchId,
                    'promo_rule_id' => $batch->promo_rule_id,
                    'expires_at' => $batch->expires_at,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            GiftCard::insert($giftCards);
            $batch->increment('quantity', $quantity);

            return $batch->fresh();
        });
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
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }

    /**
     * Update gift card
     */
    public function updateGiftCard(int $id, array $data): GiftCard
    {
        $giftCard = GiftCard::findOrFail($id);

        if($giftCard->isRedeemed()){
            throw new \Exception(__('Gift card has already been redeemed! Cannot Update it.'));
        }

        return DB::transaction(function () use ($giftCard, $data) {
            if(isset($data['promo_rule_id']) || isset($data['original_value'])){
                $originalValue = $data['original_value'] ?? $giftCard->original_value;
                $promoRuleId = $data['promo_rule_id'] ?? $giftCard->promo_rule_id;

                if($promoRuleId){
                    $promoRule = PromoRule::find($promoRuleId);
                    $data['final_credit'] = $originalValue * $promoRule->multiplier;
                    $data['bonus_value'] = $data['final_credit'] - $originalValue;
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
        $giftCard = GiftCard::findOrFail($id);

        if($giftCard->isRedeemed()){
            throw new \Exception(__('Gift card has already been redeemed! Cannot Delete it.'));
        }

        $giftCard->delete();
    }

    /**
     * Change gift card status
     */
    public function changeStatus(int $id, string $status): GiftCard
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

    /**
     * Export gift cards as CSV
     */
    public function exportGiftCards(array $filters)
    {
        $giftCards = $this->getGiftCards($filters, 10000); // Large limit for export

        $filename = "gift_cards_export_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($giftCards) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Code', 'Original Value', 'Final Credit', 'Bonus Value',
                'Currency', 'Status', 'Batch', 'Issued To', 'Redeemed By',
                'Redeemed At', 'Expires At', 'Created At'
            ]);

            foreach ($giftCards as $card) {
                fputcsv($file, [
                    $card->code,
                    $card->original_value,
                    $card->final_credit,
                    $card->bonus_value,
                    $card->currency,
                    $card->status,
                    $card->batch->name ?? 'N/A',
                    $card->issued_to,
                    $card->redeemedBy->email ?? 'N/A',
                    $card->redeemed_at?->format('Y-m-d H:i:s'),
                    $card->expires_at->format('Y-m-d'),
                    $card->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
