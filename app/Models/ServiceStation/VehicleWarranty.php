<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleWarranty extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_no',
        'war_company_name',
        'warranty_no',
        'date_of_purchase',
        'expire_date',
        'phone_number',
        'wo_id',
        'customer_id',
        'vehicle_id',
    ];
}
