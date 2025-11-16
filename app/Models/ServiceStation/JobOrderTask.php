<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderTask extends Model
{
    use HasFactory;
    protected $fillable = [
        'jo_id',
        'task_id',
        'task_name',
        'task_description',
        'hours',
        'hourly_rate',
        'actual_hours',
        'labor_cost',
        'total',
        'is_approve_cost',
        'is_approve_work',
        'jo_tsk_status',
        'is_started',
        'is_ended',
        'is_finished',
        'is_estimate',
    ];
}
