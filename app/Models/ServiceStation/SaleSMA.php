<?php

namespace App\Models\ServiceStation;

use App\Http\Controllers\Controller;
use App\Models\SettingSMA;
use App\Services\ControllerService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaleSMA extends Model
{
    use HasFactory;
    protected $table = "sma_sales";
    protected $fillable = [
        'id',
        'date',
        'reference_no',
        'customer_id',
        'customer',
        'biller_id',
        'biller',
        'warehouse_id',
        'note',
        'total_serv',
        'staff_note',
        'total',
        'total_serv_discount',
        'product_discount',
        'order_discount_id',
        'total_discount',
        'order_discount',
        'product_tax',
        'order_tax_id',
        'order_tax',
        'total_tax',
        'shipping',
        'grand_total',
        'sale_status',
        'payment_status',
        'payment_term',
        'due_date',
        'created_by',
        'updated_by',
        'updated_at',
        'total_items',
        'pos',
        'workorder',
        'paid',
        'return_id',
        'surcharge',
        'attachment',
        'return_sale_ref',
        'sale_id',
        'jo_id',
        'return_sale_total',
        'rounding',
        'suspend_note',
        'api',
        'shop',
        'address_id',
        'reserve_id',
        'hash',
        'manual_payment',
        'cgst',
        'sgst',
        'igst',
        'payment_method',
    ];

    public static function getTaxRateByID($id){
        $Q2 = DB::table('taxes')->where('id', $id)->get();
        if($Q2->count() > 0){
            return $Q2[0];
        }
        return false;
    }

    public static function syncSalePayments($id)
    {
        $sale = (new SaleSMA)::getSaleByID($id);
        if ($payments = (new SaleSMA)->getSalePayments($id)) {
            $paid        = 0;
            $grand_total = $sale->grand_total + $sale->rounding;
            foreach ($payments as $payment) {
                $paid += $payment->amount;
            }

            $payment_status = $paid == 0 ? 'pending' : $sale->payment_status;
            if ((new ControllerService())->formatDecimal($grand_total) == 0 || (new ControllerService())->formatDecimal($grand_total) <= (new ControllerService())->formatDecimal($paid)) {
                $payment_status = 'paid';
            } elseif ($sale->due_date <= date('Y-m-d') && !$sale->sale_id) {
                $payment_status = 'due';
            } elseif ($paid != 0) {
                $payment_status = 'partial';
            }

            if (SaleSMA::where(['id' => $id])->update(['paid' => $paid, 'payment_status' => $payment_status])) {
                return true;
            }
        } else {
            $payment_status = ($sale->due_date <= date('Y-m-d')) ? 'due' : 'pending';
            if (SaleSMA::where(['id' => $id])->update(['paid' => 0, 'payment_status' => $payment_status])) {
                return true;
            }
        }

        return false;
    }

    public static function getSaleByID($id)
    {
        $q = self::where(['id' => $id]);
        if ($q->count() > 0) {
            return $q->first();
        }
        return false;
    }

    public function getSalePayments($sale_id)
    {
        $q = PaymentSMA::where(['sale_id' => $sale_id]);
        if ($q->count() > 0) {
            foreach (($q->get()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public static function update_award_points($total, $customer, $user = null, $scope = null)
    {
        if (!empty(SettingSMA::getSettingSma()->each_spent)) {
            $company = CustomerSMA::getCompanyByID($customer);
            if ($total > 0 || $scope) {
                $points = floor(($total / SettingSMA::getSettingSma()->each_spent) * SettingSMA::getSettingSma()->ca_point);
            } else {
                $points = ceil(($total / SettingSMA::getSettingSma()->each_spent) * SettingSMA::getSettingSma()->ca_point);
            }
            $total_points = $scope ? $company->award_points - $points : $company->award_points + $points;
            CustomerSMA::where(['id' => $customer])->update(['award_points' => $total_points]);
        }
        if ($user && !empty(SettingSMA::getSettingSma()->each_sale) && !((new ControllerService())->in_group('customer')? true : null) && !((new ControllerService())->in_group('supplier')? true : null)) {
            $staff = (new ControllerService())->getUser($user);
            if ($total > 0 || $scope) {
                $points = floor(($total / SettingSMA::getSettingSma()->each_sale) * SettingSMA::getSettingSma()->sa_point);
            } else {
                $points = ceil(($total / SettingSMA::getSettingSma()->each_sale) * SettingSMA::getSettingSma()->sa_point);
            }
            $total_points = $scope ? $staff->award_points - $points : $staff->award_points + $points;
            User::where(['id' => empty($user)? 1 : $user])->update(['award_points' => $total_points]);
        }
        return true;
    }

    public static function addPayment($data = [], $customer_id = null){

        if(PaymentSMA::create($data)){
            if(OrderRefSMA::getReference('pay') == $data['reference_no']) {
                OrderRefSMA::updateReference('pay');
            }
            self::syncSalePayments($data['sale_id']);
            if ($data['paid_by'] == 'gift_card') {
                $gc = self::getGiftCardByNO($data['cc_no']);
                DB::table('sma_gift_cards')->where(['card_no' => $data['cc_no']])->update(['balance' => ($gc->balance - $data['amount'])]);
            } elseif ($customer_id && $data['paid_by'] == 'deposit') {
                $customer = CustomerSMA::getCompanyByID($customer_id);
                CustomerSMA::where(['id' => $customer->id])->update(['deposit_amount' => ($customer->deposit_amount - $data['amount'])]);
            }
            return true;
        }
        return false;
    }

    public static function getGiftCardByNO($no)
    {
        $Q2 = DB::table('sma_gift_cards')->where('id', $no)->get();
        if($Q2->count() > 0){
            return $Q2[0];
        }
        return false;
    }

    public function customer()
    {
        return $this->belongsTo(CustomerSMA::class, 'customer_id');
    }
}
