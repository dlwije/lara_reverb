<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SettingSMA extends Model
{
    use HasFactory;

    protected $table = "settings";

    public static function getSettingSma(){

        return self::select('*')->first();
    }
}
