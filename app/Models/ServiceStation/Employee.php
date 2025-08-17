<?php

namespace App\Models\ServiceStation;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $fillable = ['emp_no','first_name','last_name','full_name','pro_img','is_active'];

    public function users(){
        return $this->hasMany(User::class,'emp_id');
    }
}
