<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSMA extends Model
{
    use HasFactory;
    protected $table = "sma_payments";

    protected $fillable = [
        'date',
        'sale_id',
        'return_id',
        'purchase_id',
        'reference_no',
        'transaction_id',
        'paid_by',
        'cheque_no',
        'cc_no',
        'cc_holder',
        'cc_month',
        'cc_year',
        'cc_type',
        'amount',
        'currency',
        'created_by',
        'attachment',
        'type',
        'note',
        'pos_paid',
        'pos_balance',
        'approval_code',
    ];
}
