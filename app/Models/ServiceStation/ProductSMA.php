<?php

namespace App\Models\ServiceStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductSMA extends Model
{
    use HasFactory;

    protected $table = "sma_products";

    protected $fillable = [
        'code',
        'name',
        'unit',
        'cost',
        'price',
        'alert_quantity',
        'image',
        'category_id',
        'subcategory_id',
        'cf1',
        'cf2',
        'cf3',
        'cf4',
        'cf5',
        'cf6',
        'quantity',
        'tax_rate',
        'track_quantity',
        'details',
        'warehouse',
        'barcode_symbology',
        'file',
        'product_details',
        'tax_method',
        'type',
        'supplier1',
        'supplier1price',
        'supplier2',
        'supplier2price',
        'supplier3',
        'supplier3price',
        'supplier4',
        'supplier4price',
        'supplier5',
        'supplier5price',
        'promotion',
        'promo_price',
        'start_date',
        'end_date',
        'supplier1_part_no',
        'supplier2_part_no',
        'supplier3_part_no',
        'supplier4_part_no',
        'supplier5_part_no',
        'sale_unit',
        'purchase_unit',
        'brand',
        'slug',
        'featured',
        'weight',
        'hsn_code',
        'views',
        'hide',
        'second_name',
        'hide_pos',
    ];

    public static function getProductByCode($code){
        return self::where('code',$code)->first();
    }

    public static function getProductNames($term, $warehouse_id, $pos = false, $limit = 5) {

        $pro_tbl_nm = (new ProductSMA)->getTable();
        $cate_tbl_nm = (new CategorySMA)->getTable();
        $wp = "( SELECT product_id, warehouse_id, quantity as quantity from sma_warehouses_products ) FWP";
        $prod = self::query()->select($pro_tbl_nm.'.*',DB::raw('FWP.quantity as quantity'), DB::raw($cate_tbl_nm.'.id as category_id'), DB::raw($cate_tbl_nm.'.name as category_name'))
            ->leftJoin(DB::raw($wp),'FWP.product_id','=',$pro_tbl_nm.'.id')
            ->leftJoin($cate_tbl_nm,$cate_tbl_nm.'.id', '=', $pro_tbl_nm.'.category_id')
            ->groupBy($pro_tbl_nm.'.id');

        if (SettingSMA::getSettingSma()->overselling) {
            $prod->whereRaw("(".$pro_tbl_nm.".name LIKE ? OR ".$pro_tbl_nm.".code LIKE ? OR  concat(".$pro_tbl_nm.".name, ' (', ".$pro_tbl_nm.".code, ')') LIKE ? )", ["%$term%", "%$term%", "%$term%",]);
        } else {
            $prod->whereRaw("(((".$pro_tbl_nm.".track_quantity = 0 OR FWP.quantity > 0) AND FWP.warehouse_id = '" . $warehouse_id . "') OR {$pro_tbl_nm}.type != 'standard') AND "
                . "({$pro_tbl_nm}.name LIKE '%" . $term . "%' OR {$pro_tbl_nm}.code LIKE '%" . $term . "%' OR  concat({$pro_tbl_nm}.name, ' (', {$pro_tbl_nm}.code, ')') LIKE '%" . $term . "%')");
        }
        // $this->db->order_by('products.name ASC');
        if ($pos) {
            $prod->where('hide_pos', '!=', 1);
        }
        $prod->limit($limit);

        if ($prod->count() > 0){
            foreach ($prod->get() as $item) {
                $data[] = $item;
            }
            return $data;
        }
    }

    public static function getProductOptions($product_id, $warehouse_id, $all = null){

        $pro_vari_tbl_nm = (new ProductVariantSMA())->getTable();

        $wpv = "( SELECT option_id, warehouse_id, quantity from sma_warehouses_products_variants WHERE product_id = {$product_id}) FWPV ";
        $prod_vari = ProductVariantSMA::query()
        ->select(DB::raw($pro_vari_tbl_nm.'.id as id'), DB::raw($pro_vari_tbl_nm.'.name as name'), DB::raw($pro_vari_tbl_nm.'.price as price'), DB::raw($pro_vari_tbl_nm.'.quantity as total_quantity'), DB::raw('FWPV.quantity as quantity'))
        ->leftJoin(DB::raw($wpv),'FWPV.option_id','=',$pro_vari_tbl_nm.'.id')
        ->where($pro_vari_tbl_nm.'.product_id', $product_id)
        ->groupBy($pro_vari_tbl_nm.'.id');

        if (!SettingSMA::getSettingSma()->overselling && !$all) {
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

    public static function getProductOptionByID($id){
        $q = ProductVariantSMA::where('id',$id)->get();

        if($q->count() > 0){
            return $q[0];
        }
        return false;
    }

    public static function getPurchasedItems($product_id, $warehouse_id, $option_id = null, $nonPurchased = false){
        $orderby = empty(SettingSMA::getSettingSma()->accounting_method) ? 'asc' : 'desc';

        $purch_prod = PurchaseItemSMA::query()
        ->select('id', 'purchase_id', 'transfer_id', 'quantity', 'quantity_balance', 'net_unit_cost', 'unit_cost', 'item_tax', 'base_unit_cost')
        ->where('product_id', $product_id)->where('warehouse_id', $warehouse_id)->where('quantity_balance','!=', 0);

        if (!isset($option_id) || empty($option_id)) {

            $purch_prod->where(function ($query) {
               $query->where('option_id', null)->orWhere('option_id', 0);
            });
        } else {
            $purch_prod->where('option_id', $option_id);
        }
        if ($nonPurchased) {
            $purch_prod->where(function ($query) {
                $query->where('purchase_id !=', null)->orWhere('transfer_id !=', null);
            });
        }
        $purch_prod->where(function ($query) {
            $query->where('status', 'received')->orWhere('status', 'partial');
        });

        $purch_prod->groupBy('id');
        $purch_prod->orderBy('date', $orderby);
        $purch_prod->orderBy('purchase_id', $orderby);

        if ($purch_prod->count() > 0) {
            foreach (($purch_prod->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public static function getPurchasedItem($clause)
    {
        $orderby = empty(SettingSMA::getSettingSma()->accounting_method) ? 'asc' : 'desc';
        $qu = PurchaseItemSMA::query()
            ->orderBy('date', $orderby)
            ->orderBy('purchase_id', $orderby);

        if (!isset($clause['option_id']) || empty($clause['option_id'])) {
            $qu->where(function ($query) {
                $query->where('option_id', null)->orWhere('option_id', 0);
            });
        }
        $qu->where($clause);
        if ($qu->count() > 0) {
            return $qu->first();
        }
        return false;
    }

    public static function isPromo($product)
    {
        if (is_array($product)) {
            $product = json_decode(json_encode($product), false);
        }
        $today = date('Y-m-d');
        return $product->promotion && $product->start_date <= $today && $product->end_date >= $today && $product->promo_price;
    }

    public static function getProductGroupPrice($product_id, $group_id){
        $pp = ProductPriceSMA::where('price_group_id', $group_id)->where('product_id', $product_id);
        if($pp->count() > 0){
            return $pp[0];
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
        ->groupBy($com_tbl_nm.'.id');

        if ($warehouse_id) {
            $com_que->where('sma_warehouses_products.warehouse_id', $warehouse_id);
        }
        $com_que->where($com_tbl_nm.'.product_id', $pid);

        if ($com_que->count() > 0) {
            foreach (($com_que->get()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
        return false;
    }

    public static function getUnitsByBUID($base_unit){
        $q= UnitSMA::where('id', $base_unit)->orWhere('base_unit', $base_unit)->groupBy('id')->orderBy('id','asc');

        if($q->count() > 0){
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public static function getProductByID($id)
    {
        $q = self::where('id',$id)->get();
        if ($q->count() > 0) {
            return $q[0];
        }
        return false;
    }

    public static function getWarehouseProduct($warehouse_id, $product_id)
    {
        $q = self::where(['id' => $product_id, 'warehouse' => $warehouse_id])->get();
        if ($q->count() > 0) {
            return $q[0];
        }
        return false;
    }

    public static function getProductVariants($product_id)
    {
        $q = ProductVariantSMA::where(['product_id' => $product_id]);
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

}
