<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskSource extends Model
{
    use HasFactory;

    protected $fillable = ['task_source_name'];
}
