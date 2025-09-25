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

    public function __construct(public NotificationService $notificationService){}
    /**
     * Get user wallet with balance and lots
     */
    public function getUserWallet($user): array
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => trim($user->name),'total_available' => 0, 'total_pending' => 0]
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
    public function getExpiringLots($user):array
    {
//        return WalletLot::where('user_id', $user->id)
//            ->where('status', 'active')
//            ->whereB('expires_at', '>', Carbon::now())
//            ->where('expires_at', '<=', Carbon::now()->addDays(30))
//            ->orderBy('expires_at', 'asc')
//            ->get()
//            ->groupBy(function ($lot) {
//                $daysUntilExpiry = Carbon::now()->diffInDays($lot->expires_at);
//
//                if($daysUntilExpiry <= 1) return '1_day';
//                if($daysUntilExpiry <= 7) return '7_days';
//                return '30_days';
//            })
//            ->toArray();
        return WalletLot::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereBetween('expires_at', [Carbon::now()->subDays(30), Carbon::now()->addDays(30)])
            ->orderBy('expires_at', 'asc')
            ->get()
            ->groupBy(function ($lot) {
                $daysUntilExpiry = Carbon::now()->diffInDays($lot->expires_at, false); // false = allow negative

                if ($daysUntilExpiry < 0) {
                    return 'expired'; // already expired
                }
                if ($daysUntilExpiry <= 1) {
                    return '1_day'; // expires today or tomorrow
                }
                if ($daysUntilExpiry <= 7) {
                    return '7_days'; // within a week
                }
                return '30_days'; // within 30 days
            })
            ->toArray();
    }

    private function calculateRunningBalance($transactions)
    {
        $runningBalance = 0;
        $transactionsWithBalance = [];

        foreach ($transactions as $transaction) {
            if ($transaction->direction === 'CR') {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }

            $transactionsWithBalance[] = [
                'transaction' => $transaction,
                'running_balance' => round($runningBalance, 2)
            ];
        }

        return $transactionsWithBalance;
    }
    public function getUserTransactions($user, array $filters, int $perPage = 15)
    {
        $query = WalletTransaction::where('user_id', $user->id)->where('status', WalletTransaction::STATUS_COMPLETED);

        if (!empty($filters['search_input'])) {
            $query->where('ref_number', 'LIKE', '%' . $filters['search_input'] . '%');
        }
        // Handle period filter (7days, 30days, 90days)
        if (!empty($filters['period'])) {
            $query->where('created_at', '>=', $this->getDateFromPeriod($filters['period']));
        }

        if(!empty($filters['payment_type'])) {
            $query->where('type', $filters['payment_type']);
        }

        if (!empty($filters['pay_method'])) {
            $query->where(function ($subQuery) use ($filters) {
                $subQuery->whereHas('lots', function ($lotQuery) use ($filters) {
                    $lotQuery->where('source', $filters['pay_method']);
                });
            });
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

        // Get transactions in chronological order for balance calculation
        $transactions = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Calculate running balance
        $transactionsWithBalance = $this->addRunningBalanceToTransactions($transactions);

        return $this->paginateTransactions($transactionsWithBalance, $perPage);
    }

    private function getDateFromPeriod(string $period): Carbon
    {
        return match ($period) {
            '7days' => Carbon::now()->subDays(7)->startOfDay(),
            '30days' => Carbon::now()->subDays(30)->startOfDay(),
            '90days' => Carbon::now()->subDays(90)->startOfDay(),
            'current_month' => Carbon::now()->startOfMonth(),
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            'current_year' => Carbon::now()->startOfYear(),
            'last_year' => Carbon::now()->subYear()->startOfYear(),
            default => Carbon::now()->subDays(30)->startOfDay(), // Default to 30 days
        };
    }

    public function getPeriodOptions(): array
    {
        return [
            ['value' => '7days','label' => 'Last 7 Days'],
            ['value' => '30days', 'label'=> 'Last 30 Days'],
            ['value' => '90days', 'label'=> 'Last 90 Days'],
            ['value' => 'current_month', 'label'=> 'Current Month'],
            ['value' => 'last_month', 'label'=> 'Last Month'],
            ['value' => 'current_year', 'label'=> 'Current Year'],
            ['value' => 'last_year', 'label'=> 'Last Year'],
        ];
    }

    /**
     * Add running balance to each transaction object
     */
    private function addRunningBalanceToTransactions($transactions)
    {
        $runningBalance = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->direction === 'CR') {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }

            // Add running_balance as a custom attribute to the transaction
            $transaction->running_balance = round($runningBalance, 2);
        }

        return $transactions;
    }

    /**
     * Paginate the transactions with running balance
     */
    private function paginateTransactions($transactions, int $perPage = 15)
    {
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $transactions->slice($offset, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            WalletTransactionResource::collection($items),
            $transactions->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function exportTransactions($user, array $filters)
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
    public function getTransactionDescription($transaction): string
    {
        // Implementation based on transaction type
        return match ($transaction->type) {
            'gift_card_redeem' => 'Gift Card Redemption',
            'purchase'         => 'Purchase',
            'refund_credit'    => 'Refund Credit',
            'wallet_recharge'    => 'Wallet Recharge',
            default            => ucfirst(str_replace('_', ' ', $transaction->type)),
        };
    }

    public function getPaymentTypes($transaction): string
    {
//        'redeem',
//                'gift_card_redeem',
//                'purchase',
//                'refund_credit',
//                'admin_adjustment'

        return match ($transaction->type) {
            'gift_card_redeem' => 'Deposits',
            'purchase'         => 'Purchase',
            'refund_credit'    => 'Refund',
            'wallet_recharge'    => 'Wallet Recharge',
            default            => ucfirst(str_replace('_', ' ', $transaction->type)),
        };
    }

    /**
     * Deduct amount from wallet using FIFO (earliest expiry first)
     */
    public function deductFromWallet($user, float $amount, array $transactionData = []): array
    {
        Log::info('Wallet Deduct from Data:',$transactionData);
        $result = DB::transaction(function () use ($user, $amount, $transactionData) {
            Log::info('Inside DB Transaction:');
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if($wallet->total_available < $amount) {
                throw new \Exception('Insufficient wallet balance');
            }

            Log::info('Inside DB Transaction:'.$wallet->total_available);

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

//                Log::info('LotsAreUsing:', [
//                    'lot_id' => $lot->id,
//                    'amount' => $deductibleAmount,
//                    'lot_source' => $lot->source,
//                    'lot_expiry' => $lot->expires_at->toISOString(),
//                ]);
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
//                'base_value' => $baseValue,
//                'bonus_value' => $bonusValue,
                'currency' => $wallet->currency ?? 'AED',
                'type' => $transactionData['type'] ?? 'purchase',
                'status' => 'completed',
                'ref_type' => $transactionData['ref_type'] ?? null,
                'ref_id' => $transactionData['ref_id'] ?? null,
                'lot_allocation' => $lotAllocations, // lot allocation array
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $transactionData));

            return [
                'transaction' => $transaction,
                'lot_allocations' => $lotAllocations,
                'new_balance' => $wallet->fresh()->total_available,
            ];
        });
        // Send notification after transaction is committed
        $this->notificationService->sendTransactionNotification($result['transaction']);
        return $result;
    }

    /**
     * Check available balance with lot breakdown
     */
    public function getAvailableBalanceWithLots($user): array
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
    public function previewDeduction($user, float $amount): array
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
    public function refundToWallet($user, float $amount, string $source, array $metadata = []): array
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
        $user,
        float $amount,
        string $source,
        float $baseValue,
        float $bonusValue,
        string $wTStatus = WalletLot::STATUS_LOCKED,
        string $wLStatus = WalletTransaction::STATUS_PENDING,
        array $metadata = [],
        ?int $validityDays = null,
        ?string $currency = null
    ): array
    {
        $result = DB::transaction(function () use ($user, $amount, $source, $baseValue, $bonusValue, $wTStatus, $wLStatus, $metadata, $validityDays, $currency){
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            // Create wallet lot
            $walletLot = WalletLot::create([
                'user_id' => $user->id,
                'source' => $source,
                'amount' => $amount,
                'base_value' => $baseValue,
                'bonus_value' => $bonusValue,
                'remaining' => $amount,
                'currency' => $currency ?? $wallet->currency,
                'acquired_at' => now(),
                'expires_at' => !empty($validityDays) ? now()->addDays($validityDays) : now()->addDays(360),
                'status' => $wLStatus,
                'metadata' => $metadata,
            ]);

            // Create a transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'direction' => 'CR',
                'amount' => $amount,
                'base_value' => $baseValue,
                'bonus_value' => $bonusValue,
                'currency' => $currency ?? $wallet->currency,
                'type' => $this->getCreditTypeFromSource($source),
                'status' => $wTStatus,
                'ref_type' => $metadata['ref_type'] ?? null,
                'ref_id' => $metadata['ref_id'] ?? null,
                'lot_allocations' => [['lot_id' => $walletLot->id, 'amount' => $amount]],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            if($wLStatus !== WalletLot::STATUS_LOCKED) {
                // Update wallet balance
                $wallet->increment('total_available', $amount);
            }

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
     * Get transaction type based on source
     */
    private function getCreditTypeFromSource(string $source): string
    {
        return match ($source) {
            WalletLot::SOURCE_GIFT_CARD, WalletLot::SOURCE_CREDIT_CARD => 'deposit',
            'purchase' => 'purchase',
            'refund' => 'refund_credit',
            'admin_adjustment' => 'admin_adjustment',
            'promo' => 'promo_credit',
            default => 'credit',
        };
    }

    /**
     * Get comprehensive wallet summary
     */
    public function getWalletSummary($user): array
    {
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        return [
            'balance' => [
                'total' => $wallet->total_available,
                'formatted_total' => number_format($wallet->total_available, 2) . ' AED',
                'pending' => $wallet->total_pending,
                'formatted_pending' => number_format($wallet->total_pending, 2) . ' AED',
                'spendable' => $wallet->spendable_balance,
                'formatted_spendable' => number_format($wallet->spendable_balance, 2) . ' AED',
            ],

            'monthly_stats' => [
                'spent' => $wallet->current_month_spent,
                'formatted_spent' => $wallet->formatted_current_month_spent,
                'deposited' => $wallet->current_month_deposited,
                'formatted_deposited' => number_format($wallet->current_month_deposited, 2) . ' AED',
                'net_flow' => $wallet->current_month_deposited - $wallet->current_month_spent,
                'formatted_net_flow' => number_format($wallet->current_month_deposited - $wallet->current_month_spent, 2) . ' AED',
            ],

            'activity' => [
                'transactions_count' => $wallet->transactions_count,
                'last_transaction_date' => $wallet->last_transaction_date,
                'last_updated_date' => $wallet->last_updated_date,
                'formatted_last_updated' => $wallet->formatted_last_updated_date,
                'is_active' => $wallet->is_active,
                'is_locked' => $wallet->is_locked,
            ],

            'lots_info' => [
                'total_lots' => $wallet->activeLots()->count(),
                'expiring_soon_count' => $wallet->expiring_soon_lots_count,
                'expiring_soon_amount' => $wallet->expiring_soon_balance,
                'formatted_expiring_soon' => number_format($wallet->expiring_soon_balance, 2) . ' AED',
                'by_source' => $wallet->balance_by_source,
            ],

            'lifetime_stats' => [
                'total_deposited' => $wallet->total_deposited,
                'formatted_total_deposited' => number_format($wallet->total_deposited, 2) . ' AED',
                'total_spent' => $wallet->total_spent,
                'formatted_total_spent' => number_format($wallet->total_spent, 2) . ' AED',
                'average_monthly_spend' => $this->calculateAverageMonthlySpend($user),
            ],

            'spending_trend' => $wallet->monthly_spending_trend,
        ];
    }

    /**
     * Get detailed wallet information
     */
    public function getWallet($user): array
    {
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

        return [
            'basic_info' => [
                'balance' => $wallet->total_available,
                'formatted_balance' => number_format($wallet->total_available, 2) . ' AED',
                'pending_hold' => $wallet->total_pending,
                'formatted_pending' => number_format($wallet->total_pending, 2) . ' AED',
                'currency' => $wallet->currency,
                'status' => $wallet->status,
                'formatted_status' => ucfirst($wallet->status),
            ],

            'transaction_stats' => [
                'total_transactions' => $wallet->transactions_count,
                'credit_transactions' => $wallet->creditTransactions()->count(),
                'debit_transactions' => $wallet->debitTransactions()->count(),
                'last_transaction' => $wallet->last_transaction_date,
            ],

            'lots_info' => [
                'total_active_lots' => $wallet->activeLots()->count(),
                'expiring_soon_lots' => $wallet->expiring_soon_lots_count,
                'expiring_amount' => $wallet->expiring_soon_balance,
                'formatted_expiring' => number_format($wallet->expiring_soon_balance, 2) . ' AED',
                'lot_breakdown' => $this->getLotBreakdown($user),
            ],

            'activity_info' => [
                'last_updated' => $wallet->last_updated_date,
                'formatted_last_updated' => $wallet->formatted_last_updated_date,
                'created_date' => $wallet->created_at->toISOString(),
                'formatted_created' => $wallet->created_at->format('M j, Y'),
            ],

            'limits_info' => [
                'is_locked' => $wallet->is_locked,
                'can_transact' => $wallet->is_active && !$wallet->is_locked,
                'spendable_balance' => $wallet->spendable_balance,
                'formatted_spendable' => number_format($wallet->spendable_balance, 2) . ' AED',
            ],
        ];
    }

    /**
     * Calculate average monthly spending
     */
    private function calculateAverageMonthlySpend($user): float
    {
        $result = DB::table('wallet_transactions')
            ->select(DB::raw('AVG(monthly_spent) as avg_monthly_spend'))
            ->fromSub(function ($query) use ($user) {
                $query->from('wallet_transactions')
                    ->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('SUM(amount) as monthly_spent')
                    )
                    ->where('user_id', $user->id)
                    ->where('direction', 'DR')
                    ->where('status', 'completed')
                    ->groupBy('year', 'month');
            }, 'monthly_spending')
            ->first();

        return round($result->avg_monthly_spend ?? 0, 2);
    }

    /**
     * Get detailed lot breakdown
     */
    private function getLotBreakdown($user): array
    {
        return WalletLot::where('user_id', $user->id)
            ->where('status', WalletLot::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->get()
            ->groupBy('source')
            ->map(function ($lots, $source) {
                return [
                    'source' => $source,
                    'count' => $lots->count(),
                    'total_amount' => $lots->sum('remaining'),
                    'formatted_amount' => number_format($lots->sum('remaining'), 2) . ' AED',
                    'lots' => $lots->map(function ($lot) {
                        return [
                            'id' => $lot->id,
                            'amount' => $lot->amount,
                            'remaining' => $lot->remaining,
                            'expires_at' => $lot->expires_at->toISOString(),
                            'days_until_expiry' => $lot->expires_at->diffInDays(now()),
                            'formatted_expiry' => $lot->expires_at->format('M j, Y'),
                        ];
                    })
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get wallet overview for dashboard
     */
    public function getWalletOverview($user): array
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => trim($user->name),'total_available' => 0, 'total_pending' => 0]
        );
        return [
            'total_balance' => $wallet->total_available,
            'formatted_balance' => number_format($wallet->total_available, 2) . ' AED',
            'pending_balance' => $wallet->total_pending,
            'monthly_spent' => $wallet->current_month_spent,
            'formatted_monthly_spent' => number_format($wallet->current_month_spent, 2) . ' AED',
            'transactions_count' => $wallet->transactions_count, // got through a get attribute on model via transactionsCount
            'currency' => $wallet->currency ?? 'AED',
            'total_rewards_earned' => "0.00",
            'expiring_lots_count' => $wallet->expiring_soon_lots_count,
            'expiring_amount' => $wallet->expiring_soon_balance,
            'formatted_expiring' => number_format($wallet->expiring_soon_balance, 2) . ' AED',
            'last_updated' => $wallet->formatted_last_updated_date,
            'is_locked' => $wallet->is_locked,
            'can_transact' => $wallet->is_active && !$wallet->is_locked,
        ];
    }
}
