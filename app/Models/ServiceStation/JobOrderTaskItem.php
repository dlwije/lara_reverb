<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderTaskItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'jo_id',
        'jo_task_id',
        'product_id',
        'product_code',
        'product_name',
        'product_type',
        'item_option_id',
        'net_unit_price',
        'unit_price',
        'quantity',
        'warehouse_id',
        'item_tax',
        'tax_rate_id',
        'tax',
        'discount',
        'item_discount',
        'subtotal',
        'real_unit_price',
        'sale_item_id',
        'product_unit_id',
        'product_unit_code',
        'unit_quantity',
        'comment',
        'gst',
        'cgst',
        'sgst',
        'igst',
        'created_by',
    ];
}
