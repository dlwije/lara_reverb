<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServPkgTechComment extends Model
{
    use HasFactory;
    protected $fillable = ['tech_id', 'jo_id', 'jo_serv_pkg_id', 'message', 'msg_state', 'user_id', 'user_role_id'];
}
