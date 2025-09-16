<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletLot;
use Modules\Wallet\Models\WalletTransaction;

class WalletService
{

    /**
     * Get user wallet with balance and lots
     */
    public function getUserWallet(User $user): array
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['total_available' => 0, 'total_pending' => 0]
        );

        $activeLots = WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('expires_at', 'asc')
            ->get();

        return [
            'balance' => $wallet->total_available,
            'pending_hold' => $wallet->total_pending,
            'lots' => $activeLots,
            'expiring_soon' => $this->getExpiringLots($user),
        ];
    }

    /**
     * Get expiring lots (T-30, T-7, T-1)
     */
    private function getExpiringLots(User $user):array
    {
        return WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->where('expires_at', '<=', Carbon::now()->addDays(30))
            ->orderBy('expires_at', 'asc')
            ->get()
            ->groupBy(function ($lot) {
                $daysUntilExpiry = Carbon::now()->diffInDays($lot->expires_at);

                if($daysUntilExpiry <= 1) return '1_day';
                if($daysUntilExpiry <= 7) return '7_days';
                return '30_days';
            })
            ->toArray();
    }

    private function calculateRunningBalance($transaction)
    {

    }
    public function getUserTransactions(User $user, array $filters, int $perPage = 15)
    {
        $query = WalletTransaction::where('user_id', $user->id);

        if(!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if(!empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from']));
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (!empty($filters['min'])) {
            $query->where('amount', '>=', $filters['min']);
        }

        if (!empty($filters['max'])) {
            $query->where('amount', '<=', $filters['max']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    public function exportTransactions(User $user, array $filters)
    {
        $transactions = $this->getUserTransactions($user, $filters, 1000); //Large Limit for export

        $filename = "wallet_transactions_{$user->id}_" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Date', 'Type', 'Description', 'Amount (AED)', 'Balance', 'Reference'
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    strtoupper($transaction->direction),
                    $this->getTransactionDescription($transaction),
                    $transaction->amount,
                    $this->calculateRunningBalance($transaction),
                    $transaction->ref_type ?: 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    private function getTransactionDescription($transaction): string
    {
        // Implementation based on transaction type
        return match ($transaction->type) {
            'gift_card_redeem' => 'Gift Card Redemption',
            'purchase'         => 'Purchase',
            'refund_credit'    => 'Refund Credit',
            default            => ucfirst(str_replace('_', ' ', $transaction->type)),
        };
    }

    /**
     * Deduct amount from wallet using FIFO (earliest expiry first)
     */
    public function deductFromWallet(User $user, float $amount, array $transactionData = []): array
    {
        return DB::transaction(function () use ($user, $amount, $transactionData) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if($wallet->total_available < $amount) {
                throw new \Exception('Insufficient wallet balance');
            }

            // Get active lots ordered by expiry (earliest first)
            $lots = WalletLot::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('remaining', '>', 0)
                ->where('expires_at', '>', Carbon::now())
                ->orderBy('expires_at', 'asc')
                ->orderBy('acquired_at', 'asc') // Secondary order by acquisition date
                    ->lockForUpdate()
                ->get();

            $remainingAmount = $amount;
            $lotAllocations = [];
            $lotsToUpdate = [];

            foreach ($lots as $lot) {
                if($remainingAmount <= 0) break;

                $deductibleAmount = min($lot->remaining, $remainingAmount);

                $lot->remaining -= $deductibleAmount;
                $remainingAmount -= $deductibleAmount;


                $lotAllocations[] = [
                    'lot_id' => $lot->id,
                    'amount' => $deductibleAmount,
                    'lot_source' => $lot->source,
                    'lot_expiry' => $lot->expires_at->toISOString(),
                ];

                $lotsToUpdate[] = $lot;

                // Mark lot as expired if it's fully deducted
                if($lot->remaining == 0) {
                    $lot->status = 'expired';
                }
            }

            if($remainingAmount > 0) {
                throw new \Exception('Insufficient funds in available wallet lots');
            }

            // Update lots
            foreach ($lotsToUpdate as $lot) {
                $lot->save();
            }

            // Update wallet balance
            $wallet->decrement('total_available', $amount);

            // Create transaction record
            $transaction = WalletTransaction::create(array_merge([
                'user_id' => $user->id,
                'direction' => 'DR',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'type' => $transactionData['type'] ?? 'purchase',
                'status' => 'completed',
                'lot_allocations' => $lotAllocations,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $transactionData));

            return [
                'transaction' => $transaction,
                'lot_allocations' => $lotAllocations,
                'new_balance' => $wallet->fresh()->total_available,
            ];
        });
    }

    /**
     * Check available balance with lot breakdown
     */
    public function getAvailableBalanceWithLots(User $user): array
    {
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $availableLots = WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->get();

        return [
            'total_balance' => $wallet->total_available,
            'available_lots' => $availableLots,
            'expiring_soon' => $availableLots->where('expires_at', '<=', now()->addDays(30)),
        ];
    }

    /**
     * Preview deduction - shows which lots will be used
     */
    public function previewDeduction(User $user, float $amount): array
    {
        $lots = WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->orderBy('acquired_at', 'asc')
            ->get();

        $remainingAmount = $amount;
        $previewAllocations = [];
        $totalAvailable = 0;

        foreach ($lots as $lot) {
            if($remainingAmount <= 0) break;

            $deductibleAmount = min($lot->remaining, $remainingAmount);
            $remainingAmount -= $deductibleAmount;
            $totalAvailable += $deductibleAmount;

            $previewAllocations[] = [
                'lot_id' => $lot->id,
                'lot_source' => $lot->source,
                'current_balance' => $lot->remaining,
                'amount_to_deduct' => $deductibleAmount,
                'remaining_after' => $lot->remaining - $deductibleAmount,
                'expires_at' => $lot->expires_at->toISOString(),
                'days_until_expiry' => $lot->expires_at->diffInDays(now()),
            ];
        }

        if($remainingAmount > 0) {
            throw new \Exception("Insufficient funds. Available: AED {$totalAvailable}, Required: AED {$amount}");
        }

        return [
            'amount' => $amount,
            'allocations' => $previewAllocations,
            'total_available' => $totalAvailable,
        ];
    }

    /**
     * Refund amount to wallet (creates new lot)
     */
    public function refundToWallet(User $user, float $amount, string $source, array $metadata = []): array
    {
        return DB::transaction(function () use ($user, $amount, $source, $metadata) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            //Create new lot for refund
            $lot = WalletLot::create([
                'user_id' => $user->id,
                'source' => $source,
                'amount' => $amount,
                'remaining' => $amount,
                'currency' => $wallet->currency,
                'acquired_at' => now(),
                'expires_at' => now()->addDays(360), // 360 days validity
                'status' => 'active',
                'metadata' => $metadata,
            ]);

            // Update wallet balance
            $wallet->increment('total_available', $amount);

            // Create a transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'direction' => 'CR',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'type' => 'refund',
                'status' => 'completed',
                'ref_type' => $metadata['ref_type'] ?? null,
                'ref_id' => $metadata['ref_id'] ?? null,
                'lot_allocations' => [['lot_id' => $lot->id, 'amount' => $amount]],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'lot' => $lot,
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->total_available,
            ];
        });
    }

    /**
     * Add funds to wallet (generic method)
     */
    public function addToWallet(
        User $user,
        float $amount,
        string $source,
        array $metadata = [],
        ?int $validityDays = null,
        ?string $currency = null
    ): array
    {
        return DB::transaction(function () use ($user, $amount, $source, $metadata, $validityDays, $currency){
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            // Create wallet lot
            $walletLot = WalletLot::create([
                'user_id' => $user->id,
                'source' => $source,
                'amount' => $amount,
                'remaining' => $amount,
                'currency' => $currency ?? $wallet->currency,
                'acquired_at' => now(),
                'expires_at' => now()->addDays($validityDays),
                'status' => 'active',
                'metadata' => $metadata,
            ]);

            // Create a transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'direction' => 'CR',
                'amount' => $amount,
                'currency' => $currency ?? $wallet->currency,
                'type' => $this->getCreditTypeFromSource($source),
                'status' => 'completed',
                'ref_type' => $metadata['ref_type'] ?? null,
                'ref_id' => $metadata['ref_id'] ?? null,
                'lot_allocations' => [['lot_id' => $walletLot->id, 'amount' => $amount]],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Update wallet balance
            $wallet->increment('total_available', $amount);

            return [
                'lot' => $walletLot,
                'transaction' => $transaction,
                'new_balance' => $wallet->fresh()->total_available,
            ];
        });
    }

    /**
     * Get transaction type based on source
     */
    private function getCreditTypeFromSource(string $source): string
    {
        return match ($source) {
            'gift_card' => 'gift_card_redeem',
            'refund' => 'refund_credit',
            'purchase' => 'purchase',
            'admin_adjustment' => 'admin_adjustment',
            'promo' => 'promo_credit',
            default => 'credit',
        };
    }

    /**
     * Get wallet balance summary with lot breakdown
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        $lots = WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->get();

        $summary = [
            'total_balance' => $wallet->total_available,
            'active_lots' => $lots->count(),
            'expiring_soon' => $lots->where('expires_at', '<=', now()->addDays(30))->sum('remaining'),
            'lot_breakdown' => $lots->map(function ($lot) {
                return [
                    'id' => $lot->id,
                    'source' => $lot->source,
                    'amount' => $lot->amount,
                    'remaining' => $lot->remaining,
                    'expires_at' => $lot->expires_at,
                    'days_until_expiry' => $lot->expires_at->diffInDays(now()),
                    'acquired_at' => $lot->acquired_at,
                ];
            })
        ];

        return $summary;
    }
}
