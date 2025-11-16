<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSMA extends Model
{
    use HasFactory;
    protected $table = "customers";

    protected $fillable = [
        'group_id',
        'group_name',
        'customer_group_id',
        'customer_group_name',
        'name',
        'first_name',
        'last_name',
        'spouse_first_name',
        'spouse_last_name',
        'company',
        'vat_no',
        'address',
        'city',
        'city_id',
        'state',
        'state_id',
        'postal_code',
        'country',
        'country_id',
        'phone',
        'email',
        'cf1',
        'cf2',
        'cf3',
        'cf4',
        'cf5',
        'cf6',
        'invoice_footer',
        'payment_term',
        'logo',
        'award_points',
        'deposit_amount',
        'price_group_id',
        'price_group_name'
    ];

    public static function getCompanyByID($id){
        $q = self::where('id',$id)->get();
        if($q->count() > 0){
            return $q[0];
        }else
            return array();
    }
}
