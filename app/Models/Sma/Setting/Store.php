<?php

namespace App\Models\Sma\Setting;

use App\Core\Notifiable;
use App\Models\Model;
use App\Models\Sma\Order\Payment;
use App\Models\Sma\Order\Purchase;
use App\Models\Sma\Order\Sale;
use App\Models\Sma\Product\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notification;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

class Store extends Model
{
    use Notifiable;
    use HasFactory;

    protected $with = ['state', 'country', 'account:id,title'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class)->withoutGlobalScopes();
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class)->withoutGlobalScopes();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->withoutGlobalScopes();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class)->whereNull('variation_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function scopeActive($query)
    {
        $query->where('active', 1);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['name', 'phone', 'email'], 'like', "%$search%");
    }

    public function routeNotificationForMail(Notification $notification): array|string
    {
        if (! $this->email) {
            throw new Exception('Store does not have an email address.');
        }

        return $this->email;
    }
}
