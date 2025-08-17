<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['sent_from_id', 'sent_from_role_id', 'sent_to_id', 'sent_to_role_id', 'notify_title', 'notify_body', 'read_at'];
}
