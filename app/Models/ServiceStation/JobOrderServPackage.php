<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderServPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'jo_id',
        'service_pkg_id',
        'service_name',
        'description',
        'vehi_type_id',
        'cost',
        'price',
        'sub_total',
        'is_approve_tech',
        'jo_serv_pkg_status',
        'is_started',
        'is_ended',
        'is_finished',
    ];
}
