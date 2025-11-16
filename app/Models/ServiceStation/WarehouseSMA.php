<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseSMA extends Model
{
    use HasFactory;

    protected $table = "sma_warehouses";

    protected $fillable = ['code', 'name', 'address'];
}
