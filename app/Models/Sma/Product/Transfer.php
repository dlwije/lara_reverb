<?php

namespace App\Models\Sma\Product;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Scopes\OfStore;
use App\Traits\HasAttachments;
use App\Models\Sma\Setting\Store;
use App\Observers\TransferObserver;
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ScopedBy(OfStore::class)]
#[ObservedBy(TransferObserver::class)]
class Transfer extends Model
{
    use HasFactory;
    use HasAttachments;
    use HasSchemalessAttributes;

    public static $hasUser = true;

    public static $hasStore = true;

    public static $hasReference = true;

    public static $userRecords = true;

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
        return $this->hasMany(TransferItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Transfer']), $this, $this, 'Transfer');
        $this->items->each->forceDelete();

        return parent::forceDelete();
    }

    public function scopeByUser($query, $user_id)
    {
        $query->where('user_id', $user_id);
    }

    public function scopeOfType($query, $type)
    {
        $query->where('type', $type);
    }

    public function scopeOfStore($query, $store = null)
    {
        return $query->where('store_id', $store ?? session('selected_store_id'));
    }

    public function scopeFilter($query, $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['to_store_id'] ?? null, fn ($query, $store_id) => $query->where('to_store_id', $store_id))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->byUser($user_id))
            ->when($filters['products'] ?? null, fn ($query, $products) => $query->whereRelation('items', 'product_id', $products))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', 'like', "%{$reference}%"))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSearch($query, $s)
    {
        $query->where(fn ($q) => $q->where('reference', 'like', "%{$s}%")
            ->orWhere('details', 'like', "%{$s}%"))
            ->orWhereRelation('user', 'name', 'like', "%{$s}%");
    }

    public function scopeSort($query, $sort)
    {
        if ($sort == 'latest') {
            $query->latest();
            // } elseif ($sort == 'items_count') {
        } else {
            [$column, $direction] = explode(':', $sort);
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}
