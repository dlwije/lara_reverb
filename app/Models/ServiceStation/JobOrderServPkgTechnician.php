<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderServPkgTechnician extends Model
{
    use HasFactory;

    protected $fillable = ['jo_id', 'jo_serv_pkg_id', 'tech_id'];
}
