<?php

namespace Modules\GiftCard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\GiftCard\Database\Factories\GiftCardFactory;

class GiftCard extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): GiftCardFactory
    // {
    //     // return GiftCardFactory::new();
    // }
}
