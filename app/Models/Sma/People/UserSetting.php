<?php

namespace App\Models\Sma\People;

use App\Models\User;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserSetting extends Model
{
    use HasFactory;

    public static $hasUser = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeOfUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }
}
