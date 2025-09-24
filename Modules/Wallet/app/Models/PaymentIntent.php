<?php

namespace Botble\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Wallet\Database\Factories\PaymentIntentFactory;

class PaymentIntent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): PaymentIntentFactory
    // {
    //     // return PaymentIntentFactory::new();
    // }
}
