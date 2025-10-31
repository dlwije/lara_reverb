<?php

namespace Module\Wallet\Services\Abstracts;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Traits\PaymentErrorTrait;

abstract class WalletPaymentAbstract
{
    use PaymentErrorTrait;

    protected float $amount;

    protected string $currency;

    protected string $chargeId;

    protected $trantype;

    protected $transport = null;

    protected $request_timeout = 10;

    protected $apiToken;

    protected $publicKey;

    protected $notificationToken;

    protected $liveMode;

    protected $description;

    protected $cart_id;

    protected $checkout_token = null;

    protected $merchantUrl;

    protected string $returnUrl = '';

    protected string $cancelUrl = '';

    protected string $notificationUrl = '';

    protected bool $supportRefundOnline = false;

    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->totalAmount = 0;


        $this->supportRefundOnline = false;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function environment()
    {
        Config::set('wallet.transport', $this->transport);
        Config::set('wallet.success_url', $this->returnUrl);
        Config::set('wallet.failure_url', $this->cancelUrl);
        Config::set('wallet.cancel_url', $this->cancelUrl);
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
        //$this->merchantUrl->setFailureUrl($this->cancelUrl);
        //$this->merchantUrl->setCancelUrl($this->cancelUrl);
        return $this;
    }



    public function createPayment(array $data) {
        LOG::info('create payment wallet payment abstract');
        $this->environment();

        $orderCurrency = $this->currency;
        $order = new Order();
        $order->setMerchantUrl($this->merchantUrl);
        if(!empty($this->checkout_token)) { $order->setOrderReferenceId($this->checkout_token); }
        $order->setCurrency($orderCurrency);
        $order->setTotalAmount(new Money($this->amount, $orderCurrency));
        if(isset($data['general'])) {
            $order->setCountryCode($data['general']['country']);
            $order->setTaxAmount(new Money($data['general']['tax_amount'], $orderCurrency));
            if(isset($data['general']['discount_code'])) {
                $order->setDiscount(new Discount($data['general']['discount_code'], new Money($data['general']['discount_amount'], $orderCurrency)));
            }
            $order->setShippingAmount(new Money($data['general']['shipping_amount'], $orderCurrency));
        } else {
            $order->setCountryCode('AE');
        }
        $order->setPaymentType($data['payment_type']);
        $order->setInstalments(3);
        $order->setDescription($this->description);

        // second set Consumer data
        $consumer = new Consumer();
        if(isset($data['general'])) {
            $consumer->setFirstName($data['general']['first_name']);
            $consumer->setLastName($data['general']['last_name']);
            $consumer->setEmail($data['general']['email']);
            $consumer->setPhoneNumber($data['general']['phone']);
        }
        $order->setConsumer($consumer);

        $orderItemCollection = new OrderItemCollection();
        if(isset($data['products'])) {
            foreach($data['products'] as $si_product) {
                $orderItem = new OrderItem();
                $orderItem->setName($si_product['name']);
                $orderItem->setQuantity($si_product['qty']);
                $orderItem->setUnitPrice(new Money($si_product['price'], $orderCurrency));
                $orderItem->setType('Physical');
                $orderItem->setTotalAmount(new Money($si_product['total_price'], $orderCurrency));
                $orderItem->setReferenceId('REF-'.$si_product['id']);
                $orderItem->setSku($si_product['sku']);
                $orderItem->setTaxAmount(new Money(($si_product['tax_amount'] * $si_product['qty']), $orderCurrency));
                $orderItem->setDiscountAmount(new Money(0, $orderCurrency));
                $imageUrl = trim($si_product['image']);
                if (!empty($imageUrl)) {
                    $orderItem->setImageUrl($imageUrl);
                }

                $orderItemCollection->append($orderItem);
            }

        } else if(isset($data['giftcard_id'])) {
            $orderItem = new OrderItem();
            $orderItem->setName('Gift Card');
            $orderItem->setQuantity(1);
            $orderItem->setUnitPrice(new Money($this->amount, $orderCurrency));
            $orderItem->setTotalAmount(new Money($this->amount, $orderCurrency));
            $orderItem->setType('Digital');
            $orderItem->setReferenceId('GIFT-REF-'.$data['giftcard_id']);
            $orderItem->setSku('GIFT-REF-'.$data['giftcard_id']);
            $orderItem->setTaxAmount(new Money(0, $orderCurrency));
            $orderItem->setDiscountAmount(new Money(0, $orderCurrency));
            $orderItemCollection->append($orderItem);
        }
        // sixth set items collection to order
        $order->setItems($orderItemCollection);

        if(isset($data['shipping'])) {
            $tarr = $data['shipping'];
            // third sett Shipping Address
            $address = new Address();
            $address->setFirstName($tarr['first_name']);
            $address->setLastName($tarr['last_name']);
            if(isset($tarr['address_1']) || isset($tarr['city']) || isset($tarr['region']) || isset($tarr['zip']) || isset($tarr['country'])) {

                if(isset($tarr['address_1'])) { $address->setLine1($tarr['address_1']); }
                if(isset($tarr['address_2'])) { $address->setLine2($tarr['address_2']); }
                if(isset($tarr['city'])) { $address->setCity($tarr['city']); }
                if(isset($tarr['region'])) { $address->setRegion($tarr['region']); }
                if(isset($tarr['zip'])) {  }
                if(isset($tarr['country'])) { $address->setCountryCode($tarr['country']); }
                if(isset($tarr['phone'])) { $address->setPhoneNumber($tarr['phone']); }
            }
            $order->setShippingAddress($address);
        }

        if(isset($data['billing'])) {
            $tarr = $data['billing'];
            // third sett Billing Address
            $address = new Address();
            $address->setFirstName($tarr['first_name']);
            $address->setLastName($tarr['last_name']);
            if(isset($tarr['address_1']) || isset($tarr['city']) || isset($tarr['region']) || isset($tarr['zip']) || isset($tarr['country'])) {

                if(isset($tarr['address_1'])) { $address->setLine1($tarr['address_1']); }
                if(isset($tarr['address_2'])) { $address->setLine2($tarr['address_2']); }
                if(isset($tarr['city'])) { $address->setCity($tarr['city']); }
                if(isset($tarr['region'])) { $address->setRegion($tarr['region']); }
                if(isset($tarr['zip'])) {  }
                if(isset($tarr['country'])) { $address->setCountryCode($tarr['country']); }
                if(isset($tarr['phone'])) { $address->setPhoneNumber($tarr['phone']); }
            }
            $order->setBillingAddress($address);
        }

        $sessionName = 'wallet_payment_id';


        $request = new CreateCheckoutRequest($order);
        $response = $this->client->createCheckout($request);
        if($response->isSuccess()) {
            $checkoutURL = $response->getCheckoutResponse()->getCheckoutUrl();
            $checkoutOrderId = $response->getCheckoutResponse()->getOrderId();
            $checkoutCheckoutId = $response->getCheckoutResponse()->getCheckoutId();

            session([$sessionName => $checkoutOrderId]);
            return ['order_ref' => $checkoutOrderId, 'url' => $checkoutURL];
        } else {
            $errorMessage = trim($response->getMessage());

            session()->forget($sessionName);
            $message = (strlen($errorMessage) > 0 ? $errorMessage : 'Unexpected');
            $this->setErrorMessage(trans($message));
            return null;
        }
    }

    public function execute(array $data)
    {
        try {
            return $this->makePayment($data);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }



    public function getPaymentDetails(string $paymentId): bool|array|object
    {
        $response = false;
        try {
            $authOrder = new AuthoriseOrderRequest($paymentId);
            $authedResponse = $this->client->authoriseOrder($authOrder);
            $orderStatus = trim($authedResponse->getOrderStatus());
            $aprrovedArrays = ['authorised', 'approved', 'captured', 'fully_captured'];
            if(in_array($orderStatus, $aprrovedArrays)) {
                return true;
            } else {
                $this->setErrorMessage($authedResponse->getMessage());
                return false;
            }
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return $response;
    }

    public function getOrderDetails(string $paymentId): bool|array|object
    {
        $response = false;
        try {
            $order = new GetOrderRequest($paymentId);
            $orderResponse = $this->client->getOrder($order);
            if($orderResponse->getStatusCode() == 200) {
                $bodyStr = json_decode($orderResponse->getContent());
                $response = (object) $bodyStr;
            }
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
