<?php

namespace Modules\Wallet\Services\Abstracts;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Traits\PaymentErrorTrait;

abstract class WalletCheckoutPaymentAbstract
{
    use PaymentErrorTrait;

    protected float $amount;

    protected string $currency;

    protected string $chargeId;

    protected string $paymethod = '';

    protected $trantype;

    protected $tranclass;

    protected $storeId;

    protected $apiKey;

    protected $telrMode;

    protected $description;

    protected $cart_id;

    protected $checkout_token;

    protected string $returnUrl;

    protected string $cancelUrl;

    protected bool $supportRefundOnline = false;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->totalAmount = 0;

        $this->setClient();

        $this->supportRefundOnline = false;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    /**
     * Returns PayPal HTTP client instance with environment which has access
     * credentials context. This can be used invoke PayPal API's provided the
     * credentials have the access to do so.
     */
    public function setClient(): self
    {
        $this->client = new Client();

        $this->environment();

        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Setting up and Returns Telr SDK environment with Telr Access credentials.
     * For demo purpose, we are using SandboxEnvironment. In production this will be
     * ProductionEnvironment.
     */
    public function environment()
    {
        $this->storeId = env('payment_telr_store_id', '<<TELR-STORE-ID>>');
        $this->apiKey = env('payment_telr_api_key', '<<TELR-API-KEY>>');
        $telrMode = env('payment_telr_mode', false);
        $this->telrMode = ($telrMode ? 0 : 1);
        $this->trantype = 'sale';
        $this->tranclass = 'cont';
    }

    public function setCurrency(string $currency): self
    {
        $this->paymentCurrency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->paymentCurrency;
    }

    public function setReturnUrl(string $url): self
    {
        $this->returnUrl = $url;

        return $this;
    }

    public function setCancelUrl(string $url): self
    {
        $this->cancelUrl = $url;

        return $this;
    }

    public function createPayment(array $data) {
        $paramArray = [
            'method' => 'create',
            'paymethod' => $this->paymethod,
            'store' => $this->storeId,
            'authkey' => $this->apiKey,
            'framed' => 0,
            'order' => [
                'cartid' => $this->cart_id,
                'test' => $this->telrMode,
                'amount' => number_format($this->amount, 2, '.', ''),
                'currency' => $this->currency,
                'description' => $this->description
            ],
            'return' => [
                'authorised' => $this->returnUrl,
                'declined' => $this->cancelUrl,
                'cancelled' => $this->cancelUrl
            ]
        ];

        //Fetch Customer and add there detail
        $customer = auth()->user();
        if($customer)
        {
            $phone=null;
            $countryIso=null;
            $countryIso = $customer->addresses()
                ->where('is_default', 1)
                ->value('country_iso');

            if($customer->is_vendor)
            {
                $phone = $customer->getMeta('phone_number');
                //get Country
                $phone = $customer->getMeta('phone_number');
                $country_id=$customer->getMeta('company_country');
                $countryIso = $countryIso ?: Country::find($country_id)?->code ?: null;
            }
            $finalPhone = $customer->phone ?: $phone ?: null;

            $paramArray['customer']['name']['forenames'] = $customer->name;
            $paramArray['customer']['email'] = $customer->email;
            $paramArray['customer']['phone'] = $finalPhone;
            $paramArray['customer']['address']['country'] = ($countryIso) ? $countryIso :'';
        }


        if(isset($data['billing'])) {
            $tarr = $data['billing'];
            $paramArray['customer'] = [];
            if(isset($tarr['email'])) { $paramArray['customer']['email'] = $tarr['email']; }
            if(isset($tarr['first_name'])) {
                $paramArray['customer']['name']['forenames'] = $tarr['first_name'];
                $paramArray['customer']['name']['surname'] = $tarr['sur_name'];
                $paramArray['customer']['phone'] = ($tarr['phone']) ? $tarr['phone'] : '';
            }

            if(isset($tarr['address_1']) || isset($tarr['city']) || isset($tarr['region']) || isset($tarr['zip']) || isset($tarr['country'])) {
                $tempAddress = [];
                if(isset($tarr['address_1'])) { $tempAddress['line1'] = $tarr['address_1']; }
                if(isset($tarr['city'])) { $tempAddress['city'] = $tarr['city']; }
                if(isset($tarr['region'])) { $tempAddress['state'] = $tarr['region']; }
                if(isset($tarr['zip'])) { $tempAddress['areacode'] = $tarr['zip']; }
                if(isset($tarr['country'])) { $tempAddress['country'] = $tarr['country']; }

                $paramArray['customer']['address'] = $tempAddress;
            }
        }

        $sessionName = 'telr_payment_id';
        if(isset($data['repeat'])) {
            $sessionName = 'telr_subscription_id';
            $paramArray['repeat'] = $data['repeat'];
        }

        if(isset($data['subscription_payment'])) {
            $sessionName = 'telr_subscription_id';
            unset($data['subscription_payment']);
        }

        if(isset($data['giftcard_id'])) {
            $sessionName = 'telr_giftcard_id';
        }
        if(isset($data['wallettracsaction_id'])) {
            $sessionName = 'telr_wallettracsaction_id';
        }

        Log::info('WalletCheckoutPaymentAbstract createPayment params: ', $paramArray);
        $response = $this->client->request('POST', config('plugins.telr.telr.sale.endpoint'), [
            'body' => json_encode($paramArray),
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ],
        ]);

        $bodyStr = json_decode($response->getBody());
        Log::info('WalletCheckoutPaymentAbstract createPayment $bodyStr:', (array) $bodyStr);
        if(isset($bodyStr->order)) {
            session([$sessionName => $bodyStr->order->ref]);
            return ['order_ref' => $bodyStr->order->ref, 'url' => $bodyStr->order->url];
        } else {
            session()->forget($sessionName);
            $message = (isset($bodyStr->error) ? isset($bodyStr->error->message) ? $bodyStr->error->message : (isset($bodyStr->error->note) ? $bodyStr->error->note : 'Unexpected') : 'Unexpected');
            $this->setErrorMessage(trans($message));
            return null;
        }
    }

    public function execute(array $data)
    {
        try {
            return $this->makePayment($data);
        } catch (Exception $exception) {
            Log::error('WalletCheckoutPaymentAbstract execute error: ', $exception);
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    public function executeSubscription(array $data)
    {
        try {
            return $this->makeSubscriptionPayment($data);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    public function getPaymentDetails(string $paymentId): bool|array|object
    {
        try {
            $paramArray = [
                'method' => 'check',
                'store' => $this->storeId,
                'authkey' => $this->apiKey,
                'order' => [
                    'ref' => $paymentId
                ]
            ];
            $response = $this->client->request('POST', config('plugins.telr.telr.sale.endpoint'), [
                'body' => json_encode($paramArray),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                ],
            ]);
            $bodyStr = json_decode($response->getBody());
            $response = (object) $bodyStr;
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return $response;
    }

    public function isSupportedDecimals(): bool
    {
        return ! in_array($this->getCurrency(), [
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'JPY',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ]);
    }

    /**
     * List currencies supported https://developer.paypal.com/docs/api/reference/currency-codes/
     */
    public function supportedCurrencyCodes(): array
    {
        return [
            'AED',
            'AUD',
            'BRL',
            'CAD',
            'CNY',
            'CZK',
            'DKK',
            'EUR',
            'HKD',
            'HUF',
            'ILS',
            'JPY',
            'MYR',
            'MXN',
            'TWD',
            'NZD',
            'NOK',
            'PHP',
            'PLN',
            'GBP',
            'RUB',
            'SGD',
            'SEK',
            'CHF',
            'THB',
            'USD',
        ];
    }

    abstract public function makePayment(array $data);

    abstract public function afterMakePayment(array $data);
}
