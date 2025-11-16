<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $table = 'login_log';
    protected $fillable = [
        'user_id', 'ip_address', 'device_type', 'device_model', 'os',
        'browser', 'login_type', 'location', 'successful', 'logged_in_at',
    ];

    public $timestamps = false; // We are using 'logged_in_at' instead
}
