<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderRefSMA extends Model
{
    use HasFactory;
    protected $table = "sma_order_ref";

    public function getRandomReference($len = 12)
    {
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $result .= mt_rand(0, 9);
        }

        if ($this->getSaleByReference($result)) {
            $this->getRandomReference();
        }

        return $result;
    }

    public static function getReference($field)
    {
        $q = DB::table('sma_order_ref')->where('ref_id', '1')->get();
        if ($q->count() > 0) {
            $ref = $q[0];
            switch ($field) {
                case 'so':
                    $prefix = SettingSMA::getSettingSma()->sales_prefix;
                    break;
                case 'pos':
                    $prefix = isset(SettingSMA::getSettingSma()->sales_prefix) ? SettingSMA::getSettingSma()->sales_prefix . '/POS' : '';
                    break;
                case 'qu':
                    $prefix = SettingSMA::getSettingSma()->quote_prefix;
                    break;
                case 'po':
                    $prefix = SettingSMA::getSettingSma()->purchase_prefix;
                    break;
                case 'to':
                    $prefix = SettingSMA::getSettingSma()->transfer_prefix;
                    break;
                case 'do':
                    $prefix = SettingSMA::getSettingSma()->delivery_prefix;
                    break;
                case 'pay':
                    $prefix = SettingSMA::getSettingSma()->payment_prefix;
                    break;
                case 'ppay':
                    $prefix = SettingSMA::getSettingSma()->ppayment_prefix;
                    break;
                case 'ex':
                    $prefix = SettingSMA::getSettingSma()->expense_prefix;
                    break;
                case 're':
                    $prefix = SettingSMA::getSettingSma()->return_prefix;
                    break;
                case 'rep':
                    $prefix = SettingSMA::getSettingSma()->returnp_prefix;
                    break;
                case 'qa':
                    $prefix = SettingSMA::getSettingSma()->qa_prefix;
                    break;
                default:
                    $prefix = '';
            }

            $ref_no = $prefix;

            if (SettingSMA::getSettingSma()->reference_format == 1) {
                $ref_no .= date('Y') . '/' . sprintf('%04s', $ref->{$field});
            } elseif (SettingSMA::getSettingSma()->reference_format == 2) {
                $ref_no .= date('Y') . '/' . date('m') . '/' . sprintf('%04s', $ref->{$field});
            } elseif (SettingSMA::getSettingSma()->reference_format == 3) {
                $ref_no .= sprintf('%04s', $ref->{$field});
            } else {
                $ref_no .= self::getRandomReference();
            }

            return $ref_no;
        }
        return false;
    }

    public function getSaleByReference($ref)
    {
        //whereRaw("DATE_FORMAT(updated_at, '%Y-%m-%d') like ?", ["%$keyword%"]);
        $q = SaleSMA::query()
            ->whereRaw("reference_no like ?", ["%$ref%"])
            ->get();
        if ($q->count() > 0) {
            return $q[0];
        }
        return false;
    }

    public static function updateReference($field)
    {
        $q = self::where('ref_id', '1')->get();
        if ($q->count() > 0) {
            $ref = $q->first();
            self::where('ref_id', '1')->update([$field => $ref->{$field} + 1], ['ref_id' => '1']);
            return true;
        }
        return false;
    }
}
