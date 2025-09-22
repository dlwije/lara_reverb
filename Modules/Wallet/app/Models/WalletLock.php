<?php

namespace Modules\Wallet\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Wallet\Database\Factories\WalletLockFactory;

class WalletLock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'wallet_id', 'locked_by', 'reason', 'notes', 'expires_at', 'resolved_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Lock reason constants
    const REASON_DISPUTE = 'dispute';
    const REASON_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const REASON_KYC_REQUIRED = 'kyc_required';
    const REASON_MANUAL_REVIEW = 'manual_review';
    const REASON_LEGAL_HOLD = 'legal_hold';
    const REASON_SYSTEM_ALERT = 'system_alert';
    const REASON_USER_REQUEST = 'user_request';

    /**
     * Relationship with Wallet
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Relationship with User who created the lock
     */
    public function locksmith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Check if lock is active
     */
    public function isActive(): bool
    {
        return is_null($this->resolved_at) &&
            (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    /**
     * Check if lock is expired
     */
    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at->isPast();
    }

    /**
     * Check if lock is permanent
     */
    public function isPermanent(): bool
    {
        return is_null($this->expires_at);
    }

    /**
     * Check if lock is temporary
     */
    public function isTemporary(): bool
    {
        return !is_null($this->expires_at);
    }

    /**
     * Check if lock is resolved
     */
    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    /**
     * Get time until expiration (human readable)
     */
    public function getTimeUntilExpirationAttribute(): ?string
    {
        if ($this->isTemporary() && !$this->isExpired() && !$this->isResolved()) {
            return $this->expires_at->diffForHumans();
        }

        return null;
    }

    /**
     * Get time since creation (human readable)
     */
    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get duration of lock in hours
     */
    public function getDurationHoursAttribute(): ?float
    {
        if ($this->isResolved()) {
            return round($this->created_at->diffInHours($this->resolved_at), 2);
        }

        if ($this->isActive()) {
            return round($this->created_at->diffInHours(now()), 2);
        }

        return null;
    }

    /**
     * Scope for active locks
     */
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired locks
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('resolved_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope for resolved locks
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope for permanent locks
     */
    public function scopePermanent($query)
    {
        return $query->whereNull('expires_at');
    }

    /**
     * Scope for temporary locks
     */
    public function scopeTemporary($query)
    {
        return $query->whereNotNull('expires_at');
    }

    /**
     * Scope for locks by reason
     */
    public function scopeByReason($query, $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope for locks created within a date range
     */
    public function scopeCreatedBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope for locks expiring within a date range
     */
    public function scopeExpiringBetween($query, $from, $to)
    {
        return $query->whereBetween('expires_at', [$from, $to]);
    }

    /**
     * Get reason options for forms
     */
    public static function getReasonOptions(): array
    {
        return [
            self::REASON_DISPUTE => 'Dispute',
            self::REASON_SUSPICIOUS_ACTIVITY => 'Suspicious Activity',
            self::REASON_KYC_REQUIRED => 'KYC Required',
            self::REASON_MANUAL_REVIEW => 'Manual Review',
            self::REASON_LEGAL_HOLD => 'Legal Hold',
            self::REASON_SYSTEM_ALERT => 'System Alert',
            self::REASON_USER_REQUEST => 'User Request'
        ];
    }

    /**
     * Accessor for formatted reason
     */
    protected function reasonFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst(str_replace('_', ' ', $this->reason))
        );
    }

    /**
     * Accessor for lock status
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isResolved()) {
                    return 'resolved';
                }
                if ($this->isExpired()) {
                    return 'expired';
                }
                if ($this->isActive()) {
                    return $this->isPermanent() ? 'permanent' : 'temporary';
                }
                return 'unknown';
            }
        );
    }

    /**
     * Accessor for formatted status
     */
    protected function statusFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->status) {
                    'resolved' => 'Resolved',
                    'expired' => 'Expired',
                    'permanent' => 'Permanent',
                    'temporary' => 'Temporary',
                    default => 'Unknown'
                };
            }
        );
    }

    /**
     * Resolve the lock
     */
    public function resolve(): bool
    {
        return $this->update(['resolved_at' => now()]);
    }

    /**
     * Extend lock expiration
     */
    public function extend(Carbon $newExpiration): bool
    {
        if ($this->isPermanent()) {
            return false;
        }

        return $this->update(['expires_at' => $newExpiration]);
    }

    /**
     * Convert temporary lock to permanent
     */
    public function makePermanent(): bool
    {
        return $this->update(['expires_at' => null]);
    }

    /**
     * Check if lock can be extended
     */
    public function canBeExtended(): bool
    {
        return $this->isTemporary() && $this->isActive();
    }

    /**
     * Check if lock can be resolved
     */
    public function canBeResolved(): bool
    {
        return $this->isActive();
    }

    /**
     * Get the user associated with this lock through wallet
     */
    public function getUserAttribute(): ?User
    {
        return $this->wallet->user ?? null;
    }

    /**
     * Get the user email through wallet relationship
     */
    public function getUserEmailAttribute(): ?string
    {
        return $this->wallet->user->email ?? null;
    }

    /**
     * Get the wallet balance at time of locking
     */
    public function getWalletBalanceAttribute(): ?float
    {
        return $this->wallet->total_available ?? null;
    }
}
