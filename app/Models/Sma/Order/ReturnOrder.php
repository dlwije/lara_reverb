<?php

namespace App\Models\Sma\Order;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Scopes\OfStore;
use App\Traits\HasAttachments;
use App\Models\Sma\Setting\Tax;
use App\Models\Sma\Setting\Store;
use App\Models\Sma\People\Customer;
use App\Models\Sma\People\Supplier;
use App\Observers\ReturnOrderObserver;
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[ScopedBy(OfStore::class)]
#[ObservedBy(ReturnOrderObserver::class)]
class ReturnOrder extends Model
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
            'date'             => AppDate::class,
            'created_at'       => AppDate::class . ':time',
            'updated_at'       => AppDate::class . ':time',
        ];
    }

    public function items()
    {
        return $this->hasMany(ReturnOrderItem::class);
    }

    public function payments(): MorphToMany
    {
        return $this->morphToMany(Payment::class, 'payable');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Return Order']), $this, $this, 'Return Order');
        $this->items->each->forceDelete();

        return parent::forceDelete();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->ofType($type))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->ofUser($user_id))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['sale_id'] ?? null, fn ($query, $sale_id) => $query->ofSale($sale_id))
            ->when($filters['purchase_id'] ?? null, fn ($query, $purchase_id) => $query->ofPurchase($purchase_id))
            ->when($filters['customer_id'] ?? null, fn ($query, $customer_id) => $query->ofCustomer($customer_id))
            ->when($filters['supplier_id'] ?? null, fn ($query, $supplier_id) => $query->ofSupplier($supplier_id))
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereRelation('items', 'product_id', $products))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', 'like', "%{$reference}%"));
    }

    public function scopeOfSale($query, $sale_id)
    {
        $query->where('sale_id', $sale_id);
    }

    public function scopeOfPurchase($query, $purchase_id)
    {
        $query->where('purchase_id', $purchase_id);
    }

    public function scopeOfCustomer($query, $customer_id)
    {
        $query->where('customer_id', $customer_id);
    }

    public function scopeOfSupplier($query, $supplier_id)
    {
        $query->where('supplier_id', $supplier_id);
    }

    public function scopeOfStore($query, $store_id = null)
    {
        $query->where('store_id', $store_id ?: session('selected_store_id'));
    }

    public function scopeOfType($query, $type)
    {
        $query->where('type', $type);
    }

    public function scopeOfUser($query, $user_id = null)
    {
        $query->where('user_id', $user_id ?: auth()->id());
    }

    public function scopeSearch($query, $s)
    {
        $query->where(fn ($q) => $q->where('date', 'like', "%{$s}%")
            ->orWhere('reference', 'like', "%{$s}%"))
            ->orWhereRelation('customer', 'name', 'like', "%{$s}%")
            ->orWhereRelation('supplier', 'name', 'like', "%{$s}%");
    }
}
