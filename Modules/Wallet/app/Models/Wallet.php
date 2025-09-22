<?php

namespace Modules\Wallet\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

// use Modules\Wallet\Database\Factories\WalletFactory;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'total_available',
        'total_pending',
        'status',
        'currency',
        'last_activity_at'
    ];

    protected $casts = [
        'total_available' => 'decimal:2',
        'total_pending' => 'decimal:2',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Wallet status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_LOCKED = 'locked';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CLOSED = 'closed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(WalletLot::class, 'user_id', 'user_id');
    }

    public function activeLots(): HasMany
    {
        return $this->lots()
            ->where('status', WalletLot::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now());
    }

    public function expiringLotsW30(): HasMany
    {
        return $this->lots()
            ->where('status', WalletLot::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30));

    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id', 'user_id');
    }

    public function creditTransactions(): HasMany
    {
        return $this->transactions()->where('direction', WalletTransaction::DIRECTION_CREDIT);
    }

    public function debitTransactions(): HasMany
    {
        return $this->transactions()
            ->where('direction', WalletTransaction::DIRECTION_DEBIT);
    }

    /**
     * Relationship with wallet locks
     */
    public function locks(): HasMany
    {
        return $this->hasMany(WalletLock::class);
    }

    /**
     * Relationship with active locks
     */
    public function activeLocks(): HasMany
    {
        return $this->hasMany(WalletLock::class)->active();
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(WalletTransaction::class, 'user_id', 'user_id')
            ->latest();
    }
    /**
     * Check if wallet is locked
     */
    public function getIsLockedAttribute(): bool
    {
        return $this->status === self::STATUS_LOCKED || $this->activeLocks()->exists();
    }

    /**
     * Get latest lock
     */
    public function latestLock(): HasOne
    {
        return $this->hasOne(WalletLock::class)->latest();
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->total_available >= $amount;
    }

    public function hasPendingTransactions(): bool
    {
        return $this->total_pending > 0;
    }

    /**
     * Get total spent amount in current month
     */
    public function getCurrentMonthSpentAttribute(): float
    {
        return $this->debitTransactions()
            ->where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
    }

    /*Attributes*/
    public function getSpendableBalanceAttribute(): float
    {
        if ($this->is_locked) {
            return 0;
        }
        return $this->total_available;
    }

    // Total deposited for a lifetime
    public function getTotalDepositedAttribute(): float
    {
        return $this->creditTransactions()
            ->whereIn('type', ['gift_card_redeem', 'refund_credit', 'admin_adjustment'])
            ->sum('amount');
    }

    // Total spent for a lifetime
    public function getTotalSpentAttribute(): float
    {
        return $this->debitTransactions()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get expiring soon balance (within 30 days)
     */
    public function getExpiringSoonBalanceAttribute(): float
    {
        return $this->expiringLots()->sum('remaining');
    }

    public function getBalanceBySourceAttribute(): array
    {
        return $this->activeLots()
            ->selectRaw('source, SUM(remaining) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->is_locked;
    }

    /* Scopes */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLocked($query)
    {
        return $query->where('status', self::STATUS_LOCKED)
            ->orWhereHas('activeLocks');
    }

    public function scopeWithMinBalance($query, float $amount)
    {
        return $query->where('total_available', '>=', $amount);
    }

    public function scopeWithExpiringFunds($query, int $days = 30)
    {
        return $query->whereHas('lots', function ($q) use ($days) {
            $q->where('status', WalletLot::STATUS_ACTIVE)
                ->where('remaining', '>', 0)
                ->where('expires_at', '<=', now()->addDays($days))
                ->where('expires_at', '>', now());
        });
    }

    public function lock(string $reason, ?string $notes = null, ?int $lockedBy = null): WalletLock
    {
        $this->update(['status' => self::STATUS_LOCKED]);

        return WalletLock::create([
            'wallet_id' => $this->id,
            'locked_by' => $lockedBy ?? auth()->id() ?? 0,
            'reason' => $reason,
            'notes' => $notes,
            'expires_at' => null
        ]);
    }

    public function unlock(string $reason, ?int $unlockedBy = null): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);

        // Resolve active locks
        $this->activeLocks()->update([
            'resolved_at' => now(),
            'notes' => $this->activeLocks()->first()->notes ?
                $this->activeLocks()->first()->notes . "\nUnlocked: " . $reason :
                "Unlocked: " . $reason
        ]);
    }

    /* Accessors */
    /**
     * Get wallet statistics
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_deposited' => $this->total_deposited,
            'total_spent' => $this->total_spent,
            'current_balance' => $this->total_available,
            'pending_balance' => $this->total_pending,
            'expiring_soon' => $this->expiring_soon_balance,
            'transaction_count' => $this->transactions()->count(),
            'active_lots' => $this->activeLots()->count(),
            'last_activity' => $this->last_activity_at
        ];
    }

    protected function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->total_available, 2) . ' ' . $this->currency
        );
    }

    /**
     * Get total deposited amount in current month
     */
    public function getCurrentMonthDepositedAttribute(): float
    {
        return $this->creditTransactions()
            ->whereIn('type', ['gift_card_redeem', 'refund_credit', 'admin_adjustment'])
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
    }

    /**
     * Get expiring soon lots count (within 30 days)
     */
    public function getExpiringSoonLotsCountAttribute(): int
    {
        return $this->lots()
            ->where('status', WalletLot::STATUS_ACTIVE)
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->count();
    }

    /**
     * Get total transactions count
     */
    public function getTransactionsCountAttribute(): int
    {
        return $this->transactions()->count();
    }

    /**
     * Get last transaction date
     */
    public function getLastTransactionDateAttribute(): ?string
    {
        $lastTransaction = $this->transactions()
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastTransaction?->created_at?->toISOString();
    }

    /**
     * Get last updated date (wallet or transactions)
     */
    public function getLastUpdatedDateAttribute(): string
    {
        return max(
            $this->updated_at?->toISOString(),
            $this->last_transaction_date
        ) ?? $this->created_at->toISOString();
    }

    /**
     * Get monthly spending trend
     */
    public function getMonthlySpendingTrendAttribute(): array
    {
        $spending = DB::table('wallet_transactions')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total_spent')
            )
            ->where('user_id', $this->user_id)
            ->where('direction', 'DR')
            ->where('status', 'completed')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        return $spending->map(function ($item) {
            return [
                'period' => Carbon::create($item->year, $item->month)->format('M Y'),
                'amount' => (float) $item->total_spent,
                'formatted_amount' => number_format($item->total_spent, 2) . ' AED'
            ];
        })->toArray();
    }

    /**
     * Accessor for formatted current month spent
     */
    protected function formattedCurrentMonthSpent(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->current_month_spent, 2) . ' AED'
        );
    }

    /**
     * Accessor for formatted last updated date
     */
    protected function formattedLastUpdatedDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->last_updated_date) {
                    return 'Never';
                }

                $date = Carbon::parse($this->last_updated_date);
                return $date->diffForHumans() . ' (' . $date->format('M j, Y H:i') . ')';
            }
        );
    }

    protected static function booted()
    {
        parent::boot();
        // Auto create wallet when a user is created
        static::created(function ($wallet) {
            if(empty($wallet->currency)){
                $wallet->currency = 'AED';
            }
            if(empty($wallet->status)){
                $wallet->status = self::STATUS_ACTIVE;
            }
        });

        // Prevent deletion if wallet has transactions
        static::deleting(function ($wallet) {
            if ($wallet->transactions()->exists() || $wallet->locks()->exists()) {
                throw new \Exception('Cannot delete wallet with transaction history');
            }
        });
    }
}
