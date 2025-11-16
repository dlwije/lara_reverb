<?php

namespace Modules\PromoRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\PromoRules\Database\Factories\UserSegmentFactory;

class UserSegment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): UserSegmentFactory
    // {
    //     // return UserSegmentFactory::new();
    // }
}
