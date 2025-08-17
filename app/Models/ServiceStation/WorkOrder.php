<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_no',
        'customer_id',
        'vehicle_id',
        'wo_date',
        'appointment_id',
        'mileage',
        'reported_defect',
//        'warranty_id',
//        'contract_id',
//        'insurance_id',
        'is_warranty',
        'is_contract',
        'is_insurance',
        'wo_status',
        'wo_type',
        'is_active',
    ];
}
