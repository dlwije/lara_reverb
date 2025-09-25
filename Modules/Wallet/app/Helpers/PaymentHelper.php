<?php

namespace Modules\Wallet\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PaymentHelper
{
    public const COD = 'cod';
    public const BANK_TRANSFER = 'bank_transfer';
    public const APPLE_PAY = 'apple_pay';
    public const WALLET = 'wallet';

    public static function getRedirectURL(?string $checkoutToken = null): string
    {
        return apply_filters(PAYMENT_FILTER_REDIRECT_URL, $checkoutToken, BaseHelper::getHomepageUrl());
    }

    public static function getCancelURL(?string $checkoutToken = null): string
    {
        return apply_filters(PAYMENT_FILTER_CANCEL_URL, $checkoutToken, BaseHelper::getHomepageUrl());
    }

    public static function storeLocalPayment(array $args = [])
    {
        $data = [
            'user_id' => Auth::id() ?: 0,
            ...$args,
        ];

        $orderIds = (array) $data['order_id'];

        $payment = app(PaymentInterface::class)->getFirstBy([
            'charge_id' => $data['charge_id'],
            ['order_id', 'IN', $orderIds],
        ]);

        if ($payment) {
            return false;
        }

        $paymentChannel = Arr::get($data, 'payment_channel', self::COD);

        return app(PaymentInterface::class)->create([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $data['charge_id'],
            'order_id' => Arr::first($orderIds),
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => $paymentChannel,
            'status' => Arr::get($data, 'status', PaymentStatusEnum::PENDING),
        ]);
    }

    public static function storeSubscriptionLocalPayment(array $args = [])
    {
        $data = [
            'user_id' => Auth::id() ?: 0,
            ...$args,
        ];

        $paymentChannel = Arr::get($data, 'payment_channel', PaymentMethodEnum::COD);

        return app(PaymentInterface::class)->create([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $data['charge_id'],
            'subscriber_id' => $data['subscriber_id'],
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'payment_channel' => $paymentChannel,
            'status' => Arr::get($data, 'status', PaymentStatusEnum::PENDING),
        ]);
    }

    public static function formatLog(
        array $input,
        string|int $line = '',
        string $function = '',
        string $class = ''
    ): array {
        return [
            ...$input,
            'user_id' => Auth::id() ?: 0,
            'ip' => Request::ip(),
            'line' => $line,
            'function' => $function,
            'class' => $class,
            'userAgent' => Request::userAgent(),
        ];
    }

    public static function defaultPaymentMethod(): string
    {
        return setting('default_payment_method', PaymentMethodEnum::COD);
    }

    public static function getAvailableCountries(string $paymentMethod): array
    {
        $json = get_payment_setting('available_countries', $paymentMethod);

        $countries = Helper::countries();

        if ($json === null || $json === '[]') {
            return $countries;
        }

        $selectedCountries = json_decode($json, true);

        if (empty($selectedCountries)) {
            return $countries;
        }

        return array_intersect_key($countries, array_flip($selectedCountries));
    }

    public static function getPaymentMethodRules(string $paymentMethod): array
    {
        return [
            get_payment_setting_key('name', $paymentMethod) => ['required', 'string', 'max:255'],
            get_payment_setting_key('description', $paymentMethod) => ['required', 'string'],
            get_payment_setting_key('available_countries', $paymentMethod) => ['nullable', 'array'],
            sprintf('%s.*', get_payment_setting_key('available_countries', $paymentMethod)) => ['nullable', 'string'],
        ];
    }
}
