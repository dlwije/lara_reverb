<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositSMA extends Model
{
    use HasFactory;

    protected $table = "sma_deposits";

    protected $fillable = ['date', 'company_id', 'amount', 'paid_by', 'note', 'sys_type', 'created_by', 'updated_by'];
}
