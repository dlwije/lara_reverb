<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_no',
        'ins_company_name',
        'policy_no',
        'expire_date',
        'phone_number',
        'wo_id',
        'customer_id',
        'vehicle_id',
    ];
}
