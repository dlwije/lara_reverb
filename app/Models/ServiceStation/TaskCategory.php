<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    use HasFactory;

    protected $table = "task_categories";
    protected $fillable = ['task_category_name'];
}
