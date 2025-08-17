<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItemSMA extends Model
{
    use HasFactory;
    protected $table = "sma_sale_items";
    protected $fillable = [
        'id',
        'sale_id',
        'service_pkg_id',
        'old_invoice_master_id',
        'product_id',
        'item_add_by',
        'product_type',
        'product_code',
        'product_name',
        'option_id',
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
        'serial_no',
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
    ];
}
