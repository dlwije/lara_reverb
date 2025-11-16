<?php

namespace App\Models\Sma\Product;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Scopes\OfStore;
use App\Traits\HasAttachments;
use App\Models\Sma\Setting\Store;
use App\Traits\HasSchemalessAttributes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ScopedBy(OfStore::class)]
class StockCount extends Model
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
            'brands'           => 'array',
            'categories'       => 'array',
            'extra_attributes' => 'array',
            'date'             => AppDate::class,
            'adjusted_at'      => AppDate::class . ':time',
            'completed_at'     => AppDate::class . ':time',
            'created_at'       => AppDate::class . ':time',
            'updated_at'       => AppDate::class . ':time',
        ];
    }

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
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
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->byUser($user_id))
            ->when($filters['reference'] ?? null, fn ($query, $reference) => $query->where('reference', 'like', "%{$reference}%"))
            ->when($filters['sort'] ?? null, fn ($query, $sort) => $query->sort($sort));
    }

    public function scopeSearch($query, $s)
    {
        $query->where(fn ($q) => $q->where('type', 'like', "%{$s}%"))
            ->orWhere('details', 'like', "%{$s}%")
            ->orWhere('reference', 'like', "%{$s}%")
            ->orWhereRelation('user', 'name', 'like', "%{$s}%");
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

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Stock Count']), $this, $this, 'Stock Count');
        $this->items->each->forceDelete();

        return parent::forceDelete();
    }
}
