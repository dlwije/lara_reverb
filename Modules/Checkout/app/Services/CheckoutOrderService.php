<?php

namespace Modules\Checkout\Services;

use Illuminate\Support\Str;

class CheckoutOrderService
{
    public function setOrderSessionData(string|null $token, string|array $data): array
    {
        if (! $token) {
            $token = $this->getOrderSessionToken();
        }

        $data = array_replace_recursive($this->getOrderSessionData($token), $data);

        $data = $this->cleanData($data);

        session([md5('checkout_address_info_' . $token) => $data]);

        return $data;
    }

    public function cleanData(array $data): array
    {
        foreach ($data as $key => $item) {
            if (! is_string($item)) {
                continue;
            }

            $data[$key] = $this->clean($item);
        }

        return $data;
    }

    public function clean(array|string|null $dirty): array|string|null
    {
        if (config('checkout.enable_less_secure_web', false)) {
            return $dirty;
        }

        if (! $dirty && $dirty !== null) {
            return $dirty;
        }

        if (! is_numeric($dirty)) {
            $dirty = (string) $dirty;
        }

        return $dirty;
    }
    public function getOrderSessionData(string|null $token = null) :array
    {
        if (! $token) {
            $token = $this->getOrderSessionToken();
        }

        $data = [];
        $sessionKey = md5('checkout_address_info_' . $token);
        if (session()->has($sessionKey)) {
            $data = session($sessionKey);
        }

        return $this->cleanData($data);
    }

    public function getOrderSessionToken(): string
    {
        if(request()->input('order_checkout_token')) {
            $token = request()->input('order_checkout_token');
            session(['order_checkout_token' => $token]);
        } else if (session()->has('order_checkout_token')) {
            $token = session('order_checkout_token');
        } else {
            $token = md5(Str::random(40));
            session(['order_checkout_token' => $token]);
        }

        return $token;
    }
}
