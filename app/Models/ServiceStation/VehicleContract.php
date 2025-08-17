<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_no',
        'con_company_name',
        'contract_no',
        'expire_date',
        'mileage',
        'phone_number',
        'wo_id',
        'customer_id',
        'vehicle_id',
    ];
}
