<?php

namespace App\Models\Sma\Pos;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Sma\Order\Sale;
use App\Models\Sma\Setting\Store;
use App\Models\Sma\People\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    public static $hasUser = true;

    public static $hasStore = true;

    public static $hasRegister = true;

    public static $userRecords = true;

    public $casts = ['data' => 'array'];

    public $with = ['customer:id,name', 'user:id,name'];

    protected function casts(): array
    {
        return [
            'created_at' => AppDate::class . ':time',
            'updated_at' => AppDate::class . ':time',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'order_number', 'number');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'not', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->ofUser($user_id))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['customer_id'] ?? null, fn ($query, $customer_id) => $query->ofCustomer($customer_id))
            ->when($filters['start_date'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($query, $date) => $query->where('created_at', '<=', $date))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $query->latest();
        } else {
            if (str($sort)->contains('.')) {
                $relation_tables = [
                    'customer' => ['table' => 'customers', 'model' => 'App\Models\Sma\People\Customer'],
                ];
                [$relation, $column] = explode('.', $sort);
                [$column, $direction] = explode(':', $column);
                $table = $relation_tables[$relation];
                $query->orderBy($table['model']::select($column)->whereColumn($table['table'] . '.id', 'orders.' . $relation . '_id'), $direction);
            } else {
                [$column, $direction] = explode(':', $sort);
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    public function scopeOfCustomer($query, $customer_id)
    {
        $query->where('customer_id', $customer_id);
    }

    public function scopeOfStore($query, $store_id = null)
    {
        $query->where('store_id', $store_id ?: session('selected_store_id'));
    }

    public function scopeOfUser($query, $user_id = null)
    {
        $query->where('user_id', $user_id ?: auth()->id());
    }

    public function scopeSearch($query, $s)
    {
        $query->where(fn ($q) => $q->where('date', 'like', "%{$s}%")
            ->orWhere('reference', 'like', "%{$s}%"))
            ->orWhereRelation('customer', 'company', 'like', "%{$s}%");
    }

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Sale']), $this, $this, 'Sale');

        return parent::forceDelete();
    }
}
