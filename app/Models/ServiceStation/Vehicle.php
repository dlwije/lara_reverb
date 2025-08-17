<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'old_cus_db_id',
        'customer_id',
        'make_year',
        'make_year',
        'vehicle_make',
        'vehicle_model',
        'vehicle_no',
        'chassis_no',
        'engine_no',
        'vehicle_type_id',
        'vehicle_type_state',
        'sub_model',
        'body',
        'color',
        'odometer',
        'unit_number',
        'transmission',
        'is_active',
    ];

    public function scopeActive($query, $tbl_nm = ''){
        if(!empty($tbl_nm))
            return $query->where($tbl_nm.'.is_active',1);
        else
            return $query->where('is_active',1);

    }
}
