<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderServPkgItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'jo_serv_pkg_id',
        'service_pkg_id',
        'product_id',
        'product_code',
        'product_name',
        'product_type',
        'item_option_id',
        'net_unit_price',
        'net_unit_price_old',
        'unit_price',
        'unit_price_old',
        'quantity',
        'warehouse_id',
        'item_tax',
        'tax_rate_id',
        'tax',
        'discount',
        'item_discount',
        'subtotal',
        'real_unit_price',
        'real_unit_price_old',
        'sale_item_id',
        'product_unit_id',
        'product_unit_code',
        'unit_quantity',
        'comment',
    ];
}
