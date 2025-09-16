<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// use Modules\Wallet\Database\Factories\WalletFactory;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

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

    /**
     * Check if wallet is locked
     */
    public function getIsLockedAttribute(): bool
    {
        return $this->status === 'locked' || $this->activeLocks()->exists();
    }

    /**
     * Get latest lock
     */
    public function latestLock(): HasOne
    {
        return $this->hasOne(WalletLock::class)->latest();
    }
}
