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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Modules\Chat\Models\ChatMessage;
use Mpociot\Teamwork\Traits\UserHasTeams;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, UserHasTeams;
    use HasAwardPoints;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
