<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderTaskTechTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'jo_task_id',
        'tech_id',
        'tech_id',
        'date',
        'start_datetime',
        'end_datetime',
        'session_tot_time',
        'is_started',
        'is_ended',
        'is_finished',
    ];
}
