<?php

namespace Modules\Wallet\Services;

use App\Services\ControllerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Telr\Services\Abstracts\TelrPaymentAbstract;
use Modules\Wallet\Services\Abstracts\WalletCheckoutPaymentAbstract;

class WalletCheckoutPaymentService extends TelrPaymentAbstract
{
    function __construct(public SplitPaymentPGatewayFirstService $splitPaymentPGatewayFirstService, public ControllerService $controllerService)
    {
        parent::__construct();
    }

    public function makePayment(array $data)
    {
        Log::info('MakePayment - WalletCheckoutPaymentService.php');
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
        $this->paymethod = (!empty($request->telr_payment_type) ? trim($request->telr_payment_type) : '');

        $address = $data['address'];
        $name = (isset($address['name']) ? explode(' ', trim($address['name'])) : '');
        $firstName = $name[0];
        array_shift($name);
        $lastName = (count($name) > 0) ? implode(' ', $name) : '';

        $params = [];
        $params['billing'] = [
            'first_name' => $firstName,
            'sur_name' => $lastName,
            'address_1' => ($address['address']?: ''),
            'address_2' => '',
            'city' => ($address['city']?: ''),
            'region' => ($address['state']?: ''),
            'zip' => ($address['zip_code']?: ''),
            // 'country' => ($address['country']?: ''),
            'country' => 'AE',
            'email' => ($address['email']?: ''),
            'phone'=>($address['phone']?: ''),
        ];

        $queryParams = [
            'type' => TELR_PAYMENT_METHOD_NAME,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'order_id' => $data['order_id'] ?? null,
            'trans_freeze_id' => $data['trans_freeze_id'] ?? null,
            'wallet_applied_amount' => $data['wallet_applied_amount'] ?? null,
            'wallet_pay_id' => $data['payment_id'] ?? null,
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'isApp' => ($isMobile ? '1' : '0'),
        ];

        Log::info('WalletCheckoutPayment $queryParams: ' , $queryParams);
        Log::info('WalletCheckoutPayment $thisData: ' , [
            'this->amount'=> $this->amount,
            'this->currency'=> $this->currency,
            'this->cart_id'=> $this->cart_id,
            'this->description'=> $this->description,
            'this->checkout_token'=> $this->checkout_token,
            'this->paymethod'=> $this->paymethod,
        ]);

        $errorQueryParam = ['isApp' => ($isMobile ? '1' : '0')];
        if($isMobile) { $errorQueryParam['tracked_start_checkout'] = $this->checkout_token; }
        return $this->setCancelUrl(route('payments.telr.error') . '?' . http_build_query($errorQueryParam))->setReturnUrl(route('payments.telr.success') . '?' . http_build_query($queryParams))->createPayment($params);
    }

    public function afterMakePayment(array $data): string|null
    {
        $request = request();
        $status = PaymentStatusEnum::COMPLETED;

        Log::info('AfterMAkePayment on WalletCheckoutPaymentService: $data', $data);

        //$chargeId = session('telr_payment_id');
        $chargeId = trim($request->get('OrderRef'));

        $orderIds = (array)Arr::get($data, 'order_id', []);

        app(WalletCheckoutService::class)->paymentActionPaymentProcessed([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $chargeId,
            'order_id' => $orderIds,
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => TELR_PAYMENT_METHOD_NAME,
            'status' => $status,
        ]);

        session()->forget('telr_payment_id');

        return $chargeId;
    }

    public function makeSubscriptionPayment(array $data)
    {
        $request = request();
        $domain = str_ireplace('www.', '', parse_url(get_frontend_url(), PHP_URL_HOST));
        $this->amount = round((float)$data['amount'], $this->isSupportedDecimals() ? 2 : 0);
        $this->currency = strtoupper($data['currency']);
        $this->cart_id = uniqid();
        $this->description = trans('plugins/telr::telr.subscription_description', [
            'subscriber_id' => $data['subscriber_id'],
            'site_url' => $domain,
        ]);
        $this->paymethod = (!empty($request->telr_payment_type) ? trim($request->telr_payment_type) : '');

        $params = [];
        if($data['payment_term'] > 0) {
            $params['repeat'] = [
                'auto' => true,
                'amount' => $this->amount,
                'interval' => $data['payment_term'],
                'period' => 'M',
                'term' => 0,
                'final' => 0,
                'start' => 'next'
            ];
        } else {
            $params['subscription_payment'] = true;
        }


        $queryParams = [
            'type' => TELR_PAYMENT_METHOD_NAME,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'subscriber_id' => $data['subscriber_id'],
            'payment_term' => $data['payment_term'],
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
        ];

        return $this->setCancelUrl(route('payments.telr.sub.error'))->setReturnUrl(route('payments.telr.sub.success') . '?' . http_build_query($queryParams))->createPayment($params);
    }

    public function afterMakeSubscriptionPayment(array $data): string|null
    {
        $request = request();
        $status = PaymentStatusEnum::COMPLETED;

        //$chargeId = session('telr_subscription_id');
        $chargeId = trim($request->get('OrderRef'));

        $subId = Arr::get($data, 'subscriber_id');

        do_action(PAYMENT_ACTION_SUBSCRIPTION_PAYMENT_PROCESSED, [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $chargeId,
            'subscriber_id' => $subId,
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => TELR_PAYMENT_METHOD_NAME,
            'status' => $status,
        ]);

        session()->forget('telr_subscription_id');

        return $chargeId;
    }

    public function makeGiftCardPayment(array $data)
    {
        $request = request();
        $domain = str_ireplace('www.', '', parse_url(get_frontend_url(), PHP_URL_HOST));
        $this->amount = round((float)$data['amount'], $this->isSupportedDecimals() ? 2 : 0);
        $this->currency = strtoupper($data['currency']);
        $this->cart_id = uniqid();
        $this->description = trans('plugins/telr::telr.gift_card_description', [
            'giftcard_id' => $data['giftcard_id'],
            'site_url' => $domain,
        ]);

        $params = ['giftcard_id' => $data['giftcard_id']];
        $this->paymethod = (!empty($request->telr_payment_type) ? trim($request->telr_payment_type) : '');

        $queryParams = [
            'type' => TELR_PAYMENT_METHOD_NAME,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'giftcard_id' => $data['giftcard_id'],
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
        ];

        return $this->setCancelUrl(route('payments.telr.giftcard.error').'?giftcard_id='.$data['giftcard_id'])->setReturnUrl(route('payments.telr.giftcard.success') . '?' . http_build_query($queryParams))->createPayment($params);
    }

    public function afterMakeGiftCardPayment(array $data): string|null
    {
        $request = request();
        $status = PaymentStatusEnum::COMPLETED;

        //$chargeId = session('telr_giftcard_id');
        $chargeId = trim($request->get('OrderRef'));

        $subId = Arr::get($data, 'giftcard_id');

        do_action(PAYMENT_ACTION_GIFT_CARD_PAYMENT_PROCESSED, [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $chargeId,
            'giftcard_id' => $subId,
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => TELR_PAYMENT_METHOD_NAME,
            'status' => $status,
        ]);

        session()->forget('telr_giftcard_id');

        return $chargeId;
    }

    //Make Wallet Top Up Payment
    public function makeWalletTopUpPayment(array $data)
    {
        $request = request();
        $domain = str_ireplace('www.', '', parse_url(get_frontend_url(), PHP_URL_HOST));
        $this->amount = round((float)$data['amount'], $this->isSupportedDecimals() ? 2 : 0);
        $this->currency = strtoupper($data['currency']);
        $this->cart_id = uniqid();
        $this->description = trans('plugins/telr::telr.wallet_top_up_description', [
            'wallettracsaction_id' => $data['wallettracsaction_id'],
            'site_url' => $domain,
        ]);

        $params = ['wallettracsaction_id' => $data['wallettracsaction_id'], 'wallet_lot_id' => $data['wallet_lot_id'], 'payment_id' => $data['payment_id']];
        $this->paymethod = (!empty($request->telr_payment_type) ? trim($request->telr_payment_type) : '');
        $queryParams = [
            'type' => TELR_PAYMENT_METHOD_NAME,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'wallettracsaction_id' => $data['wallettracsaction_id'],
            'wallet_lot_id' => $data['wallet_lot_id'],
            'payment_id' => $data['payment_id'],
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
        ];

        return $this->setCancelUrl(route('payments.telr.wallettopup.error').'?wallettracsaction_id='.$data['wallettracsaction_id'])->setReturnUrl(route('payments.telr.wallettopup.success') . '?' . http_build_query($queryParams))->createPayment($params);
    }
    public function afterWalletTopUpPayment(array $data): string|null
    {
        Log::info('After Payment Successfull WalletCheckoutPaymentService');
        $request = request();
        $status = PaymentStatusEnum::COMPLETED;

        //$chargeId = session('telr_wallettracsaction_id');
        $chargeId = trim($request->get('OrderRef'));

        $subId = Arr::get($data, 'wallettracsaction_id');
        $walletLotId = Arr::get($data, 'wallet_lot_id');
        $paymentId = Arr::get($data, 'payment_id');

        do_action(PAYMENT_ACTION_WALLET_TOP_UP_PAYMENT_PROCESSED, [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $chargeId,
            'wallettracsaction_id' => $subId,
            'wallet_lot_id' => $walletLotId,
            'payment_id' => $paymentId,
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => TELR_PAYMENT_METHOD_NAME,
            'status' => $status,
        ]);

        session()->forget('telr_wallettracsaction_id');

        return $chargeId;
    }
}
