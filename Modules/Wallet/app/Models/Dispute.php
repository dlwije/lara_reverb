<?php

namespace Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// use Modules\Wallet\Database\Factories\DisputeFactory;

class Dispute extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id', 'transaction_id', 'reason', 'status', 'priority',
        'resolution', 'resolved_by', 'resolved_at', 'notes'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Status constants
    const STATUS_OPEN = 'open';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CANCELLED = 'cancelled';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Resolution constants
    const RESOLUTION_REFUND = 'refund';
    const RESOLUTION_PARTIAL_REFUND = 'partial_refund';
    const RESOLUTION_REJECTED = 'rejected';
    const RESOLUTION_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'transaction_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    /**
     * Relationship with latest evidence (convenience method)
     */
    public function latestEvidence(): HasOne
    {
        return $this->hasOne(DisputeEvidence::class)->latest();
    }

    /**
     * Check if dispute is open
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if dispute is under review
     */
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if dispute is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if dispute is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if dispute requires urgent attention
     */
    public function isUrgent(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Get the dispute amount from related transaction
     */
    public function getAmountAttribute(): float
    {
        return $this->transaction->amount ?? 0;
    }

    /**
     * Get the dispute currency from related transaction
     */
    public function getCurrencyAttribute(): string
    {
        return $this->transaction->currency ?? 'AED';
    }

    /**
     * Get the time since dispute was created (human readable)
     */
    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the resolution time in hours
     */
    public function getResolutionTimeAttribute(): ?float
    {
        if ($this->resolved_at && $this->created_at) {
            return round($this->created_at->diffInHours($this->resolved_at), 2);
        }

        return null;
    }

    /**
     * Scope for open disputes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
    Scope for under review disputes
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    /**
     * Scope for resolved disputes
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope for high priority disputes
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Scope for disputes created within a date range
     */
    public function scopeCreatedBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope for disputes resolved within a date range
     */
    public function scopeResolvedBetween($query, $from, $to)
    {
        return $query->whereBetween('resolved_at', [$from, $to]);
    }

    /**
     * Scope for disputes by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for disputes with specific resolution
     */
    public function scopeWithResolution($query, $resolution)
    {
        return $query->where('resolution', $resolution);
    }

    /**
     * Get status options for forms
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * Get priority options for forms
     */
    public static function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical'
        ];
    }

    /**
     * Get resolution options for forms
     */
    public static function getResolutionOptions(): array
    {
        return [
            self::RESOLUTION_REFUND => 'Full Refund',
            self::RESOLUTION_PARTIAL_REFUND => 'Partial Refund',
            self::RESOLUTION_REJECTED => 'Rejected',
            self::RESOLUTION_CANCELLED => 'Cancelled'
        ];
    }

    /**
     * Accessor for formatted status
     */
    protected function statusFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst(str_replace('_', ' ', $this->status))
        );
    }

    /**
     * Accessor for formatted priority
     */
    protected function priorityFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst($this->priority)
        );
    }

    /**
     * Accessor for formatted resolution
     */
    protected function resolutionFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolution ? ucfirst(str_replace('_', ' ', $this->resolution)) : null
        );
    }

    /**
     * Check if dispute can be reopened
     */
    public function canBeReopened(): bool
    {
        return $this->isResolved() &&
            $this->resolved_at->gt(now()->subDays(7)); // Within 7 days of resolution
    }

    /**
     * Check if dispute has evidence
     */
    public function hasEvidence(): bool
    {
        return $this->evidence()->exists();
    }

    /**
     * Get the first evidence file (if any)
     */
    public function getFirstEvidenceAttribute(): ?DisputeEvidence
    {
        return $this->evidence()->first();
    }

    /**
     * Get evidence count
     */
    public function getEvidenceCountAttribute(): int
    {
        return $this->evidence()->count();
    }
}
