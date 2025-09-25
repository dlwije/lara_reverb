<?php

namespace Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Wallet\Database\Factories\NotificationPreferenceFactory;

class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'channels',
        'enabled'
    ];

    protected $casts = [
        'channels' => 'array',
        'enabled' => 'boolean'
    ];

    // Notification types constants
    const TYPE_TRANSACTION = 'transaction';
    const TYPE_EXPIRY_REMINDER = 'expiry_reminder';
    const TYPE_PROMOTIONAL = 'promotional';
    const TYPE_SECURITY = 'security';
    const TYPE_SYSTEM = 'system';
    const TYPE_ACHIEVEMENTS = 'achievements';

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for enabled preferences
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if channel is enabled
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels ?? []);
    }

    /**
     * Add channel to preferences
     */
    public function addChannel(string $channel): void
    {
        $channels = $this->channels ?? [];
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            $this->channels = $channels;
            $this->save();
        }
    }

    /**
     * Remove channel from preferences
     */
    public function removeChannel(string $channel): void
    {
        $channels = $this->channels ?? [];
        $channels = array_diff($channels, [$channel]);
        $this->channels = array_values($channels);
        $this->save();
    }

    /**
     * Get all available notification types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_TRANSACTION => 'Transaction Notifications',
            self::TYPE_EXPIRY_REMINDER => 'Expiry Reminders',
            self::TYPE_PROMOTIONAL => 'Promotional Offers',
            self::TYPE_SECURITY => 'Security Alerts',
            self::TYPE_SYSTEM => 'System Notifications',
            self::TYPE_ACHIEVEMENTS => 'Achievement Notifications'
        ];
    }

    /**
     * Get description for notification type
     */
    public function getTypeDescription(): string
    {
        return self::getAvailableTypes()[$this->type] ?? ucfirst($this->type);
    }
}
