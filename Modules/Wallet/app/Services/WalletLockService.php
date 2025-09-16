<?php

namespace Modules\Wallet\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\AuditLog;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletLock;
use Modules\Wallet\Models\WalletLot;

class WalletLockService
{
    /**
     *Freeze entire wallet
     */
    public function freezeWallet(int $userId, string $reason, ?string $notes = null, ?int $lockedBy = null): WalletLock
    {
        return DB::transaction(function () use ($userId, $reason, $notes, $lockedBy) {
            $wallet = Wallet::query()->where('user_id', $userId)->firstOrFail();

            // Freeze wallet logic here
            $wallet->update(['status' => 'locked']);

            // Freeze all active wallet lots
            WalletLot::where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'locked']);

            // Create lock record
            $lock = WalletLock::create([
                'wallet_id' => $wallet->id,
                'locked_by' => $lockedBy ?? auth()->id() ?? 0, // 0 for system locks
                'reason' => $reason,
                'notes' => $notes,
                'expires_at' => null, // Permanent lock until manually unlocked
            ]);

            // Log audit trail
            AuditLog::create([
                'actor_type' => $lockedBy ? User::class : 'System',
                'actor_id' => $lockedBy ?? 0,
                'event' => 'wallet_frozen',
                'entity_type' => Wallet::class,
                'entity_id' => $wallet->id,
                'after' => json_encode([
                    'reason' => $reason,
                    'notes' => $notes,
                    'locked_by' => $lockedBy ?? auth()->id() ?? 0,
                ]),
                'ip' => request()->ip(),
            ]);
            return $lock;
        });
    }

    public function unfreezeWallet(int $userId, string $reason, ?int $unlockedBy = null): void
    {
        DB::transaction(function () use ($userId, $reason, $unlockedBy) {
            $wallet = Wallet::query()->where('user_id', $userId)->firstOrFail();

            //unfreeze wallet logic here
            $wallet->update(['status' => 'active']);

            // Unfreeze locked lots
            WalletLot::where('user_id', $userId)
                ->where('status', 'locked')
                ->update(['status' => 'active']);

            // Close active lock records
            WalletLock::where('wallet_id', $wallet->id)
                ->whereNull('expires_at')
                ->update(['expires_at' => now()]);

            // Log audit trail
            AuditLog::create([
                'actor_type' => $unlockedBy ? User::class : 'System',
                'actor_id' => $unlockedBy ?? auth()->id() ?? 0,
                'event' => 'wallet_unfrozen',
                'entity_type' => Wallet::class,
                'entity_id' => $wallet->id,
                'after' => json_encode([compact('reason')]),
                'ip' => request()->ip(),
            ]);
        });
    }

    public function freezeLot(int $lotId, string $reason, ?int $lockedBy = null): void
    {
        DB::transaction(function () use ($lotId, $reason, $lockedBy) {
            $lot = WalletLot::findOrFail($lotId);

            WalletLot::where('id', $lotId)->where('status', 'active')->update(['status' => 'locked']);

            AuditLog::create([
                'actor_type' => $lockedBy ? User::class : 'System',
                'actor_id' => $lockedBy ?? auth()->id() ?? 0,
                'event' => 'lot_frozen',
                'entity_type' => WalletLot::class,
                'entity_id' => $lotId,
                'after' => json_encode([compact('reason'), 'user_id' => $lot->user_id]),
                'ip' => request()->ip(),
            ]);
        });
    }

    /**
     * Unfreeze specific wallet lot
     */
    public function unfreezeLot(int $lotId, string $reason, ?int $unlockedBy = null): void
    {
        DB::transaction(function () use ($lotId, $reason, $unlockedBy) {
            $lot = WalletLot::findOrFail($lotId);

            WalletLot::where('id', $lotId)->where('status', 'locked')->update(['status' => 'active']);

            // Log audit trail
            AuditLog::create([
                'actor_type' => $unlockedBy ? User::class : 'System',
                'actor_id' => $unlockedBy ?? 0,
                'event' => 'lot_unfrozen',
                'entity_type' => WalletLot::class,
                'entity_id' => $lotId,
                'after' => json_encode([
                    'reason' => $reason,
                    'user_id' => $lot->user_id
                ]),
                'ip' => request()->ip()
            ]);
        });
    }

    /**
     * Temporary freeze with expiration
     */
    public function temporaryFreeze(int $userId, string $reason, Carbon $expiresAt, ?string $notes = null): WalletLock
    {
        return DB::transaction(function () use ($userId, $reason, $expiresAt, $notes) {
            $wallet = Wallet::query()->where('user_id', $userId)->firstOrFail();

            // Freeze wallet logic here
            $wallet->update(['status' => 'locked']);

            // Freeze all active lots
            WalletLot::where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'locked']);

            // Create temporary lock record
            $lock = WalletLock::create([
                'wallet_id' => $wallet->id,
                'locked_by' => auth()->id() ?? 0,
                'reason' => $reason,
                'notes' => $notes,
                'expires_at' => $expiresAt,
            ]);

            // Log audit trail
            AuditLog::create([
                'actor_type' => auth()->check() ? User::class : 'System',
                'actor_id' => auth()->id() ?? 0,
                'event' => 'wallet_temporary_freeze',
                'entity_type' => Wallet::class,
                'entity_id' => $wallet->id,
                'after' => json_encode([
                    'reason' => $reason,
                    'expires_at' => $expiresAt->toISOString(),
                    'notes' => $notes
                ]),
                'ip' => request()->ip()
            ]);

            return $lock;
        });
    }

    /**
     * Check if wallet is frozen
     */
    public function isWalletFrozen(int $userId): bool
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        return $wallet && $wallet->status === 'locked';
    }

    /**
     * Check if specific lot is frozen
     */
    public function isLotFrozen(int $lotId): bool
    {
        $lot = WalletLot::find($lotId);
        return $lot && $lot->status === 'locked';
    }

    /**
     * Get active locks for a wallet
     */
    public function getActiveLocks(int $userId)
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return collect();
        }

        return WalletLock::where('wallet_id', $wallet->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('locksmith')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get lock history for a wallet
     */
    public function getLockHistory(int $userId, int $perPage = 15)
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return collect();
        }

        return WalletLock::where('wallet_id', $wallet->id)
            ->with('locksmith')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Auto-unfreeze expired temporary locks
     */
    public function processExpiredLocks(): int
    {
        $expiredLocks = WalletLock::where('expires_at', '<=', now())
            ->whereNull('resolved_at')
            ->get();

        $unfrozenCount = 0;
        foreach ($expiredLocks as $lock) {
            DB::transaction(function () use ($lock, &$unfrozenCount) {
                $wallet = Wallet::find($lock->wallet_id);

                if ($wallet) {
                    // Unfreeze wallet
                    $wallet->update(['status' => 'active']);

                    // Unfreeze locked lots
                    WalletLot::where('user_id', $wallet->user_id)
                        ->where('status', 'locked')
                        ->update(['status' => 'active']);

                    // Mark lock as resolved
                    $lock->update(['resolved_at' => now()]);

                    $unfrozenCount++;

                    // Log audit trail
                    AuditLog::create([
                        'actor_type' => 'System',
                        'actor_id' => 0,
                        'event' => 'wallet_auto_unfrozen',
                        'entity_type' => Wallet::class,
                        'entity_id' => $wallet->id,
                        'after' => json_encode([
                            'lock_id' => $lock->id,
                            'reason' => 'automatic_expiry'
                        ])
                    ]);
                }
            });
        }

        return $unfrozenCount;
    }

    /**
     * Get lock statistics
     */
    public function getLockStats(): array
    {
        return [
            'total_active' => WalletLock::whereNull('expires_at')->orWhere('expires_at', '>', now())->count(),
            'total_expired' => WalletLock::where('expires_at', '<=', now())
                ->count(),
            'by_reason' => WalletLock::select('reason', DB::raw('count(*) as count'))
                ->groupBy('reason')
                ->get()
                ->pluck('count', 'reason')
                ->toArray(),
            'recent_locks' => WalletLock::with(['wallet.user', 'locksmith'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
        ];
    }
}
