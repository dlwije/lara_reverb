<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'jo_id',
        'task_id',
        'task_name',
        'task_description',
        'hours',
        'hourly_rate',
        'actual_hours',
        'labor_cost',
        'total',
    ];
}
