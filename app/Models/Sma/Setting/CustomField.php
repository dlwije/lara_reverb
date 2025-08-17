<?php

namespace App\Models\Sma\Setting;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomField extends Model
{
    use HasFactory;

    public $casts = ['models' => 'array', 'options' => 'array'];

    public static $types = [
        'text', 'select', 'radio', 'checkbox', 'textarea', 'date', 'time', 'number', 'email', 'url',
    ];

    public static $models = [
        'adjustment', 'customer', 'delivery', 'expense', 'payment', 'product', 'purchase',
        'quotation', 'return_order', 'sale', 'stock_count', 'store', 'supplier', 'transfer',
    ];

    public function customizable()
    {
        return $this->morphTo();
    }

    public function forceDelete()
    {
        log_activity(__('{record} has permanently deleted.', ['record' => 'Custom field']), $this, $this, 'Custom field');

        return parent::forceDelete();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['trashed'] ?? 'with', fn ($q, $t) => $q->trashed($t))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search));
    }

    public function scopeOfModel($query, string $model)
    {
        $query->where('models', 'like', "%{$model}%")->orderBy('order_no', 'asc');
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['code', 'models'], 'like', "%$search%");
    }
}
