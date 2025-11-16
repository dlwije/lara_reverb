<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServicePackage extends Model
{
    use HasFactory;
    protected $fillable = ['service_name', 'description', 'cost', 'price', 'vehicle_type_id', 'is_active'];

    public static function getProductNames($term, $warehouse_id, $limit = 5){
        $pro_tbl_nm = (new ProductSMA)->getTable();
        $prod = ProductSMA::query()
            ->select($pro_tbl_nm.'.*', 'sma_warehouses_products.quantity')
            ->leftJoin('sma_warehouses_products','sma_warehouses_products.product_id','=',$pro_tbl_nm.'.id')
            ->groupBy($pro_tbl_nm.'.id')
            ->whereRaw("(".$pro_tbl_nm.".name LIKE ? OR ".$pro_tbl_nm.".code LIKE ? OR  concat(".$pro_tbl_nm.".name, ' (', ".$pro_tbl_nm.".code, ')') LIKE ? )", ["%$term%", "%$term%", "%$term%",])
            ->limit($limit);

        if ($prod->count() > 0){
            foreach ($prod->get() as $item) {
                $data[] = $item;
            }
            return $data;
        }
    }

    public static function getProductOptions($product_id, $warehouse_id, $all = null)
    {
        $pro_vari_tbl_nm = (new ProductVariantSMA())->getTable();

        $wpv = "( SELECT option_id, warehouse_id, quantity from sma_warehouses_products_variants WHERE product_id = {$product_id}) FWPV ";
        $prod_vari = ProductVariantSMA::query()
            ->select(DB::raw($pro_vari_tbl_nm.'.id as id'), DB::raw($pro_vari_tbl_nm.'.name as name'), DB::raw($pro_vari_tbl_nm.'.price as price'), DB::raw($pro_vari_tbl_nm.'.quantity as total_quantity'), DB::raw('FWPV.quantity as quantity'))
            ->leftJoin(DB::raw($wpv),'FWPV.option_id','=',$pro_vari_tbl_nm.'.id')
            ->where($pro_vari_tbl_nm.'.product_id', $product_id)
            ->groupBy($pro_vari_tbl_nm.'.id');

        if (!$all) {
            $prod_vari->where('FWPV.warehouse_id', $warehouse_id);
            $prod_vari->where('FWPV.quantity','>', 0);
        }

        if($prod_vari->count() > 0){
            foreach ($prod_vari->get() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public static function getProductComboItems($pid, $warehouse_id = null){

        $com_tbl_nm = (new ComboItemSMA())->getTable();
        $pro_tbl_nm = (new ProductSMA())->getTable();

        $com_que = ComboItemSMA::query()
            ->select(DB::raw($pro_tbl_nm.'.id as id'), DB::raw($com_tbl_nm.'.item_code as code'), DB::raw($com_tbl_nm.'.quantity as qty'), DB::raw($pro_tbl_nm.'.name as name'), DB::raw($pro_tbl_nm.'.type as type'), DB::raw('sma_warehouses_products.quantity as quantity'))
            ->leftJoin($pro_tbl_nm, $pro_tbl_nm.'.code','=',$com_tbl_nm.'.item_code')
            ->leftJoin('sma_warehouses_products','sma_warehouses_products.product_id','=',$pro_tbl_nm.'.id')
            ->where('sma_warehouses_products.warehouse_id', $warehouse_id)
            ->groupBy($com_tbl_nm.'.id');

        $com_que->where($com_tbl_nm.'.product_id', $pid);

        if ($com_que->count() > 0) {
            foreach (($com_que->get()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
        return false;
    }
}
