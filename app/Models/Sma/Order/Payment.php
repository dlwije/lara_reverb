<?php

namespace App\Models\Sma\Order;

use App\Casts\AppDate;
use App\Models\Model;
use App\Models\Sma\People\Customer;
use App\Models\Sma\People\Supplier;
use App\Models\Sma\Pos\Register;
use App\Models\Sma\Setting\Store;
use App\Models\User;
use App\Traits\HasAttachments;
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Payment extends Model
{
    use HasFactory;
    use HasAttachments;
    use HasSchemalessAttributes;

    public static $hasUser = true;

    public static $hasStore = true;

    public static $hasRegister = true;

    public static $userRecords = true;

    public static $hasReference = true;

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
            'method_data'      => 'array',
            'date'             => AppDate::class,
            'created_at'       => AppDate::class . ':time',
            'updated_at'       => AppDate::class . ':time',
        ];
    }

    public function purchases(): MorphToMany
    {
        return $this->morphedByMany(Purchase::class, 'payable');
    }

    public function sales(): MorphToMany
    {
        return $this->morphedByMany(Sale::class, 'payable')->withPivot('amount');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfCustomer($query, $customer_id)
    {
        $query->where('customer_id', $customer_id);
    }

    public function scopeOfMethod($query, $method)
    {
        $query->where('method', $method);
    }

    public function scopeOfStore($query, $store_id = null)
    {
        $query->where('store_id', $store_id ?: session('selected_store_id', config('app.default_store_id')));
    }

    public function scopeOfUser($query, $user_id = null)
    {
        $query->where('user_id', $user_id ?: auth()->id());
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->ofMethod($method))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->ofUser($user_id))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['customer_id'] ?? null, fn ($query, $customer_id) => $query->ofCustomer($customer_id))
            ->when($filters['start_date'] ?? null, fn ($query, $date) => $query->where('date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($query, $date) => $query->where('date', '<=', $date));
    }

    public function scopeSearch($query, $s)
    {
        $query->where(fn ($q) => $q->where('date', 'like', "%{$s}%")
            ->orWhere('reference', 'like', "%{$s}%"))
            ->orWhereRelation('customer', 'company', 'like', "%{$s}%")
            ->orWhereRelation('supplier', 'company', 'like', "%{$s}%");
    }

    public function scopeReceived($query)
    {
        $query->where('received', true);
    }

    public function scopeNotReceived($query)
    {
        $query->whereNull('received')->orWhere('received', false);
    }

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Payment']), $this, $this, 'Payment');

        return parent::forceDelete();
    }
}
