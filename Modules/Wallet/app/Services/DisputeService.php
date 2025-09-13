<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Wallet\Models\AuditLog;
use Modules\Wallet\Models\Dispute;
use Modules\Wallet\Models\DisputeEvidence;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Models\WalletTransaction;

class DisputeService
{
    public function createDispute(int $userId, int $transactionId, string $reason, array $evidence = []): Dispute
    {
        return DB::transaction(function () use ($userId, $transactionId, $reason, $evidence) {
            $dispute = Dispute::create([
                'user_id' => $userId,
                'transaction_id' => $transactionId,
                'reason' => $reason,
                'status' => 'open',
                'priority' => 'medium',
            ]);

            // Store evidence files
            foreach ($evidence as $file) {
                DisputeEvidence::create([
                    'dispute_id' => $dispute->id,
                    'file_path' => $file->store('disputes/evidence', 'public'),
                    'file_name' => $file->getClientOriginalName(),
                    'description' => $file->getClientOriginalName(),
                ]);
            }
//            $dispute->evidence()->createMany($evidence);
            // Freeze related funds if needed
            $transaction = WalletTransaction::find($transactionId);
            if($transaction->direction == 'DR'){
                $this->freezeRelatedFunds($transaction);
            }

            // Notify admin team
            $this->notifyAdmins('new_dispute',$dispute);

            // Log audit trail
            AuditLog::create([
                'actor_type' => User::class,
                'actor_id' => $userId,
                'event' => 'dispute_created',
                'entity_type' => Dispute::class,
                'entity_id' => $dispute->id,
                'after' => json_encode([
                    'reason' => $reason,
                    'transaction_id' => $transactionId,
                ]),
                'ip' => request()->ip(),
            ]);
            return $dispute;
        });
    }

    /**
     * Freeze funds related to a disputed transaction
     */
    private function freezeRelatedFunds(WalletTransaction $transaction): void
    {
        if($transaction->lot_allocation){
            $lotAllocations = json_decode($transaction->lot_allocation, true);

            foreach ($lotAllocations as $allocation) {
                WalletLot::where('id', $allocation['lot_id'])->where('status', 'active')->update(['status' => 'locked']);
            }

            AuditLog::create([
                'actor_type' => 'System',
                'actor_id' => 0,
                'event' => 'funds_frozen_for_dispute',
                'entity_type' => WalletTransaction::class,
                'entity_id' => $transaction->id,
                'after' => json_encode(['lot_allocations' => $lotAllocations]),
            ]);
        }
    }

    /**
     * Unfreeze funds related to a dispute
     */
    private function unfreezeRelatedFunds(int $transactionId): void
    {
        $transaction = WalletTransaction::find($transactionId);

        if($transaction->lot_allocation){
            $lotAllocations = json_decode($transaction->lot_allocation, true);

            foreach ($lotAllocations as $allocation) {
                WalletLot::where('id', $allocation['lot_id'])->where('status', 'locked')->update(['status' => 'active']);
            }

            AuditLog::create([
                'actor_type' => 'System',
                'actor_id' => 0,
                'event' => 'funds_unfrozen_after_dispute',
                'entity_type' => WalletTransaction::class,
                'entity_id' => $transactionId,
                'after' => json_encode(['lot_allocations' => $lotAllocations]),
            ]);
        }
    }

    /**
     * Notify administrators about new disputes
     */
    private function notifyAdmins(string $type, Dispute $dispute): void
    {
        $adminUsers = User::where('role', 'admin')
            ->orWhere('role', 'support')
            ->get();

        Notification::send($adminUsers, new DisputeCreatedNotification($dispute));
    }

    /**
     * Notify user about dispute resolution
     */
    private function notifyUser(int $userId, string $type, Dispute $dispute): void
    {
        $user = User::find($userId);
        $user->notify(new DisputeResolvedNotification($dispute));
    }

    /**
     * Resolve a dispute
     */
    public function resolveDispute(int $disputeId, string $resolution, string $notes, ?float $refundAmount = null): Dispute
    {
        return DB::transaction(function () use ($disputeId, $resolution, $notes, $refundAmount) {
            $dispute = Dispute::findOrFail($disputeId);

            $dispute->update([
                'status' => 'resolved',
                'resolution' => $resolution,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
                'notes' => $notes,
            ]);

            if(($resolution === 'refund' || $resolution == 'partial_refund') && $refundAmount){
                $this->processRefund($dispute, $refundAmount);
            }

            // Unfreeze funds if they were frozen
            $this->unfreezeRelatedFunds($dispute->transaction_id);

            // Notify user
            $this->notifyUser($dispute->user_id, 'dispute_resolved', $dispute);

            // Log audit trail
            AuditLog::create([
                'actor_type' => User::class,
                'actor_id' => auth()->id,
                'event' => 'dispute_resolved',
                'entity_type' => Dispute::class,
                'entity_id' => $dispute->id,
                'after' => json_encode([
                    'resolution' => $resolution,
                    'refund_amount' => $refundAmount,
                    'notes' => $notes,
                ]),
                'ip' => request()->ip(),
            ]);

            return $dispute;
        });
    }

    /**
     * Process refund for a dispute
     */
    private function processRefund(Dispute $dispute, float $refundAmount): void
    {
        $transaction = $dispute->transaction;
        $user = $transaction->user;

        //Create a refund transaction
        $refundTransaction = WalletTransaction::create([
            'user_id' => $user->id,
            'direction' => 'CR',
            'amount' => $refundAmount,
            'type' => 'dispute_refund',
            'status' => 'completed',
            'ref_type' => Dispute::class,
            'ref_id' => $dispute->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Create wallet lot for refund
        $walletLot = WalletLot::create([
            'user_id' => $user->id,
            'source' => 'dispute_refund',
            'amount' => $refundAmount,
            'base_value' => $refundAmount,
            'bonus_value' => 0,
            'currency' => $transaction->currency,
            'acquired_at' => now(),
            'expires_at' => now()->addDays(360),
            'status' => 'active',
        ]);

        // Update wallet balance
        $user->wallet->increment('total_available', $refundAmount);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id,
            'event' => 'dispute_refund_processed',
            'entity_type' => Dispute::class,
            'entity_id' => $dispute->id,
            'after' => json_encode([
                'refund_amount' => $refundAmount,
                'lot_id' => $walletLot->id,
                'transaction_id' => $refundTransaction->id,
            ])
        ]);
    }

    /**
     * Get all disputes with optional filters
     */
    public function getDisputes(array $filters = [], int $perPage = 15)
    {
        $query = Dispute::query()->with(['user', 'transaction', 'resolver', 'evidence']);

        if(!empty($filters['status'])){
            $query->where('status', $filters['status']);
        }

        if(!empty($filters['priority'])){
            $query->where('priority', $filters['priority']);
        }

        if(!empty($filters['user_id'])){
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        if(!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reason', 'like', '%' . $filters['search'] . '%')
                    ->orWhereHas('user', function ($userQ) use ($filters) {
                        $userQ->where('name', 'like', '%' . $filters['search'] . '%')
                            ->orWhere('email', 'like', '%' . $filters['search'] . '%');
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Update dispute priority
     */
    public function updateDisputePriority(int $disputeId, string $priority): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);
        $dispute->update(['priority' => $priority]);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id,
            'event' => 'dispute_priority_updated',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode(['priority' => $priority]),
        ]);

        return $dispute;
    }

    /**
     * Add evidence to an existing dispute
     */
    public function addEvidence(int $disputeId, $file, ?string $description = null): DisputeEvidence
    {
        $dispute = Dispute::findOrFail($disputeId);

        $evidence = DisputeEvidence::create([
            'dispute_id' => $dispute->id,
            'file_path' => $file->store('disputes/evidence'),
            'file_name' => $file->getClientOriginalName(),
            'description' => $description ?? $file->getClientOriginalName(),
        ]);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id,
            'event' => 'dispute_evidence_added',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode([
                'file_name' => $file->getClientOriginalName(),
                'description' => $description ?? $file->getClientOriginalName(),
            ]),
        ]);

        return $evidence;
    }

    /**
     * Cancel a dispute
     */
    public function cancelDispute(int $disputeId, string $reason): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);

        $dispute->update([
            'status' => 'cancelled',
            'resolution' => 'cancelled',
            'notes' => $dispute->notes ? $dispute->notes . '\nCancellation:' . $reason : $reason,
        ]);

        // Unfreeze funds if they were frozen
        $this->unfreezeRelatedFunds($dispute->transaction_id);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id,
            'event' => 'dispute_cancelled',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode(['reason' => $reason]),
        ]);

        return $dispute;
    }

    /**
     * Get dispute statistics
     */
    public function getDisputeStats(): array
    {
        return [
            'total' => Dispute::count(),
            'open' => Dispute::where('status', 'open')->count(),
            'under_review' => Dispute::where('status', 'under_review')->count(),
            'resolved' => Dispute::where('status', 'resolved')->count(),
            'by_priority' => Dispute::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority')
            ->toArray(),
            'avg_resolution_time' => Dispute::whereNotNull('resolved_at')
            ->select(DB::raw('avg(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
            ->value('avg_hours'),
        ];
    }
}
