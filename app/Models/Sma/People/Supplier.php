<?php

namespace App\Models\Sma\People;

use App\Models\Model;
use App\Core\Notifiable;
use App\Traits\Trackable;
use Nnjeim\World\Models\State;
use Nnjeim\World\Models\Country;
use App\Models\Sma\Order\Payment;
use App\Models\Sma\Order\Purchase;
use App\Models\Sma\Order\ReturnOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use Trackable;
    use Notifiable;
    use HasFactory;

    public static $hasUser = true;

    protected $appends = ['balance'];

    protected $with = ['state', 'country'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function returnOrder()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function scopeFilter($query, $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['name', 'company', 'email', 'phone'], 'like', "%$search%");
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $query->latest();
        } else {
            [$column, $direction] = explode(':', $sort);
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    protected static function booted()
    {
        parent::booted();

        static::created(function ($model) {
            $model->mutateTracking($model->opening_balance ?: 0, [
                'description' => 'Opening Balance',
            ]);
        });
    }
}
