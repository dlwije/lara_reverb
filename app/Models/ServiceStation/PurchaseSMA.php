<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseSMA extends Model
{
    use HasFactory;
    protected $table = 'sma_purchases';

    protected $fillable = [
        'reference_no',
        'date',
        'supplier_id',
        'supplier',
        'warehouse_id',
        'note',
        'total',
        'product_discount',
        'order_discount_id',
        'order_discount',
        'total_discount',
        'product_tax',
        'order_tax_id',
        'order_tax',
        'total_tax',
        'shipping',
        'grand_total',
        'paid',
        'status',
        'payment_status',
        'created_by',
        'updated_by',
        'updated_at',
        'attachment',
        'payment_term',
        'due_date',
        'return_id',
        'surcharge',
        'return_purchase_ref',
        'purchase_id',
        'return_purchase_total',
        'cgst',
        'sgst',
        'igst',
    ];

    public static function getPurchaseByID($id)
    {
        $q = self::where(['id' => $id]);
        if ($q->count() > 0) {
            return $q->first();
        }
        return false;
    }

    public static function getPurchasePayments($purchase_id)
    {
        $payments = PaymentSMA::where('purchase_id', $purchase_id)
            ->get();

        if ($payments->isNotEmpty()) {
            return $payments; // returns a Collection of results
        }

        return false;
    }
}
