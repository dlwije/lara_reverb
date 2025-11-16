<?php

namespace App\Models\Sma\Setting;

use App\Models\Model;
use App\Traits\Trackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use Trackable;
    use HasFactory;

    protected $appends = ['balance'];

    public static array $types = ['Assets', 'Expenses', 'Liabilities', 'Equity', 'Revenue'];

    public function scopeActive($query)
    {
        $query->where('active', 1);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['title', 'description'], 'like', "%$search%");
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
