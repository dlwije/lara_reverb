<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Wallet\Models\AuditLog;
use Modules\Wallet\Models\KycVerification;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Notifications\KycApprovedNotification;
use Modules\Wallet\Notifications\KycRejectedNotification;
use Modules\Wallet\Notifications\KycSubmittedNotification;

class KYCService
{
    public function getKycTier(User $user): int
    {
        $latestVerification = KycVerification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return $latestVerification?->tier ?? 0;
    }

    public function requiresKycUpgrade(User $user, float $amount): bool
    {
        $currentTier = $this->getKycTier($user);
        $walletBalance = Wallet::where('user_id', $user->id)->value('total_available') ?? 0;

        $tierLimits = [
            0 => 1000, // Tier 0: AED 1,000 max balance
            1 => 5000, // Tier 1: AED 5,000 max balance
            2 => 20000, // Tier 2: AED 20,000 max balance
            3 => 50000, // Tier 3: AED 50,000 max balance (no limit)
        ];

        return ($walletBalance + $amount) > ($tierLimits[$currentTier] ?? 0);
    }

    /**
     * Get required KYC tier for a specific amount
     */
    public function getRequiredTier(float $amount): int
    {
        $tierLimits = config('wallet.kyc_tier_limits', [
            0 => 1000,   // Tier 0: AED 1,000 max balance
            1 => 5000,   // Tier 1: AED 5,000 max balance
            2 => 20000,  // Tier 2: AED 20,000 max balance
            3 => 50000   // Tier 3: AED 50,000 max balance (no limit)
        ]);

        foreach ($tierLimits as $tier => $limit) {
            if ($amount <= $limit) {
                return $tier;
            }
        }

        return 3; // Highest tier
    }

    /**
     * Block transaction if KYC verification is required
     */
    public function blockIfKycRequired(User $user, float $amount): void
    {
        if($this->requiresKycUpgrade($user, $amount))
        {
            $currentTier = $this->getKycTier($user);
            $requiredTier = $this->getRequiredTier($amount);

            throw new \Exception(
                "KYC verification required. " .
                "Current tier: {$currentTier}, " .
                "Required tier: {$requiredTier}. " .
                "Please complete KYC verification to proceed."
            );
        }
    }

    /**
     * Submit KYC verification documents
     */
    public function submitKycVerification(User $user, int $tier, array $documents, array $documentPaths): KycVerification
    {
        return DB::transaction(function () use ($user, $tier, $documents, $documentPaths) {
            // Close any existing draft verification
            KycVerification::where('user_id', $user->id)
                ->where('status', 'draft')
                ->update(['status' => 'cancelled']);

            $verification = KycVerification::create([
                'user_id' => $user->id,
                'tier' => $tier,
                'status' => 'pending',
                'submitted_at' => now(),
                'document_paths' => $documentPaths,
            ]);

            // Notify admins
            $this->notifyAdmins('kyc_submitted', $verification);

            // Log audit trail
            AuditLog::create([
                'actor_type' => User::class,
                'actor_id' => $user->id,
                'event' => 'kyc_submitted',
                'entity_type' => KycVerification::class,
                'entity_id' => $verification->id,
                'after' => json_encode([
                    'tier' => $tier,
                    'document_count' => count($documents)
                ]),
                'ip' => request()->ip()
            ]);

            return $verification;
        });
    }

    /**
     * Approve KYC verification
     */
    public function approveKycVerification(int $verificationId, int $verifiedBy): KycVerification
    {
        return DB::transaction(function () use ($verificationId, $verifiedBy) {
           $verification = KycVerification::findOrFail($verificationId);

           $verification->update([
               'status' => 'approved',
               'verified_by' => $verifiedBy,
               'verified_at' => now(),
           ]);

           //Close any other pending verifications for this user
            KycVerification::where('user_id', $verification->user_id)
                ->where('id', '!=', $verificationId)
               ->where('status', 'pending')
               ->update(['status' => 'cancelled']);

            // Notify user
            $this->notifyUser($verification->user_id,'kyc_approved', $verification);

            // Log audit trail
            AuditLog::create([
                'actor_type' => User::class,
                'actor_id' => $verifiedBy,
                'event' => 'kyc_approved',
                'entity_type' => KycVerification::class,
                'entity_id' => $verificationId,
                'after' => json_encode(['tier' => $verification->tier])
            ]);

            return $verification;
        });
    }

    /**
     * Reject KYC verification
     */
    public function rejectKycVerification(int $verificationId, int $verifiedBy, string $rejectionReason): KycVerification
    {
        return DB::transaction(function () use ($verificationId, $verifiedBy, $rejectionReason) {
            $verification = KycVerification::findOrFail($verificationId);

            $verification->update([
                'status' => 'rejected',
                'verified_by' => $verifiedBy,
                'verified_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            // Notify user
            $this->notifyUser($verification->user_id, 'kyc_rejected', $verification);

            // Log audit trail
            AuditLog::create([
                'actor_type' => User::class,
                'actor_id' => $verifiedBy,
                'event' => 'kyc_rejected',
                'entity_type' => KycVerification::class,
                'entity_id' => $verificationId,
                'after' => json_encode(['tier' => $verification->tier, 'rejection_reason' => $rejectionReason])
            ]);

            return $verification;
        });
    }

    /**
     * Get all KYC verifications with filters
     */
    public function getKycVerifications(array $filters = [], int $perPage = 15)
    {
        $query = KycVerification::query()->with('user', 'verifier');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tier'])) {
            $query->where('tier', $filters['tier']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('submitted_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('submitted_at', '<=', $filters['to_date']);
        }

        if(!empty($filters['search'])) {
            $query->whereHas('user', function ($userQ) use ($filters) {
                $userQ->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Check if user has pending KYC verification
     */
    public function hasKycPending(User $user): bool
    {
        return KycVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Get KYC verification statistics
     */
    public function getKycStates(): array
    {
        return [
            'total' => KycVerification::count(),
            'pending' => KycVerification::where('status', 'pending')->count(),
            'approved' => KycVerification::where('status', 'approved')->count(),
            'rejected' => KycVerification::where('status', 'rejected')->count(),
            'by_tier' => KycVerification::select('tier', DB::raw('count(*) as count'))
            ->groupBy('tier')
            ->get()
            ->pluck('count', 'tier')
            ->toArray(),
            'avg_processing_time' => KycVerification::whereNotNull('verified_at')
            ->whereNotNull('submitted_at')
            ->select(DB::raw('avg(TIMESTAMPDIFF(HOUR, submitted_at, verified_at)) as avg_hours'))
            ->value('avg_hours'),
        ];
    }

    /**
     * Notify administrators about KYC submissions
     */
    private function notifyAdmins(string $type, KycVerification $verification): void
    {
        $adminUsers = User::where('role', 'admin')
            ->orWhere('role', 'compliance')
            ->get();

        Notification::send($adminUsers, new KycSubmittedNotification($verification));
    }

    /**
     * Notify user about KYC status
     */
    private function notifyUser(int $userId, string $type, KycVerification $verification): void
    {
        $user = User::find($userId);

        if($type === 'kyc_approved'){
            $user->notify(new KycApprovedNotification($verification));
        }elseif ($type === 'kyc_rejected') {
            $user->notify(new KycRejectedNotification($verification));
        }
    }

    /**
     * Get document requirements for each KYC tier
     */
    public function getDocumentRequirements(int $tier): array
    {
        $requirements = config('wallet.kyc_document_requirements', [
            0 => ['id_proof'],
            1 => ['id_proof', 'address_proof'],
            2 => ['id_proof', 'address_proof', 'income_proof'],
            3 => ['id_proof', 'address_proof', 'income_proof', 'tax_document'],
        ]);

        return $requirements[$tier] ?? [];
    }

    /**
     * Check if user can upgrade to a specific tier
     */
    public function canUpgradeToTier(User $user, int $targetTier): bool
    {
        $currentTier = $this->getKycTier($user);

        // Can only upgrade one tier at a time
        if ($currentTier > $targetTier + 1) {
            return false;
        }

        // Check if user has any rejected verifications for this tier
        $hasRejected = KycVerification::where('user_id', $user->id)
            ->where('tier', $targetTier)
            ->where('status', 'rejected')
            ->exists();

        return !$hasRejected;
    }

    /**
     * Auto-approve KYC if conditions are met (for lower tiers)
     */
    public function autoApproveIfEligible(KycVerification $verification): bool
    {
        // Only auto-approve for lower tiers
        if ($verification->tier > 1) {
            return false;
        }

        // Check if user has good history (no previous rejections, good transaction history)
        $user = $verification->user;
        $hasGoodHistory = !KycVerification::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->exists();

        if($hasGoodHistory){
            $this->approveKycVerification($verification->id, 0); // 0 for system approval
            return true;
        }

        return false;
    }
}
