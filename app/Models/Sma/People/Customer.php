<?php

namespace App\Models\Sma\People;

use App\Core\Notifiable;
use App\Models\Model;
use App\Models\Sma\Order\Payment;
use App\Models\Sma\Order\ReturnOrder;
use App\Models\Sma\Order\Sale;
use App\Traits\HasAwardPoints;
use App\Traits\Trackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

class Customer extends Model
{
    use Trackable;
    use Notifiable;
    use HasFactory;
    use HasAwardPoints;

    public static $hasUser = true;

    protected $appends = ['balance', 'points'];

    protected $with = ['state', 'country', 'customerGroup', 'priceGroup'];

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function returnOrder()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function priceGroup()
    {
        return $this->belongsTo(PriceGroup::class);
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
            ->when(($filters['overdue'] ?? null) == 1, fn ($query) => $query->whereHasBalanceAbove('due_limit'))
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
        } elseif (str($sort)->contains('.')) {
            $relation_tables = [
                'customer_group' => ['table' => 'customer_groups', 'model' => 'App\Models\Sma\People\CustomerGroup'],
                'price_group'    => ['table' => 'price_groups', 'model' => 'App\Models\Sma\People\PriceGroup'],
            ];
            [$relation, $column] = explode('.', $sort);
            [$column, $direction] = explode(':', $column);
            $table = $relation_tables[$relation];
            $query->orderBy($table['model']::select($column)->whereColumn($table['table'] . '.id', 'customers.' . $relation . '_id'), $direction);
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
