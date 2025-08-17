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
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ScopedBy(OfStore::class)]
class Quotation extends Model
{
    use HasFactory;
    use HasAttachments;
    use HasSchemalessAttributes;

    public static $hasUser = true;

    public static $hasStore = true;

    public static $userRecords = true;

    public static $hasReference = true;

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
            'date'             => AppDate::class,
            'emailed_at'       => AppDate::class . ':time',
            'converted_at'     => AppDate::class . ':time',
            'created_at'       => AppDate::class . ':time',
            'updated_at'       => AppDate::class . ':time',
        ];
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->ofUser($user_id))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['customer_id'] ?? null, fn ($query, $customer_id) => $query->ofCustomer($customer_id))
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereRelation('items', 'product_id', $products))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', 'like', "%{$reference}%"));
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
}
