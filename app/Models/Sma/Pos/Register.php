<?php

namespace App\Models\Sma\Pos;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Sma\Order\Sale;
use App\Models\Sma\Order\Expense;
use App\Models\Sma\Order\Payment;
use App\Models\Sma\Setting\Store;
use App\Models\Sma\Order\Purchase;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Register extends Model
{
    use HasFactory;

    public static $hasUser = true;

    public static $hasStore = true;

    public static $userRecords = true;

    public $with = ['store', 'user:id,name'];

    protected function casts(): array
    {
        return [
            'closed_at'  => AppDate::class . ':time',
            'created_at' => AppDate::class . ':time',
            'updated_at' => AppDate::class . ':time',
        ];
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeClosed($query)
    {
        $query->whereNotNull('closed_at');
    }

    public function scopeOpen($query)
    {
        $query->whereNull('closed_at')->orWhere('closed_at', '');
    }

    public function scopeOfUser($query, $user_id)
    {
        $query->where('user_id', $user_id);
    }

    public function scopeOfStore($query, $store = null)
    {
        return $query->where('store_id', $store ?? session('selected_store_id'));
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'not', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search))
            ->when($filters['user_id'] ?? null, fn ($query, $user_id) => $query->ofUser($user_id))
            ->when($filters['store_id'] ?? null, fn ($query, $store_id) => $query->ofStore($store_id))
            ->when($filters['end_date'] ?? null, fn ($query, $end) => $query->where('created_at', '<=', $end))
            ->when($filters['start_date'] ?? null, fn ($query, $start) => $query->where('created_at', '>=', $start));
    }

    public function scopeSearch($query, $s)
    {
        $query->where(fn ($q) => $q->whereRelation('user', 'name', 'like', "%{$s}%")
            ->orWhereRelation('store', 'name', 'like', "%{$s}%"));
    }
}
