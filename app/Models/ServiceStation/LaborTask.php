<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaborTask extends Model
{
    use HasFactory;

    protected $fillable = ['task_code', 'task_name', 'task_description', 'source_id', 'category_id', 'task_type_id', 'task_hour', 'normal_rate', 'warranty_rate', 'is_active'];
}
