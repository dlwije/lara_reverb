<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;
    protected $table = "vehicle_types";

    protected $fillable = ['type_name', 'is_active'];
}
