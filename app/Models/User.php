<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\AppDate;
use App\Models\Sma\Order\Purchase;
use App\Models\Sma\Order\Sale;
use App\Models\Sma\People\UserSetting;
use App\Models\Sma\Pos\Order;
use App\Models\Sma\Pos\Register;
use App\Models\Sma\Setting\Store;
use App\Traits\HasAwardPoints;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Modules\Chat\Models\ChatMessage;
use Modules\Wallet\Models\NotificationPreference;
use Mpociot\Teamwork\Traits\UserHasTeams;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, UserHasTeams;
    use HasAwardPoints, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'phone', 'email', 'username', 'password', 'locale',
        'customer_id', 'supplier_id', 'store_id', 'employee', 'active',
        'bulk_actions',  'edit_all', 'read-all', 'can_be_impersonated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes', 'two_factor_secret'
    ];
    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
//            'email_verified_at' => 'datetime',
            'email_verified_at' => AppDate::class . ':time',
            'created_at'        => AppDate::class . ':time',
            'updated_at'        => AppDate::class . ':time',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function openedRegister()
    {
        return $this->hasOne(Register::class)->whereNull('closed_at');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class);
    }

    public function registers()
    {
        return $this->hasMany(Register::class);
    }

    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }

    public function scopeActive($query)
    {
        $query->where('active', 1);
    }

    public function scopeEmployee($query)
    {
        $query->where('employee', 1);
    }

    public function scopeOfCompany($query, $company_id = null)
    {
        return $query->where('company_id', get_company_id($company_id));
    }

    public function scopeFilter($query, $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['name', 'phone', 'email', 'username'], 'like', "%$search%");
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $query->latest();
        } elseif (str($sort)->contains('.')) {
            [$relation, $column] = explode('.', $sort);
            [$column, $direction] = explode(':', $column);
            $query->withAggregate($relation, $column)->orderBy($relation . '_' . $column, $direction);
        } else {
            [$column, $direction] = explode(':', $sort);
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    public function scopeTrashed($query, $value)
    {
        if (in_array($value, ['with', 'only'])) {
            return $query->{$value . 'Trashed'}();
        }

        return $query;
    }

    /* Wallet Notification*/
    /**
     * Relationship with notification preferences
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Get notification channels for a specific type
     */
    public function getNotificationChannels(string $type): array
    {
        $preference = $this->notificationPreferences()
            ->where('type', $type)
            ->where('enabled', true)
            ->first();

        return $preference ? $preference->channels : $this->getDefaultChannels($type);
    }

    /**
     * Check if notification is enabled for a type
     */
    public function isNotificationEnabled(string $type): bool
    {
        $preference = $this->notificationPreferences()
            ->where('type', $type)
            ->first();

        return $preference ? $preference->enabled : true; // Default to enabled
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreference(string $type, array $channels, bool $enabled = true): void
    {
        $this->notificationPreferences()->updateOrCreate(
            ['type' => $type],
            ['channels' => $channels, 'enabled' => $enabled]
        );
    }

    /**
     * Get default channels for notification types
     */
    private function getDefaultChannels(string $type): array
    {
        $defaults = [
            'transaction' => ['mail', 'database'],
            'expiry_reminder' => ['mail'],
            'promotional' => ['mail'],
            'security' => ['mail', 'database'], // Login alerts, etc.
            'system' => ['database'] // System maintenance, updates
        ];

        return $defaults[$type] ?? ['mail'];
    }

    /**
     * Get all notification settings for user
     */
    public function getNotificationSettings(): array
    {
        $types = ['transaction', 'expiry_reminder', 'promotional', 'security', 'system', 'achievements'];
        $settings = [];

        foreach ($types as $type) {
            $preference = $this->notificationPreferences()
                ->where('type', $type)
                ->first();

            $settings[$type] = [
                'enabled' => $preference ? $preference->enabled : true,
                'channels' => $preference ? $preference->channels : $this->getDefaultChannels($type),
                'available_channels' => $this->getAvailableChannelsForType($type)
            ];
        }

        return $settings;
    }

    /**
     * Get available channels for notification type
     */
    private function getAvailableChannelsForType(string $type): array
    {
        $available = [
            'mail' => 'Email',
            'database' => 'In-App',
            'broadcast' => 'Push Notification',
        ];

        // Add SMS if configured
        if (config('wallet.notifications.sms_enabled')) {
            $available['sms'] = 'SMS';
        }

        return $available;
    }

    /**
     * Bulk update notification preferences
     */
    public function updateNotificationPreferences(array $preferences): void
    {
        foreach ($preferences as $type => $settings) {
            if (isset($settings['enabled']) && isset($settings['channels'])) {
                $this->updateNotificationPreference(
                    $type,
                    $settings['channels'],
                    $settings['enabled']
                );
            }
        }
    }

    /**
     * Route notifications for specific channels
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    public function routeNotificationForSms($notification)
    {
        return $this->phone; // Make sure you have a phone field
    }

    /*Notification list*/
    /**
     * Get the user's notifications with eager loading
     */
    public function loadNotifications($limit = 15)
    {
        return $this->notifications()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread notifications count (cached for performance)
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return cache()->remember(
            "user.{$this->id}.unread_notifications_count",
            now()->addMinutes(5),
            function () {
                return $this->unreadNotifications()->count();
            }
        );
    }

    /**
     * Get latest notifications for dashboard
     */
    public function getRecentNotificationsAttribute()
    {
        return $this->notifications()
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Check if user has unread notifications
     */
    public function getHasUnreadNotificationsAttribute(): bool
    {
        return $this->unread_notifications_count > 0;
    }

    /* END: Wallet Notification*/

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($model) {
            if (! $model->company_id) {
                $model->company_id = get_company_id();
            }
        });

        static::saving(function ($model) {
            if ($model->all_permissions) {
                unset($model->all_permissions);
            }
        });
    }
}
