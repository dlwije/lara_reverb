<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Carbon\Carbon;
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
}
