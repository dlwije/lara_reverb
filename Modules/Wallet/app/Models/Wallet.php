<?php

namespace Botble\Wallet\Models;

use App\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends BaseModel
{
    protected $table = 'wallets';

    protected $fillable = [
        'name',
        'status',
        'user_id',
        'total_available',
        'total_pending',
        'status',
        'currency',
        'last_activity_at'
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
        'name' => SafeContent::class,
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

    /* Attributes */

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
        return $this->transactions()->where('status', WalletTransaction::STATUS_COMPLETED)->count();
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
}
