<?php
namespace App\Services;
use App\Models\ServiceStation\CustomerSMA;
use App\Models\ServiceStation\JobOrderTaskItem;
use App\Models\ServiceStation\Notification;
use App\Models\ServiceStation\ProductSMA;
use App\Models\ServiceStation\ProductVariantSMA;
use App\Models\ServiceStation\PurchaseItemSMA;
use App\Models\ServiceStation\PurchaseSMA;
use App\Models\ServiceStation\SaleItemSMA;
use App\Models\ServiceStation\SaleSMA;
use App\Models\ServiceStation\SettingSMA;
use App\Models\ServiceStation\UnitSMA;
use App\Models\User;
use App\Models\ServiceStation\WarehouseSMA;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ControllerService
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public $dateFormats;
    protected $salt_value = '#@12we3e0$3Fq4Dwd@';
    protected $salt_length = 10;
    protected $store_salt = false;
    protected $hash_method = 'sha1';

    public function __construct()
    {
        if(empty(SettingSMA::getSettingSma()->dateformat)){
            $dateFormats = [
                'js_sdate'    => 'mm-dd-yyyy',
                'php_sdate'   => 'm-d-Y',
                'mysq_sdate'  => '%m-%d-%Y',
                'js_ldate'    => 'mm-dd-yyyy hh:ii:ss',
                'php_ldate'   => 'm-d-Y H:i:s',
                'mysql_ldate' => '%m-%d-%Y %T',
            ];
        }elseif($sd = $this->getDateFormat(SettingSMA::getSettingSma()->dateformat)){
            $dateFormats = [
                'js_sdate'    => $sd->js,
                'php_sdate'   => $sd->php,
                'mysq_sdate'  => $sd->sql,
                'js_ldate'    => $sd->js . ' hh:ii',
                'php_ldate'   => $sd->php . ' H:i',
                'mysql_ldate' => $sd->sql . ' %H:%i',
            ];
        }else {
            $dateFormats = [
                'js_sdate'    => 'mm-dd-yyyy',
                'php_sdate'   => 'm-d-Y',
                'mysq_sdate'  => '%m-%d-%Y',
                'js_ldate'    => 'mm-dd-yyyy hh:ii:ss',
                'php_ldate'   => 'm-d-Y H:i:s',
                'mysql_ldate' => '%m-%d-%Y %T',
            ];
        }
        $this->dateFormats         = $dateFormats;
    }

    public function get_frontend_url()
    {
        return env('WALLET_PAYMENT_FRONTEND_URL', url(''));
    }

    public function salt(){
        return substr(md5(uniqid(rand(), true)), 0, $this->salt_length);
    }

    public function hashPosPassword($password){
        $salt = $this->salt();
        return $salt . substr(sha1($salt . $password),0,-$this->salt_length);
    }

    public static function getDefaultValues(){
        return array(
            'warehouse_default_id' => 1,
            'biller_default_id' => 3,
            'no_tax' => 1,
            'system_admin_role_id' => 1,
            'service_manager_role_id' => 4,
            'warranty_manager_role_id' => 7,
            'service_writer_role_id' => 8,
            'cashier_role_id' => 9,
            'parts_manager_role_id' => 10,
            'technician_role_id' => 11,
            'default_currency' => 'AED'
        );
    }

    public function errorResponse($msg = '', $status=false): array
    {
        return array("status" => $status, "msg" => $msg);
    }

    public function successResponse($msg = '', $status=true): array
    {
        return array("status" => $status, "msg" => $msg);
    }
    public function responseDataJson($status, $message, $statusCode, $data=array()): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'   => $status,
            'message'  => $message,
            'data'  => $data,
            'statusCode' => (int)$statusCode
        ],$statusCode);
    }

    public function responseSelectedDataJson($status, $data, $statusCode): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status'   => $status,
            'data'  => $data,
            'statusCode' => (int)$statusCode
        ],$statusCode);
    }

    public function send_json_sma($data){
        header('Content-Type: application/json');
        die(json_encode($data));
        exit;
    }

    public function clear_tags($str){
        return htmlentities(
            strip_tags(
                $str,
                '<span><div><a><br><p><b><i><u><img><blockquote><small><ul><ol><li><hr><big><pre><code><strong><em><table><tr><td><th><tbody><thead><tfoot><h3><h4><h5><h6>'
            ),
            ENT_QUOTES | ENT_XHTML | ENT_HTML5,
            'UTF-8'
        );
    }

    public function format_the_time($seconds) {

        $hours = floor($seconds/3600);
        $seconds -= $hours*3600;
        $minutes  = floor($seconds/60);
        $seconds -= $minutes*60;

        if($seconds < 9){ $seconds = "0".$seconds; }
        if($minutes < 9){ $minutes = "0".$minutes; }
        if($hours < 9){ $hours = "0".$hours; }

        return array("rawTime"=> $hours.'.'.$minutes, "formatTime" => "{$hours}hr {$minutes}min {$seconds}sec");
    }

    public function formatDecimal($number, $decimals = null)
    {
        if (!is_numeric($number)) {
            return null;
        }
        if (!$decimals && $decimals !== 0) {
            $decimals = SettingSMA::getSettingSma()->decimals;
        }
        return number_format($number, $decimals, '.', '');
    }

    public function calculateDiscount($discount = null, $amount = 0, $order = null)
    {
        if ($discount && ($order || SettingSMA::getSettingSma()->product_discount)) {
            $dpos = strpos($discount, '%');
            if ($dpos !== false) {
                $pds = explode('%', $discount);
                return $this->formatDecimal(((($this->formatDecimal($amount)) * (float) ($pds[0])) / 100), 4);
            }
            return $this->formatDecimal($discount, 4);
        }
        return 0;
    }

    public function calculateTax($product_details = null, $tax_details = null, $custom_value = null, $c_on = null)
    {
        $value      = $custom_value ? $custom_value : (($c_on == 'cost') ? $product_details->cost : $product_details->price);
        $tax_amount = 0;
        $tax        = 0;
        if ($tax_details && $tax_details->type == 1 && $tax_details->rate != 0) {
            if ($product_details && $product_details->tax_method == 1) {
                $tax_amount = $this->formatDecimal((($value) * $tax_details->rate) / 100, 4);
                $tax        = $this->formatDecimal($tax_details->rate, 0) . '%';
            } else {
                $tax_amount = $this->formatDecimal((($value) * $tax_details->rate) / (100 + $tax_details->rate), 4);
                $tax        = $this->formatDecimal($tax_details->rate, 0) . '%';
            }
        } elseif ($tax_details && $tax_details->type == 2) {
            $tax_amount = $this->formatDecimal($tax_details->rate);
            $tax        = $this->formatDecimal($tax_details->rate, 0);
        }
        return ['id' => $tax_details ? $tax_details->id : null, 'tax' => $tax, 'amount' => $tax_amount];
    }

    public function calculateIndianGST($item_tax, $state, $tax_details)
    {
        if (SettingSMA::getSettingSma()->indian_gst) {
            $cgst = $sgst = $igst = 0;
            if ($state) {
                $gst  = $tax_details->type == 1 ? $this->formatDecimal(($tax_details->rate / 2), 0) . '%' : $this->formatDecimal(($tax_details->rate / 2), 0);
                $cgst = $this->formatDecimal(($item_tax / 2), 4);
                $sgst = $this->formatDecimal(($item_tax / 2), 4);
            } else {
                $gst  = $tax_details->type == 1 ? $this->formatDecimal(($tax_details->rate), 0) . '%' : $this->formatDecimal(($tax_details->rate), 0);
                $igst = $item_tax;
            }
            return ['gst' => $gst, 'cgst' => $cgst, 'sgst' => $sgst, 'igst' => $igst];
        }
        return [];
    }

    public function calculateOrderTax($order_tax_id = null, $amount = 0)
    {
        if (SettingSMA::getSettingSma()->tax2 != 0 && $order_tax_id) {
            if ($order_tax_details = SaleSMA::getTaxRateByID($order_tax_id)) {
                if ($order_tax_details->type == 1) {
                    return $this->formatDecimal((($amount * $order_tax_details->rate) / 100), 4);
                }
                return $this->formatDecimal($order_tax_details->rate, 4);
            }
        }
        return 0;
    }

    public function check_customer_deposit($customer_id, $amount)
    {
        $customer = CustomerSMA::getCompanyByID($customer_id);
        return $customer->deposit_amount >= $amount;
    }

    public function costing($items)
    {
        $citems = [];
//        unset($items['product_id']);
        foreach ($items as $item) {
            $option            = (isset($item['option_id']) && !empty($item['option_id']) && $item['option_id'] != 'null' && $item['option_id'] != 'false') ? $item['option_id'] : '';
            $pr                = ProductSMA::getProductByID($item['product_id']);
            $item['option_id'] = $option;
            if ($pr && $pr->type == 'standard') {
                if (isset($citems['p' . $item['product_id'] . 'o' . $item['option_id']])) {
                    $citems['p' . $item['product_id'] . 'o' . $item['option_id']]['aquantity'] += $item['quantity'];
                } else {
                    $citems['p' . $item['product_id'] . 'o' . $item['option_id']]              = $item;
                    $citems['p' . $item['product_id'] . 'o' . $item['option_id']]['aquantity'] = $item['quantity'];
                }
            } elseif ($pr && $pr->type == 'combo') {
                $wh          = $this->Settings->overselling ? null : $item['warehouse_id'];
                $combo_items = ProductSMA::getProductComboItems($item['product_id'], $wh);
                foreach ($combo_items as $combo_item) {
                    if ($combo_item->type == 'standard') {
                        if (isset($citems['p' . $combo_item->id . 'o' . $item['option_id']])) {
                            $citems['p' . $combo_item->id . 'o' . $item['option_id']]['aquantity'] += ($combo_item->qty * $item['quantity']);
                        } else {
                            $cpr = ProductSMA::getProductByID($combo_item->id);
                            if ($cpr->tax_rate) {
                                $cpr_tax = SaleSMA::getTaxRateByID($cpr->tax_rate);
                                if ($cpr->tax_method) {
                                    $item_tax       = $this->formatDecimal((($combo_item->unit_price) * $cpr_tax->rate) / (100 + $cpr_tax->rate));
                                    $net_unit_price = $combo_item->unit_price - $item_tax;
                                    $unit_price     = $combo_item->unit_price;
                                } else {
                                    $item_tax       = $this->formatDecimal((($combo_item->unit_price) * $cpr_tax->rate) / 100);
                                    $net_unit_price = $combo_item->unit_price;
                                    $unit_price     = $combo_item->unit_price + $item_tax;
                                }
                            } else {
                                $net_unit_price = $combo_item->unit_price;
                                $unit_price     = $combo_item->unit_price;
                            }
                            $cproduct                                                              = ['product_id' => $combo_item->id, 'product_name' => $cpr->name, 'product_type' => $combo_item->type, 'quantity' => ($combo_item->qty * $item['quantity']), 'net_unit_price' => $net_unit_price, 'unit_price' => $unit_price, 'warehouse_id' => $item['warehouse_id'], 'item_tax' => $item_tax, 'tax_rate_id' => $cpr->tax_rate, 'tax' => ($cpr_tax->type == 1 ? $cpr_tax->rate . '%' : $cpr_tax->rate), 'option_id' => null, 'product_unit_id' => $cpr->unit];
                            $citems['p' . $combo_item->id . 'o' . $item['option_id']]              = $cproduct;
                            $citems['p' . $combo_item->id . 'o' . $item['option_id']]['aquantity'] = ($combo_item->qty * $item['quantity']);
                        }
                    }
                }
            }
        }
        // $this->sma->print_arrays($combo_items, $citems);
        $cost = [];
        foreach ($citems as $item) {
            $item['aquantity'] = $citems['p' . $item['product_id'] . 'o' . $item['option_id']]['aquantity'];
            $cost[]            = $this->item_costing($item, true);
        }
        return $cost;
    }

    public function item_costing($item, $pi = null)
    {
        $item_quantity = $pi ? $item['aquantity'] : $item['quantity'];
        if (!isset($item['option_id']) || empty($item['option_id']) || $item['option_id'] == 'null') {
            $item['option_id'] = null;
        }

        if (SettingSMA::getSettingSma()->accounting_method != 2 && !SettingSMA::getSettingSma()->overselling) {
            if (ProductSMA::getProductByID($item['product_id'])) {
                if ($item['product_type'] == 'standard') {
                    $unit                   = UnitSMA::getUnitByID($item['product_unit_id']);
//                    Log::info($item['product_unit_id']);
                    Log::info("Items before cost".json_encode($item));
                    $item['net_unit_price'] = !empty($unit) ? $this->convertToBase($unit, $item['net_unit_price']) : $item['net_unit_price'];
                    $item['unit_price']     = !empty($unit) ? $this->convertToBase($unit, $item['unit_price']) : $item['unit_price'];
                    $cost                   = $this->calculateCost($item['product_id'], $item['warehouse_id'], $item['net_unit_price'], $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id'], $item_quantity);
                } elseif ($item['product_type'] == 'combo') {
                    $combo_items = ProductSMA::getProductComboItems($item['product_id'], $item['warehouse_id']);
                    foreach ($combo_items as $combo_item) {
                        $pr = ProductSMA::getProductByCode($combo_item->code);
                        if ($pr->tax_rate) {
                            $pr_tax = SaleSMA::getTaxRateByID($pr->tax_rate);
                            if ($pr->tax_method) {
                                $item_tax       = $this->formatDecimal((($combo_item->unit_price) * $pr_tax->rate) / (100 + $pr_tax->rate));
                                $net_unit_price = $combo_item->unit_price - $item_tax;
                                $unit_price     = $combo_item->unit_price;
                            } else {
                                $item_tax       = $this->formatDecimal((($combo_item->unit_price) * $pr_tax->rate) / 100);
                                $net_unit_price = $combo_item->unit_price;
                                $unit_price     = $combo_item->unit_price + $item_tax;
                            }
                        } else {
                            $net_unit_price = $combo_item->unit_price;
                            $unit_price     = $combo_item->unit_price;
                        }
                        if ($pr->type == 'standard') {
                            $cost[] = $this->calculateCost($pr->id, $item['warehouse_id'], $net_unit_price, $unit_price, ($combo_item->qty * $item['quantity']), $pr->name, null, ($combo_item->qty * $item_quantity));
                        } else {
                            $cost[] = [['date' => date('Y-m-d'), 'product_id' => $pr->id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => null, 'quantity' => ($combo_item->qty * $item['quantity']), 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $combo_item->unit_price, 'sale_unit_price' => $combo_item->unit_price, 'quantity_balance' => (0 - $item_quantity), 'inventory' => null]];
                        }
                    }
                } else {
                    $cost = [['date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => null, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => (0 - $item_quantity), 'inventory' => null]];
                }
            } elseif ($item['product_type'] == 'manual') {
                $cost = [['date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => null, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => null, 'inventory' => null]];
            }
        } else {
            if (ProductSMA::getProductByID($item['product_id'])) {
                if ($item['product_type'] == 'standard') {
                    $cost = $this->calculateAVCost($item['product_id'], $item['warehouse_id'], $item['net_unit_price'], $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id'], $item_quantity);
                } elseif ($item['product_type'] == 'combo') {
                    $combo_items = ProductSMA::getProductComboItems($item['product_id'], $item['warehouse_id']);
                    foreach ($combo_items as $combo_item) {
                        $pr = ProductSMA::getProductByCode($combo_item->code);
                        if ($pr->tax_rate) {
                            $pr_tax = SaleSMA::getTaxRateByID($pr->tax_rate);
                            if ($pr->tax_method) {
                                $item_tax       = $this->formatDecimal((($combo_item->unit_price) * $pr_tax->rate) / (100 + $pr_tax->rate));
                                $net_unit_price = $combo_item->unit_price - $item_tax;
                                $unit_price     = $combo_item->unit_price;
                            } else {
                                $item_tax       = $this->formatDecimal((($combo_item->unit_price) * $pr_tax->rate) / 100);
                                $net_unit_price = $combo_item->unit_price;
                                $unit_price     = $combo_item->unit_price + $item_tax;
                            }
                        } else {
                            $net_unit_price = $combo_item->unit_price;
                            $unit_price     = $combo_item->unit_price;
                        }
                        $cost[] = $this->calculateAVCost($combo_item->id, $item['warehouse_id'], $net_unit_price, $unit_price, ($combo_item->qty * $item['quantity']), $item['product_name'], $item['option_id'], ($combo_item->qty * $item_quantity));
                    }
                } else {
                    $cost = [['date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => null, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => null, 'inventory' => null]];
                }
            } elseif ($item['product_type'] == 'manual') {
                $cost = [['date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => null, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => null, 'inventory' => null]];
            }
        }
        return $cost;
    }

    public function calculateCost($product_id, $warehouse_id, $net_unit_price, $unit_price, $quantity, $product_name, $option_id, $item_quantity)
    {
        $pis           = ProductSMA::getPurchasedItems($product_id, $warehouse_id, $option_id);
        $real_item_qty = $quantity;
        $quantity      = $item_quantity;
        $balance_qty   = $quantity;
        foreach ($pis as $pi) {
            $cost_row = null;
            if (!empty($pi) && $balance_qty <= $quantity && $quantity != 0) {
                $purchase_unit_cost = $pi->base_unit_cost ?? ($pi->unit_cost ?? ($pi->net_unit_cost + ($pi->item_tax / $pi->quantity)));
                if ($pi->quantity_balance >= $quantity && $quantity != 0) {
                    $balance_qty = $pi->quantity_balance - $quantity;
                    $cost_row    = ['date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_id' => $pi->purchase_id, 'transfer_id' => $pi->transfer_id, 'purchase_item_id' => $pi->id, 'quantity' => $quantity, 'purchase_net_unit_cost' => $pi->net_unit_cost, 'purchase_unit_cost' => $purchase_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => $balance_qty, 'inventory' => 1, 'option_id' => $option_id];
                    $quantity    = 0;
                } elseif ($quantity != 0) {
                    $quantity    = $quantity - $pi->quantity_balance;
                    $balance_qty = $quantity;
                    $cost_row    = ['date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_id' => $pi->purchase_id, 'transfer_id' => $pi->transfer_id, 'purchase_item_id' => $pi->id, 'quantity' => $pi->quantity_balance, 'purchase_net_unit_cost' => $pi->net_unit_cost, 'purchase_unit_cost' => $purchase_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => 0, 'inventory' => 1, 'option_id' => $option_id];
                }
            }
            $cost[] = $cost_row;
            if ($quantity == 0) {
                break;
            }
        }
        if ($quantity > 0) {
            Log::error(trans('messages.quantity_out_of_stock_for').''.($pi->product_name ?? $product_name).$product_id." option_id: ".$option_id." QTY: ".$quantity);
            return (new ControllerService())->responseDataJson(false, [sprintf(trans('messages.quantity_out_of_stock_for'), ($pi->product_name ?? $product_name))], 401);
        }
        return $cost;
    }

    public function calculateAVCost($product_id, $warehouse_id, $net_unit_price, $unit_price, $quantity, $product_name, $option_id, $item_quantity)
    {
        $product       = ProductSMA::getProductByID($product_id);
        $real_item_qty = $quantity;
        $wp_details    = ProductSMA::getWarehouseProduct($warehouse_id, $product_id);
        $con           = $wp_details ? $wp_details->avg_cost : $product->cost;
        $tax_rate      = SaleSMA::getTaxRateByID($product->tax_rate);
        $ctax          = $this->calculateTax($product, $tax_rate, $con);
        if ($product->tax_method) {
            $avg_net_unit_cost = $con;
            $avg_unit_cost     = ($con + $ctax['amount']);
        } else {
            $avg_unit_cost     = $con;
            $avg_net_unit_cost = ($con - $ctax['amount']);
        }

        if ($pis = ProductSMA::getPurchasedItems($product_id, $warehouse_id, $option_id)) {
            $cost_row    = [];
            $quantity    = $item_quantity;
            $balance_qty = $quantity;
            foreach ($pis as $pi) {
                if (!empty($pi) && $pi->quantity_balance != 0 && $balance_qty <= $quantity && $quantity != 0) {
                    if ($pi->quantity_balance >= $quantity && $quantity != 0) {
                        $balance_qty = $pi->quantity_balance - $quantity;
                        $cost_row    = ['date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_id' => $pi->purchase_id, 'transfer_id' => $pi->transfer_id, 'purchase_item_id' => $pi->id, 'quantity' => $quantity, 'purchase_net_unit_cost' => $avg_net_unit_cost, 'purchase_unit_cost' => $avg_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => $balance_qty, 'inventory' => 1, 'option_id' => $option_id];
                        $quantity    = 0;
                    } elseif ($quantity != 0) {
                        $quantity    = $quantity - $pi->quantity_balance;
                        $balance_qty = $quantity;
                        $cost_row    = ['date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_id' => $pi->purchase_id, 'transfer_id' => $pi->transfer_id, 'purchase_item_id' => $pi->id, 'quantity' => $pi->quantity_balance, 'purchase_net_unit_cost' => $avg_net_unit_cost, 'purchase_unit_cost' => $avg_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => 0, 'inventory' => 1, 'option_id' => $option_id];
                    }
                }
                if (empty($cost_row)) {
                    break;
                }
                $cost[] = $cost_row;
                if ($quantity == 0) {
                    break;
                }
            }
        }
        if ($quantity > 0 && !SettingSMA::getSettingSma()->overselling) {
            return (new ControllerService())->responseDataJson(false, [sprintf(trans('messages.amount_greater_than_deposit'), $product_name)], 401);
        } elseif ($quantity != 0) {
            $cost[] = ['date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => null, 'quantity' => $quantity, 'purchase_net_unit_cost' => $avg_net_unit_cost, 'purchase_unit_cost' => $avg_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => (0 - $quantity), 'overselling' => 1, 'inventory' => 1];
            $cost[] = ['pi_overselling' => 1, 'product_id' => $product_id, 'quantity_balance' => (0 - $quantity), 'warehouse_id' => $warehouse_id, 'option_id' => $option_id];
        }
        return $cost;
    }

    public function syncPurchaseItems($data = [])
    {
        if (!empty($data)) {
            foreach ($data as $items) {
                foreach ($items as $item) {
                    if (isset($item['pi_overselling'])) {
                        unset($item['pi_overselling']);
                        $option_id = (isset($item['option_id']) && !empty($item['option_id'])) ? $item['option_id'] : null;
                        $clause    = ['purchase_id' => null, 'transfer_id' => null, 'product_id' => $item['product_id'], 'warehouse_id' => $item['warehouse_id'], 'option_id' => $option_id];
                        if ($pi = ProductSMA::getPurchasedItem($clause)) {
                            $quantity_balance = $pi->quantity_balance + $item['quantity_balance'];
                            DB::table((new PurchaseItemSMA())->getTable())->where(['id' => $pi->id])->update(['quantity_balance' => $quantity_balance]);
                        } else {
                            $clause['quantity']         = 0;
                            $clause['item_tax']         = 0;
                            $clause['quantity_balance'] = $item['quantity_balance'];
                            $clause['status']           = 'received';
                            $clause['option_id']        = !empty($clause['option_id']) && is_numeric($clause['option_id']) ? $clause['option_id'] : null;

                            DB::table((new PurchaseItemSMA())->getTable())->insert($clause);
                        }
                    } elseif (!isset($item['overselling']) || empty($item['overselling'])) {
                        if ($item['inventory']) {
                            DB::table((new PurchaseItemSMA())->getTable())->where(['id' => $item['purchase_item_id']])->update(['quantity_balance' => $item['quantity_balance']]);
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function updateCostingAndPurchaseItem($return_item, $product_id, $quantity)
    {
        $bln_quantity = $quantity;
        if ($costings = $this->getCostingLines($return_item['id'], $product_id)) {
            foreach ($costings as $costing) {
                if ($costing->quantity > $bln_quantity && $bln_quantity != 0) {
                    $qty = $costing->quantity                                                                                     - $bln_quantity;
                    $bln = $costing->quantity_balance && $costing->quantity_balance >= $bln_quantity ? $costing->quantity_balance - $bln_quantity : 0;
                    DB::table('sma_costing')->where(['id' => $costing->id])->update(['quantity' => $qty, 'quantity_balance' => $bln]);
                    $bln_quantity = 0;
                    break;
                } elseif ($costing->quantity <= $bln_quantity && $bln_quantity != 0) {
                    DB::table('sma_costing')->where(['id' => $costing->id])->delete();
                    $bln_quantity = ($bln_quantity - $costing->quantity);
                }
            }
        }
        $clause = ['product_id' => $product_id, 'warehouse_id' => $return_item['warehouse_id'], 'purchase_id' => null, 'transfer_id' => null, 'option_id' => $return_item['option_id']];
        $this->setPurchaseItem($clause, $quantity);
        $this->syncQuantity(null, null, null, $product_id);
    }

    public function getCostingLines($sale_item_id, $product_id, $sale_id = null)
    {
        $qu = DB::table('sma_costing')->newQuery();

        if ($sale_id) {
            $qu->where('sale_id', $sale_id);
        }
        $orderby = (SettingSMA::getSettingSma()->accounting_method == 1) ? 'asc' : 'desc';

        $qu->orderBy('id', $orderby)
            ->where(['sale_item_id' => $sale_item_id, 'product_id' => $product_id]);

        if ($qu->count() > 0) {
            foreach (($qu->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function setPurchaseItem($clause, $qty)
    {
        if ($product = ProductSMA::getProductByID($clause['product_id'])) {
            if ($pi = ProductSMA::getPurchasedItem($clause)) {
                if ($pi->quantity_balance > 0) {
                    $quantity_balance = $pi->quantity_balance + $qty;
                    Log::error('error: More than zero: ' . $quantity_balance . ' = ' . $pi->quantity_balance . ' + ' . $qty . ' PI: '. json_encode($pi, true));
                } else {
                    $quantity_balance = $pi->quantity_balance + $qty;
                    Log::error('error: Less than zero: ' . $quantity_balance . ' = ' . $pi->quantity_balance . ' + ' . $qty . ' PI: ' . json_encode($pi, true));
                }
                return DB::table((new PurchaseItemSMA())->getTable())->where('id', $pi->id)->update(['quantity_balance' => $quantity_balance]);
            }
            $unit                        = $this->getUnitByID($product->unit);
            $clause['product_unit_id']   = $product->unit;
            $clause['product_unit_code'] = $unit->code;
            $clause['product_code']      = $product->code;
            $clause['product_name']      = $product->name;
            $clause['purchase_id']       = $clause['transfer_id']       = $clause['item_tax']       = null;
            $clause['net_unit_cost']     = $clause['real_unit_cost']     = $clause['unit_cost']     = $product->cost;
            $clause['quantity_balance']  = $clause['quantity']  = $clause['unit_quantity']  = $clause['quantity_received']  = $qty;
            $clause['subtotal']          = ($product->cost * $qty);
            if (isset($product->tax_rate) && $product->tax_rate != 0) {
                $tax_details           = SaleSMA::getTaxRateByID($product->tax_rate);
                $ctax                  = $this->calculateTax($product, $tax_details, $product->cost);
                $item_tax              = $clause['item_tax']              = $ctax['amount'];
                $tax                   = $clause['tax']                   = $ctax['tax'];
                $clause['tax_rate_id'] = $tax_details->id;
                if ($product->tax_method != 1) {
                    $clause['net_unit_cost'] = $product->cost - $item_tax;
                    $clause['unit_cost']     = $product->cost;
                } else {
                    $clause['net_unit_cost'] = $product->cost;
                    $clause['unit_cost']     = $product->cost + $item_tax;
                }
                $pr_item_tax = $this->formatDecimal($item_tax * $clause['unit_quantity'], 4);
                if (SettingSMA::getSettingSma()->indian_gst && $gst_data = $this->calculateIndianGST($pr_item_tax, (SettingSMA::getSettingSma()->state == $supplier_details->state), $tax_details)) {
                    $clause['gst']  = $gst_data['gst'];
                    $clause['cgst'] = $gst_data['cgst'];
                    $clause['sgst'] = $gst_data['sgst'];
                    $clause['igst'] = $gst_data['igst'];
                }
                $clause['subtotal'] = (($clause['net_unit_cost'] * $clause['unit_quantity']) + $pr_item_tax);
            }
            $clause['status']    = 'received';
            $clause['date']      = date('Y-m-d');
            $clause['option_id'] = !empty($clause['option_id']) && is_numeric($clause['option_id']) ? $clause['option_id'] : null;
            Log::error('error: Why else: ' . json_encode($clause, true));
            return PurchaseItemSMA::create($clause);
        }
        return false;
    }

    public function syncQuantity($sale_id = null, $purchase_id = null, $oitems = null, $product_id = null, $job_task_item_id = null)
    {
        if ($sale_id) {
            $sale_items = $this->getAllSaleItems($sale_id);
            Log::info("sale_data =".json_encode($sale_items)."\n");
            foreach ($sale_items as $item) {

                if ($item->product_type == 'standard') {
                    $this->syncProductQty($item->product_id, $item->warehouse_id);
                    if (isset($item->option_id) && !empty($item->option_id)) {
                        $this->syncVariantQty($item->option_id, $item->warehouse_id, $item->product_id);
                    }
                } elseif ($item->product_type == 'combo') {
                    $wh          = SettingSMA::getSettingSma()->overselling ? null : $item->warehouse_id;
                    $combo_items = ProductSMA::getProductComboItems($item->product_id, $wh);
                    foreach ($combo_items as $combo_item) {
                        if ($combo_item->type == 'standard') {
                            $this->syncProductQty($combo_item->id, $item->warehouse_id);
                        }
                    }
                }
            }
        } elseif ($purchase_id) {
            $purchase_items = $this->getAllPurchaseItems($purchase_id);
            foreach ($purchase_items as $item) {
                $this->syncProductQty($item->product_id, $item->warehouse_id);
                $this->checkOverSold($item->product_id, $item->warehouse_id);
                if (isset($item->option_id) && !empty($item->option_id)) {
                    $this->syncVariantQty($item->option_id, $item->warehouse_id, $item->product_id);
                    $this->checkOverSold($item->product_id, $item->warehouse_id, $item->option_id);
                }
            }
        } elseif ($oitems) {
            foreach ($oitems as $item) {
                if (isset($item->product_type)) {
                    if ($item->product_type == 'standard') {
                        $this->syncProductQty($item->product_id, $item->warehouse_id);
                        if (isset($item->option_id) && !empty($item->option_id)) {
                            $this->syncVariantQty($item->option_id, $item->warehouse_id, $item->product_id);
                        }
                    } elseif ($item->product_type == 'combo') {
                        $combo_items = ProductSMA::getProductComboItems($item->product_id, $item->warehouse_id);
                        foreach ($combo_items as $combo_item) {
                            if ($combo_item->type == 'standard') {
                                $this->syncProductQty($combo_item->id, $item->warehouse_id);
                            }
                        }
                    }
                } else {
                    $this->syncProductQty($item->product_id, $item->warehouse_id);
                    if (isset($item->option_id) && !empty($item->option_id)) {
                        $this->syncVariantQty($item->option_id, $item->warehouse_id, $item->product_id);
                    }
                }
            }
        } elseif ($product_id) {
            $warehouses = $this->getAllWarehouses();
            foreach ($warehouses as $warehouse) {
                $this->syncProductQty($product_id, $warehouse->id);
                $this->checkOverSold($product_id, $warehouse->id);
                $quantity           = 0;
                $warehouse_products = $this->getWarehouseProducts($product_id);
                foreach ($warehouse_products as $product) {
                    $quantity += $product->quantity;
                }
                ProductSMA::where(['id' => $product_id])->update(['quantity' => $quantity]);
                if ($product_variants = ProductSMA::getProductVariants($product_id)) {
                    foreach ($product_variants as $pv) {
                        $this->syncVariantQty($pv->id, $warehouse->id, $product_id);
                        $this->checkOverSold($product_id, $warehouse->id, $pv->id);
                        $quantity           = 0;
                        $warehouse_variants = $this->getWarehouseProductsVariants($pv->id);
                        foreach ($warehouse_variants as $variant) {
                            $quantity += $variant->quantity;
                        }
                        ProductVariantSMA::where(['id' => $pv->id])->update(['quantity' => $quantity]);
                    }
                }
            }
        } elseif ($job_task_item_id) {
            $job_task_item = $this->getJobTaskItem($job_task_item_id);

            Log::info(json_encode($job_task_item));
            foreach ($job_task_item as $item){
                if($item->product_type == 'standard') {
                    $this->syncProductQty($item->product_id, $item->warehouse_id);
                    if(isset($item->option_id) && !empty($item->option_id)) {
                        $this->syncVariantQty($item->option_id, $item->warehouse_id, $item->product_id);
                    }
                }elseif ($item->product_type == 'combo') {
                    $wh          = SettingSMA::getSettingSma()->overselling ? null : $item->warehouse_id;
                    $combo_items = ProductSMA::getProductComboItems($item->product_id, $wh);
                    foreach ($combo_items as $combo_item){
                        if($combo_item->type == 'standard') {
                            $this->syncProductQty($combo_item->id, $item->warehouse_id);
                        }
                    }
                }
            }
        }
    }

    public function getJobTaskItem($jo_tsk_id){
        $q = JobOrderTaskItem::where('id', $jo_tsk_id);
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getAllJobTaskItems($jo_tsk_id){
        $q = JobOrderTaskItem::where('jo_task_id', $jo_tsk_id);
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getAllSaleItems($sale_id)
    {
        $q = SaleItemSMA::where('sale_id', $sale_id);
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function syncProductQty($product_id, $warehouse_id)
    {
        $balance_qty    = $this->getBalanceQuantity($product_id);
        $wh_balance_qty = $this->getBalanceQuantity($product_id, $warehouse_id);
        Log::info("bal_qty =".$balance_qty);
        Log::info("wh_bal_qty =".$wh_balance_qty);

        if (ProductSMA::where(['id' => $product_id])->update(['quantity' => $balance_qty])) {
            if ($this->getWarehouseProducts($product_id, $warehouse_id)) {
                DB::table('sma_warehouses_products')->where(['product_id' => $product_id, 'warehouse_id' => $warehouse_id])->update(['quantity' => $wh_balance_qty]);
            } else {
                if (!$wh_balance_qty) {
                    $wh_balance_qty = 0;
                }
                $product = ProductSMA::getProductByID($product_id);
                if ($product) {
                    DB::table('sma_warehouses_products')->insert(['quantity' => $wh_balance_qty, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'avg_cost' => $product->cost]);
                } else {
                    DB::table('sma_warehouses_products')->insert(['quantity' => $wh_balance_qty, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id]);
                }
            }
            return true;
        }
        return false;
    }

    private function getBalanceQuantity($product_id, $warehouse_id = null)
    {
        $query = PurchaseItemSMA::query()
            ->select(DB::raw('SUM(COALESCE(quantity_balance, 0)) as stock'))
            ->where('product_id', $product_id)
            ->where('quantity_balance', '!=', 0);

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }
        $query->where(function ($qu){
            $qu->where('status', 'received')->orWhere('status', 'partial');
        });

        if ($query->count() > 0) {
            $data = $query->first();
            return $data->stock;
        }
        return 0;
    }

    public function getWarehouseProducts($product_id, $warehouse_id = null)
    {
        if ($warehouse_id) {
            $q = DB::table('sma_warehouses_products')->where('warehouse_id', $warehouse_id)->where(['product_id' => $product_id]);
        }else {
            $q = DB::table('sma_warehouses_products')->where(['product_id' => $product_id]);
        }
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            Log::info("wh_prods =".json_encode($data));
            return $data;
        }
        return false;
    }

    public function syncVariantQty($variant_id, $warehouse_id, $product_id = null)
    {
        $balance_qty    = $this->getBalanceVariantQuantity($variant_id);
        $wh_balance_qty = $this->getBalanceVariantQuantity($variant_id, $warehouse_id);
        if (ProductVariantSMA::where(['id' => $variant_id])->update(['quantity' => $balance_qty])) {
            if ($this->getWarehouseProductsVariants($variant_id, $warehouse_id)) {
                DB::table('sma_warehouses_products_variants')->where(['option_id' => $variant_id, 'warehouse_id' => $warehouse_id])->update(['quantity' => $wh_balance_qty]);
            } else {
                if ($wh_balance_qty) {
                    DB::table('sma_warehouses_products_variants')->insert(['quantity' => $wh_balance_qty, 'option_id' => $variant_id, 'warehouse_id' => $warehouse_id, 'product_id' => $product_id]);
                }
            }
            return true;
        }
        return false;
    }

    private function getBalanceVariantQuantity($variant_id, $warehouse_id = null)
    {
        $query = PurchaseItemSMA::query()
            ->select(DB::raw('SUM(COALESCE(quantity_balance, 0)) as stock'))
            ->where('option_id', $variant_id)
            ->where('quantity_balance', '!=', 0);

        if ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        }
        $query->where(function ($qu){
            $qu->where('status', 'received')->orWhere('status', 'partial');
        });

        if ($query->count() > 0) {
            $data = $query->first();
            return $data->stock;
        }
        return 0;
    }

    public function getWarehouseProductsVariants($option_id, $warehouse_id = null)
    {
        if ($warehouse_id) {
            $q = DB::table('sma_warehouses_products_variants')->where('warehouse_id', $warehouse_id)->where(['option_id' => $option_id]);
        }else {
            $q = DB::table('sma_warehouses_products_variants')->where(['option_id' => $option_id]);
        }
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getAllPurchaseItems($purchase_id)
    {
        $q = PurchaseItemSMA::where(['purchase_id' => $purchase_id]);
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function checkOverSold($product_id, $warehouse_id, $option_id = null)
    {
        $clause = ['purchase_id' => null, 'transfer_id' => null, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'option_id' => $option_id];
        if ($os = ProductSMA::getPurchasedItem($clause)) {
            if ($os->quantity_balance < 0) {
                if ($pis = ProductSMA::getPurchasedItems($product_id, $warehouse_id, $option_id, true)) {
                    $quantity = $os->quantity_balance;
                    foreach ($pis as $pi) {
                        if ($pi->quantity_balance >= (0 - $quantity) && $quantity != 0) {
                            $balance = $pi->quantity_balance + $quantity;
                            PurchaseItemSMA::where(['id' => $pi->id])->update(['quantity_balance' => $balance]);
                            $quantity = 0;
                            break;
                        } elseif ($quantity != 0) {
                            $quantity = $quantity + $pi->quantity_balance;
                            PurchaseItemSMA::where(['id' => $pi->id])->update(['quantity_balance' => 0]);
//                            $this->db->update('purchase_items', ['quantity_balance' => 0], ['id' => $pi->id]);
                        }
                    }
                    PurchaseItemSMA::where(['id' => $os->id])->update(['quantity_balance' => $quantity]);
//                    $this->db->update('purchase_items', ['quantity_balance' => $quantity], ['id' => $os->id]);
                }
            }
        }
    }

    public function getAllWarehouses()
    {
        $q = WarehouseSMA::query();
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getUser($id = null)
    {
        if (!$id) {
            $id = auth()->user()->id;
        }
        $q = User::where(['id' => $id]);
        if ($q->count() > 0) {
            return $q->first();
        }
        return false;
    }

    public function in_group($check_group, $id = false)
    {
//        $this->auth_model->trigger_events('in_group');

        $id || $id = 1;

        $user = User::find($id);

//        Log::info('Users:', [$user]);
//        Log::info('Role Group:', [$user->roles]);
//
//        Log::info('Users Roles:', $user->roles->toArray());
//        Log::info('Group Name:', ['group' => $check_group]);
        if (!$user || $user->roles->isEmpty()) {
            return false;
        }



        $groupName = $user->roles->pluck('name')->first();
//        Log::info('Group Name:', ['group' => $groupName]);

        if ($groupName === $check_group) {
            return true;
        }

        return false;
    }

    public function convertToBase($unit, $value)
    {
        switch ($unit->operator) {
            case '*':
                if ($unit->operation_value == 0) {
                    return $value / 1;
                }
                return $value / $unit->operation_value;

            case '/':
                return $value * $unit->operation_value;

            case '+':
                return $value - $unit->operation_value;

            case '-':
                return $value + $unit->operation_value;

            default:
                return $value;
        }
    }

    public function getDateFormat($id)
    {

        $q = DB::table('sma_date_format')->where('id', $id)->get();

        if ($q->count() > 0) {
            return $q[0];
        }
        return false;
    }

    public function syncPurchasePayments($id)
    {
        $purchase = PurchaseSMA::getPurchaseByID($id);
        $paid     = 0;
        if ($payments = PurchaseSMA::getPurchasePayments($id)) {
            foreach ($payments as $payment) {
                $paid += $payment->amount;
            }
        }

        $payment_status = $paid <= 0 ? 'pending' : $purchase->payment_status;
        if (self::formatDecimal($purchase->grand_total) > self::formatDecimal($paid) && $paid > 0) {
            $payment_status = 'partial';
        } elseif (self::formatDecimal($purchase->grand_total) <= self::formatDecimal($paid)) {
            $payment_status = 'paid';
        }

        if (PurchaseSMA::where(['id' => $id])->update(['paid' => $paid, 'payment_status' => $payment_status])) {
            return true;
        }

        return false;
    }


    public function notifySysAdmin($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['system_admin_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['system_admin_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifySysAdminError:".$e->getMessage());
        }
    }

    public function notifyServiceMana($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['service_manager_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['service_manager_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifyServiceManaError:".$e->getMessage());
        }
    }

    public function notifyWarrantyMana($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['warranty_manager_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['warranty_manager_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifyWarrantyManaError:".$e->getMessage());
        }
    }

    public function notifyServiceWriter($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['service_writer_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['service_writer_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifyServiceWriterError:".$e->getMessage());
        }
    }

    public function notifyCashier($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['cashier_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['cashier_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifyCashierError:".$e->getMessage());
        }
    }

    public function notifyPartsMana($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['parts_manager_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['parts_manager_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifyPartsManagerError:".$e->getMessage());
        }
    }

    public function notifyAllTechnician($notify_title = null, $notify_body = null){
        $respSM = User::role((new ControllerService())->getDefaultValues()['technician_role_id'])->get();//Time being role id is this

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            foreach ($respSM AS $sm_user){
                if($sm_user->is_active) {
                    $ins_array = [
                        "sent_from_id" => $sent_from_id,
                        "sent_from_role_id" => $sent_from_role_id,
                        "sent_to_id" => $sm_user->id,
                        "sent_to_role_id" => (new ControllerService())->getDefaultValues()['technician_role_id'],
                        "notify_title" => $notify_title,
                        "notify_body" => $notify_body
                    ];
                    Notification::create($ins_array);
                }
            }
        }catch (\Exception $e){
            Log::error("notifyAllTechnicianError:".$e->getMessage());
        }
    }

    public function notifyTechnician($sent_to_id, $notify_title = null, $notify_body = null){

        $sent_from_id = auth()->user()->id;
        $sent_from_role_id = auth()->user()->roles()->get()[0]->id;

        try {
            $ins_array = [
                "sent_from_id" => $sent_from_id,
                "sent_from_role_id" => $sent_from_role_id,
                "sent_to_id" => $sent_to_id, //user_id
                "sent_to_role_id" => (new ControllerService())->getDefaultValues()['technician_role_id'],
                "notify_title" => $notify_title,
                "notify_body" => $notify_body
            ];
            Notification::create($ins_array);
        }catch (\Exception $e){
            Log::error("notifyTechnicianError:".$e->getMessage());
        }
    }

    public function test()
    {
        if ($class == PaymentMethodEnum::class && $value == TELR_PAYMENT_METHOD_NAME) {
            $value = Html::tag(
                'span',
                PaymentMethodEnum::getLabel($value),
                ['class' => 'label-success status-label']
            )
                ->toHtml();
        }

        return $value;
    }
}
