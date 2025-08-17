<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'serial_no',
        'wo_order_id',
        'job_type',
        'jo_date',
        'jo_status',
        'is_active',
        'is_estimate',
        'is_approve_cost',
        'is_approve_work',
    ];

    public function workorder(){
        $this->hasOne(WorkOrder::class,'wo_order_id');
    }

    public static function analyze_term($term){
        $spos = strpos($term,SettingSMA::getSettingSma()->barcode_separator);
        if ($spos !== false) {
            $st        = explode(SettingSMA::getSettingSma()->barcode_separator, $term);
            $sr        = trim($st[0]);
            $option_id = trim($st[1]);
        } else {
            $sr        = $term;
            $option_id = false;
        }

        $barcode = (new JobOrder)->parse_scale_barcode($sr);
        if (!is_array($barcode)) {
            return ['term' => $sr, 'option_id' => $option_id];
        }
        return ['term' => $barcode['item_code'], 'option_id' => $option_id, 'quantity' => $barcode['weight'], 'price' => $barcode['price'], 'strict' => $barcode['strict'] ? (ProductSMA::getProductByCode($barcode['item_code']) ? false : true) : false];

    }

    public function parse_scale_barcode($barcode)
    {
        if (strlen($barcode) == SettingSMA::getSettingSma()->ws_barcode_chars) {
            $product = ProductSMA::getProductByCode($barcode);
            if ($product) {
                return $barcode;
            }
            $price  = false;
            $weight = false;
            if (SettingSMA::getSettingSma()->ws_barcode_type == 'price') {
                try {
                    $price = substr($barcode, SettingSMA::getSettingSma()->price_start - 1, SettingSMA::getSettingSma()->price_chars);
                    $price = SettingSMA::getSettingSma()->price_divide_by ? $price / SettingSMA::getSettingSma()->price_divide_by : $price;
                } catch (\Exception $e) {
                    $price = 0;
                }
            } else {
                try {
                    $weight = substr($barcode, SettingSMA::getSettingSma()->weight_start - 1, SettingSMA::getSettingSma()->weight_chars);
                    $weight = SettingSMA::getSettingSma()->weight_divide_by ? $weight / SettingSMA::getSettingSma()->weight_divide_by : $weight;
                } catch (\Exception $e) {
                    $weight = 0;
                }
            }
            $item_code = substr($barcode, SettingSMA::getSettingSma()->item_code_start - 1, SettingSMA::getSettingSma()->item_code_chars);

            return ['item_code' => $item_code, 'price' => $price, 'weight' => $weight, 'strict' => true];
        }
        return $barcode;
    }

}
