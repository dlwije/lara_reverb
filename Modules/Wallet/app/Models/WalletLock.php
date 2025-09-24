<?php

namespace Botble\Wallet\Models;

use App\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLock extends BaseModel
{
    protected $table = 'wallet_locks';

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

    public function isActive(): bool
    {
        return is_null($this->resolved_at) &&
            (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at->isPast();
    }

    public function isPermanent(): bool
    {
        return is_null($this->expires_at);
    }

    public function isTemporary(): bool
    {
        return !is_null($this->expires_at);
    }

    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    public function getTimeUntilExpirationAttribute(): ?string
    {
        if ($this->isTemporary() && !$this->isExpired() && !$this->isResolved()) {
            return $this->expires_at->diffForHumans();
        }

        return null;
    }

    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

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

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('resolved_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopePermanent($query)
    {
        return $query->whereNull('expires_at');
    }

    public function scopeTemporary($query)
    {
        return $query->whereNotNull('expires_at');
    }

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
}
