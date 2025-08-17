<?php

namespace App\Models\Sma\Order;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Sma\Pos\Order;
use App\Models\Scopes\OfStore;
use App\Traits\HasAttachments;
use App\Models\Sma\Setting\Tax;
use App\Observers\SaleObserver;
use App\Models\Sma\Pos\Register;
use App\Models\Sma\Setting\Store;
use App\Models\Sma\People\Customer;
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[ScopedBy(OfStore::class)]
#[ObservedBy(SaleObserver::class)]
class Sale extends Model
{
    use HasFactory;
    use HasAttachments;
    use HasSchemalessAttributes;

    public static $hasUser = true;

    public static $hasStore = true;

    public static $hasRegister = true;

    public static $userRecords = true;

    public static $hasReference = true;

    // protected $hidden = ['total_cost'];

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
            'date'             => AppDate::class,
            'due_date'         => AppDate::class,
            'created_at'       => AppDate::class . ':time',
            'updated_at'       => AppDate::class . ':time',
        ];
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class)->latestOfMany();
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): MorphToMany
    {
        return $this->morphToMany(Payment::class, 'payable')
            ->withPivot('amount')->withoutGlobalScope(OfStore::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_number', 'number');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function returnOrders()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * To check if the model is due
     */
    public function isDue()
    {
        return $this->due_date && $this->due_date->lt(now());
    }

    /**
     * To check if the model is due
     */
    public function isDueToday()
    {
        return $this->due_date && $this->due_date->isToday();
    }

    /**
     * Check if model is paid
     */
    public function isPaid()
    {
        return $this->grand_total - ($this->rounding ?: 0) >= $this->paid;
    }

    /**
     * Check if model is unpaid
     */
    public function isUnpaid()
    {
        return $this->grand_total - ($this->rounding ?: 0) < $this->paid;
    }

    /**
     * Query scope to get due models
     */
    public function scopeDue($query)
    {
        return $query->unpaid()
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->toDateString());
    }

    /**
     * Query scope to get models due today
     */
    public function scopeDueToday($query)
    {
        return $query->unpaid()
            ->whereNotNull('due_date')
            ->where('due_date', now()->toDateString());
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['paid'] ?? null, fn ($query) => $query->paid())
            ->when($filters['overdue'] ?? null, fn ($query) => $query->due())
            ->when($filters['unpaid'] ?? null, fn ($query) => $query->unpaid())
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->ofUser($user_id))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['customer_id'] ?? null, fn ($query, $customer_id) => $query->ofCustomer($customer_id))
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereHas('items', fn ($q) => $q->whereIn('product_id', $products)))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', 'like', "%{$reference}%"))
            ->when($filters['start_date'] ?? null, fn ($query, $date) => $query->where('date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($query, $date) => $query->where('date', '<=', $date));
    }

    public function scopePaid($query)
    {
        $query->whereRaw('paid >= grand_total-rounding');
    }

    public function scopeUnpaid($query)
    {
        $query->whereNull('paid')->orWhereRaw('paid < grand_total-rounding');
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
        $this->items->each->forceDelete();

        return parent::forceDelete();
    }

    protected static function booted()
    {
        parent::booted();

        static::retrieved(function (Sale $sale) {
            $user = auth()->user();
            if ($user && $user->cant('show-cost')) {
                $sale->setHidden(['total_cost']);
            }
        });
    }
}
