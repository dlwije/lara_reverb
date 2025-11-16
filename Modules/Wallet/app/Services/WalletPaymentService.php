<?php

namespace Modules\Wallet\Services;

use App\Services\ControllerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Module\Wallet\Services\Abstracts\WalletPaymentAbstract;

class WalletPaymentService extends WalletPaymentAbstract
{

    function __construct(public ControllerService $controllerService)
    {
        parent::__construct();
    }
    public function makePayment(array $data): string|false
    {
        Log::info('MakePayment - WalletPaymentService.php');

        $request = request();
        $isMobile = (bool) $request->is_mobile;
        $domain = str_ireplace('www.', '', parse_url($this->controllerService->get_frontend_url(), PHP_URL_HOST));
        $this->amount = round((float)$data['amount'], $this->isSupportedDecimals() ? 2 : 0);
        $this->currency = strtoupper($data['currency']);
        $this->cart_id = uniqid();
        $this->description = trans('plugins/payment::payment.payment_description', [
            'order_id' => implode(', #', $data['order_id']),
            'site_url' => $domain,
        ]);
        $this->checkout_token = $data['checkout_token'];
        $address = $data['address'];
        $name = (isset($address['name']) ? explode(' ', trim($address['name'])) : '');
        $firstName = $name[0];
        array_shift($name);
        $lastName = (count($name) > 0) ? implode(' ', $name) : '';
        $billing_address = $data['billing_address'];
        $b_name = (isset($billing_address['name']) ? explode(' ', trim($billing_address['name'])) : '');
        $b_firstName = $b_name[0];
        array_shift($b_name);
        $b_lastName = (count($b_name) > 0) ? implode(' ', $b_name) : '';
        $paymentType = '';

        $params = [];
        $params['payment_type'] = $paymentType;
        $params['general'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $address['email'],
            'phone' => $address['phone'],
            //'country' => $address['country'],
            'country' =>'AE',
            'order_amount' => round((float)$data['amount_without_any_rule'], $this->isSupportedDecimals() ? 2 : 0),
            'final_order_amount' => $this->amount,
            'tax_amount' => round((float)$data['tax_amount'], $this->isSupportedDecimals() ? 2 : 0),
            'discount_amount' => round((float)$data['discount_amount'], $this->isSupportedDecimals() ? 2 : 0),
            'shipping_amount' => round((float)$data['shipping_amount'], $this->isSupportedDecimals() ? 2 : 0),
            'discount_code' => $data['discount_code']
        ];

        $params['products'] = $data['products'];
        $params['shipping'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address_1' => ($address['address']?: ''),
            'address_2' => '',
            'city' => ($address['city']?: ''),
            'region' => ($address['state']?: ''),
            'zip' => ($address['zip_code']?: ''),
            // 'country' => ($address['country']?: ''),
            'country' => 'AE',
            'email' => ($address['email']?: ''),
            'phone' => ($address['phone']?: ''),
        ];

        $params['billing'] = [
            'first_name' => $b_firstName,
            'last_name' => $b_lastName,
            'address_1' => ($billing_address['address']?: ''),
            'address_2' => '',
            'city' => ($billing_address['city']?: ''),
            'region' => ($billing_address['state']?: ''),
            'zip' => ($billing_address['zip_code']?: ''),
            //'country' => ($billing_address['country']?: ''),
            'country' => 'AE',
            'email' => ($billing_address['email']?: ''),
            'phone' => ($billing_address['phone']?: ''),
        ];
        $queryParams = [
            'type' => WALLET_PAYMENT_METHOD_NAME,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'rorder_id' => $data['order_id'],
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'isApp' => ($isMobile ? '1' : '0'),
        ];


        $userId = Arr::get($data, 'customer_id');

        // 1. Check if customer exists
        $customer = Customer::find($userId);
        if (! $customer) {
            $this->setErrorMessage(__('Customer not found.'));
            return false;
        }

        $amount = round((float) $data['amount'], 2);

        // 6. Generate unique charge ID
        $chargeId = Str::upper(Str::random(10));

        // 7. Trigger Botble payment processed action
        $orderIds = (array) $data['order_id'];

        //Do Payment Here


        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount'          => $amount,
            'currency'        => $data['currency'],
            'charge_id'       => $chargeId,
            'order_id'        => $orderIds,
            'customer_id'     => $customer->id,
            'customer_type'   => Customer::class,
            'payment_channel' => 'wallet',// use Wallet channel
            'status'          => PaymentStatusEnum::COMPLETED,
        ]);


        $queryParams = [
            'type' => WALLET_PAYMENT_METHOD_NAME,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'rorder_id' => $data['order_id'],
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'isApp' => ($isMobile ? '1' : '0'),
        ];

        // 8. Return the charge ID only (like execute method)
        //$errorQueryParam = ['isApp' => ($isMobile ? '1' : '0')];
        if($isMobile) { $errorQueryParam['tracked_start_checkout'] = $this->checkout_token; }
        // return $this->setCancelUrl(route('wallet.error') . '?' . http_build_query($errorQueryParam))->setReturnUrl(route('wallet.success') . '?' . http_build_query($queryParams))->createPayment($params);
        return $chargeId;
    }

    /*public function makePayment(array $data)
    {
        LOG::info('MakePayment - WalletPaymentService.php');

        $userId = Arr::get($data, 'customer_id');

        // 1. Check if customer exists
        $customer = Customer::find($userId);
        if (! $customer) {
            $this->setErrorMessage(__('Customer not found.'));
            return false;
        }

        $amount = round((float) $data['amount'], 2);

        // 2. Find wallet
        $wallet = Wallet::where('user_id', $customer->id)->first();
        if (! $wallet) {
            $this->setErrorMessage(__('Wallet not found.'));
            return false;
        }

        // 3. Check if wallet is frozen
        if (strtolower(trim($wallet->wt_status)) !== 'active') {
            $this->setErrorMessage(__('Your wallet is frozen.'));
            return false;
        }

        // 4. Check balance
        if ($wallet->total_available < $amount) {
            $this->setErrorMessage(__('Insufficient wallet balance.'));
            return false;
        }

        // 5. Deduct balance
        $wallet->total_available -= $amount;
        $wallet->save();

        // 6. Generate unique transaction/charge ID
        $chargeId = uniqid('wallet_', true);

        // 7. Return array for Botble checkout
        return [
            'status'        => 'success',
            'message'       => __('Wallet payment successful.'),
            'transaction_id'=> $chargeId,
            'charge_id'     => $chargeId,
            'amount'        => $amount,
            'currency'      => $data['currency'],
            'order_id'      => $data['order_id'],
            'customer_id'   => $customer->id,
            'customer_type' => Customer::class,
        ];
    }*/

    public function afterMakePayment(array $data): string|null
    {
        LOG::info(['data after make payment' => $data]);
        LOG::info('After Make Payment');

        $status = PaymentStatusEnum::COMPLETED;

        $chargeId = Arr::get($data, 'charge_id'); // Use charge_id instead of orderId

        $orderIds = (array) Arr::get($data, 'order_id', []);

        // Fire Botble payment processed action
        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount'          => Arr::get($data, 'amount'),
            'currency'        => Arr::get($data, 'currency'),
            'charge_id'       => $chargeId,
            'order_id'        => $orderIds,
            'customer_id'     => Arr::get($data, 'customer_id'),
            'customer_type'   => Arr::get($data, 'customer_type'),
            'payment_channel' => WALLET_PAYMENT_METHOD_NAME,
            'status'          => $status,
        ]);

        session()->forget('wallet_payment_id');

        return $chargeId;
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            'AED','AUD','BRL','CAD','CNY','CZK','DKK','EUR','HKD','HUF','ILS',
            'JPY','MYR','MXN','TWD','NZD','NOK','PHP','PLN','GBP','RUB','SGD',
            'SEK','CHF','THB','USD',
        ];
    }
}
