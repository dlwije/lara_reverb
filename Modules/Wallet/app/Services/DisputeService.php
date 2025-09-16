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
use Modules\Wallet\Notifications\DisputeCreatedNotification;
use Modules\Wallet\Notifications\DisputeResolvedNotification;

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

    /**
     * Escalate dispute priority
     */
    public function escalateDispute(int $disputeId, string $newPriority, string $reason): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);

        $dispute->update([
            'priority' => $newPriority,
            'notes' => $dispute->notes ? $dispute->notes . '\nEscalation:' . $reason : $reason,
        ]);

        // Notify admin team
        $this->notifyAdmins('dispute_escalated', $dispute);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id(),
            'event' => 'dispute_escalated',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode([
                'old_priority' => $dispute->getOriginal('priority'),
                'new_priority' => $newPriority,
                'reason' => $reason
            ])
        ]);

        return $dispute;
    }

    /**
     * Change dispute status to under review
     */
    public function markUnderReview(int $disputeId, int $reviewerId): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);

        $dispute->update([
            'status' => 'under_review',
            'notes' => $dispute->notes ? $dispute->notes . "\nAssigned to reviewer: " . $reviewerId : "Assigned to reviewer: " . $reviewerId
//            'under_review_by' => $reviewerId,
//            'under_review_at' => now(),
        ]);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id(),
            'event' => 'dispute_under_review',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode(['reviewer_id' => $reviewerId])
        ]);

        return $dispute;
    }

    /**
     * Add internal note to dispute
     */
    public function addInternalNote(int $disputeId, string $note, bool $isInternal = true): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);

        $prefix = $isInternal ? "[INTERNAL] " : "";
        $dispute->update([
            'notes' => $dispute->notes ? $dispute->notes . "\n" . $prefix . $note : $prefix . $note
        ]);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id(),
            'event' => 'dispute_note_added',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode([
                'note' => $note,
                'is_internal' => $isInternal
            ])
        ]);

        return $dispute;
    }

    /**
     * Reopen a resolved dispute
     */
    public function reopenDispute(int $disputeId, string $reason): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);

        if ($dispute->status !== 'resolved') {
            throw new \Exception('Only resolved disputes can be reopened');
        }

        $dispute->update([
            'status' => 'under_review',
            'resolution' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'notes' => $dispute->notes ? $dispute->notes . "\nReopened: " . $reason : "Reopened: " . $reason
        ]);

        // Freeze funds again if needed
        $transaction = $dispute->transaction;
        if ($transaction->direction === 'DR') {
            $this->freezeRelatedFunds($transaction);
        }

        // Notify admins
        $this->notifyAdmins('dispute_reopened', $dispute);

        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => auth()->id(),
            'event' => 'dispute_reopened',
            'entity_type' => Dispute::class,
            'entity_id' => $disputeId,
            'after' => json_encode(['reason' => $reason])
        ]);

        return $dispute;
    }

    /**
     * Get disputes assigned to specific admin
     */
    public function getAssignedDisputes(int $adminId, array $filters = [], int $perPage = 15)
    {
        $query = Dispute::with(['user', 'transaction', 'evidence'])
            ->where('status', '!=', 'resolved')
            ->where('status', '!=', 'cancelled');

        // Add filters
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Calculate suggested refund amount
     */
    public function calculateSuggestedRefund(int $disputeId): float
    {
        $dispute = Dispute::with('transaction')->findOrFail($disputeId);
        $transaction = $dispute->transaction;

        // Default to full amount
        $suggestedRefund = $transaction->amount;

        // Apply business logic for partial refunds
        if ($this->isPartialRefundScenario($dispute)) {
            $suggestedRefund = $transaction->amount * 0.5; // 50% for partial scenarios
        }

        return round($suggestedRefund, 2);
    }

    /**
     * Check if dispute qualifies for partial refund
     */
    private function isPartialRefundScenario(Dispute $dispute): bool
    {
        // Implement business logic for partial refund scenarios
        $partialKeywords = ['partial', '50%', 'half', 'portion', 'some'];
        $reason = strtolower($dispute->reason);

        foreach ($partialKeywords as $keyword) {
            if (str_contains($reason, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Export disputes report
     */
    public function exportDisputesReport(array $filters)
    {
        $disputes = $this->getDisputes($filters, 1000); // Large limit for export

        $filename = "disputes_report_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($disputes) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID', 'User', 'Transaction ID', 'Amount', 'Reason', 'Status',
                'Priority', 'Resolution', 'Created At', 'Resolved At', 'Notes'
            ]);

            foreach ($disputes as $dispute) {
                fputcsv($file, [
                    $dispute->id,
                    $dispute->user->email,
                    $dispute->transaction_id,
                    $dispute->transaction->amount,
                    $dispute->reason,
                    $dispute->status,
                    $dispute->priority,
                    $dispute->resolution,
                    $dispute->created_at->format('Y-m-d H:i:s'),
                    $dispute->resolved_at?->format('Y-m-d H:i:s'),
                    substr($dispute->notes ?? '', 0, 100) // First 100 chars
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get dispute timeline
     */
    public function getDisputeTimeline(int $disputeId)
    {
        $dispute = Dispute::findOrFail($disputeId);

        return AuditLog::where('entity_type', Dispute::class)
            ->where('entity_id', $disputeId)
            ->orWhere(function ($query) use ($dispute) {
                $query->where('entity_type', WalletTransaction::class)
                    ->where('entity_id', $dispute->transaction_id);
            })
            ->with('actor')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Bulk update dispute statuses
     */
    public function bulkUpdateStatuses(array $disputeIds, string $status, string $reason): int
    {
        $updated = 0;

        foreach ($disputeIds as $disputeId) {
            try {
                $dispute = Dispute::find($disputeId);
                if ($dispute) {
                    $dispute->update([
                        'status' => $status,
                        'notes' => $dispute->notes ? $dispute->notes . "\nBulk update: " . $reason : "Bulk update: " . $reason
                    ]);
                    $updated++;
                }
            } catch (\Exception $e) {
                // Continue with other disputes
                continue;
            }
        }

        return $updated;
    }
}
