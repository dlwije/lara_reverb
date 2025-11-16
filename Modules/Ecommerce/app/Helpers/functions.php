<?php

/** E-commerce */
// Format number
use App\Models\Sma\Order\Sale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

if (! function_exists('address')) {
    function address($address, $address_label = false)
    {
        $address_str = '';

        if ($address->name) {
            $address_str .= '<div class="font-bold text-lg">' . $address->name . ', </div>';
        }
        if ($address->company && is_string($address->company)) {
            $address_str .= '<div class="font-bold">' . $address->company . ', </div>';
        }

        if ($address_label) {
            $address_str .= '<div class="text-sm flex"><div class="min-w-16">' . __('Address') . ':</div><div>';
        }
        if ($address->lot_no || $address->street) {
            $address_str .= '<div>' . ($address->lot_no) . ' ' . $address->street . '</div>';
        }
        if ($address->address_line_1) {
            $address_str .= '<div>' . $address->address_line_1 . '</div>';
        }
        if ($address->address_line_2) {
            $address_str .= '<div>' . $address->address_line_2 . '</div>';
        }
        if ($address->city || $address->postal_code || $address->state || $address->country) {
            $address_str .= '<div>' . ($address->city) . ' ' . ($address->postal_code) . ' ' . ($address->state?->name) . ' ' . ($address->country?->name) . '</div>';
        }
        if ($address_label) {
            $address_str .= '</div></div>';
        }

        if ($address->phone) {
            $address_str .= '<div class="text-sm flex"><div class="min-w-16">' . __('Phone') . ':</div> ' . $address->phone . '</div>';
        }
        if ($address->email) {
            $address_str .= '<div class="text-sm flex"><div class="min-w-16">' . __('Email') . ':</div> ' . $address->email . '</div>';
        }

        return str($address_str)->toHtmlString();
    }
}

// Format number
if (! function_exists('format_decimal')) {
    function format_decimal($number = 0, $fraction = null)
    {
        if (! $fraction) {
            $fraction = Cache::memo()->get('fraction');
            if (! $fraction) {
                $fraction = get_settings('fraction');
                Cache::memo()->put('fraction', $fraction);
            }
        }

        return number_format($number, $fraction, '.', '');
    }
}

// Format currency
if (! function_exists('format_currency')) {
    function format_currency($number = 0)
    {
        $currency = session('shop_currency', null) ?? default_currency();

        return Number::currency(
            $number,
            in: $currency->currency?->code ?? $currency->code,
            locale: app()->getLocale(),
            precision: $currency->currency?->precision ?? $currency->precision
        );
    }
}

// Get currency value
if (! function_exists('currency_value')) {
    function currency_value($amount, $default = false)
    {
        $currency = session('shop_currency', null);
        if (! $currency || $default) {
            $currency = default_currency();
        }
        $fraction = cache()->flexible('shop_fraction', [15, 30], (int) get_settings('fraction') ?? 2);
        $amount = format_number($currency ? ($currency->exchange_rate ?? 1) * $amount : $amount, $fraction);

        if ($currency?->show_at_end) {
            return $amount . ($currency->currency?->symbol ?? $currency->symbol ?: '');
        }

        return ($currency?->currency?->symbol ?? $currency?->symbol ?: '') . $amount;
    }
}

// Cart items quantity
if (! function_exists('cart_items_quantity')) {
    function cart_items_quantity($cartId = null)
    {
        $cartId ??= request()->cookie('cart_id');

        return \Modules\Cart\Models\CartItem::where('cart_id', $cartId)->sum('qty');
    }
}

// Get unpaid orders count for the customer
if (! function_exists('my_unpaid_shop_orders')) {
    function my_unpaid_shop_orders()
    {
        $user = auth()->user();
        if ($user) {
            return Sale::whereNotNull('shop')->where('shop', true)->unpaid()->ofCustomer($user->customer_id)->count();
        }

        return 1;
    }
}

// Get meta string
if (! function_exists('meta_array_to_string')) {
    function meta_array_to_string($meta)
    {
        $s = [];
        foreach ($meta as $key => $value) {
            $s[] = $key . ': ' . $value;
        }

        return implode(', ', $s);
    }
}

// Get shop header code
if (! function_exists('shop_header_code')) {
    function shop_header_code()
    {
        return get_settings('shop_header_code');
    }
}

// Get shop footer code
if (! function_exists('shop_footer_code')) {
    function shop_footer_code()
    {
        return get_settings('shop_footer_code');
    }
}
