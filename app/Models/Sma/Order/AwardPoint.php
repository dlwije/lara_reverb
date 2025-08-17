<?php

namespace App\Models\Sma\Order;

use App\Models\User;
use App\Models\Model;
use App\Casts\AppDate;
use App\Models\Sma\Setting\Store;
use App\Models\Sma\People\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AwardPoint extends Model
{
    use HasFactory;

    // public static $hasUser = true;

    public static $hasStore = true;

    protected function casts(): array
    {
        return [
            'created_at' => AppDate::class . ':time',
            'updated_at' => AppDate::class . ':time',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Delivery']), $this, $this, 'Delivery');

        return parent::forceDelete();
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
        $query->where('details', 'like', "%{$s}%");
    }
}
