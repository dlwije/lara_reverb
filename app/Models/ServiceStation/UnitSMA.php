<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitSMA extends Model
{
    use HasFactory;
    protected $table = "sma_units";

    public static function getUnitByID($id)
    {
        $q = self::where('id', $id)->first(); // ✅ this returns an object or null

        if ($q) {
            return $q; // object
        }

        return null; // or you can return a default object if needed
    }
}
