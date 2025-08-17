<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItemSMA extends Model
{
    use HasFactory;

    protected $table = "sma_purchase_items";
    protected $fillable = [
        'purchase_id',
        'transfer_id',
        'product_id',
        'product_code',
        'product_name',
        'option_id',
        'net_unit_cost',
        'quantity',
        'warehouse_id',
        'item_tax',
        'tax_rate_id',
        'tax',
        'discount',
        'item_discount',
        'expiry',
        'subtotal',
        'quantity_balance',
        'date',
        'status',
        'unit_cost',
        'real_unit_cost',
        'quantity_received',
        'supplier_part_no',
        'purchase_item_id',
        'product_unit_id',
        'product_unit_code',
        'unit_quantity',
        'gst',
        'cgst',
        'sgst',
        'igst',
        'base_unit_cost',
    ];
}
