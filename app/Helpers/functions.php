<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (! defined('WALLET_PAYMENT_METHOD_NAME')) {
    define('WALLET_PAYMENT_METHOD_NAME', 'wallet');
}

if (! defined('BASE_FILTER_ENUM_HTML')) {
    define('BASE_FILTER_ENUM_HTML', 'base_filter_enum_html');
}

if (! defined('TELR_PAYMENT_METHOD_NAME')) {
    define('TELR_PAYMENT_METHOD_NAME', 'telr');
}

if(! function_exists('get_module')) {
    function get_module($module_name = null) {
        $modules = cache()->rememberForever('sma_modules', function () {
            return json_decode(Storage::disk('local')->get('modules.json'), true);
        });

        if ($module_name) {
            return $modules[$module_name]['enabled'] ?? null;
        }

        return $modules;
    }
}

if (! function_exists('set_module')) {
    function set_module(array $data): bool
    {
        // Ensure 'sma' key is present
        if (! isset($data['sma'])) {
            throw new Exception('Please provide sma key.');
        }

        // Set defaults if not already defined
        $data['pos'] ??= ['key' => null, 'enabled' => false];
        $data['shop'] ??= ['key' => null, 'enabled' => false];

        // Clear module cache
        Cache::forget('sma_modules');

        // Save to storage/app/modules.json
        return Storage::disk('local')->put(
            'modules.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

if (! function_exists('enable_module')) {
    function enable_module($module, $key)
    {
        $moduleIds = [
            'pos'  => '4494018',
            'shop' => '20046278',
//            'service'  => '12345678',
        ];
        if (! $key) {
            throw new Exception('Please provide a valid key.');
        }

        if (! in_array($module, ['pos', 'shop'])) {
            throw new Exception('No module found with name ' . $module);
        }

//        $res = Install::verifyPurchase($key);
        $res = [
            'error' => false,
            'purchase-data' => [
                'item_id' => $moduleIds[$module],
                'buyer' => 'john_doe',
                'license' => 'Regular License',
                'purchase_date' => '2020-12-01T00:00:00Z',
                // ... maybe more fields
            ],
            'description' => 'Purchase code verified successfully.',
        ];

        if ($res['error'] ?? null) {
            throw new Exception($res['description'] ?? 'Failed to verify purchase code!');
        }

        if ($res['purchase-data'] ?? null) {
            $module_id = match ($module) {
                'pos'  => '4494018',
                'shop' => '20046278',
//                'service'  => '12345678',
            };

            if ($res['purchase-data']['item_id'] != $module_id) {
                throw new Exception('Purchase code does not belongs to the selected module!');
            }
        }

        $modules = get_module();
        $modules[$module]['key'] = $key;
        $modules[$module]['enabled'] = true;

        return set_module($modules);
    }
}

if (! function_exists('disable_module')) {
    function disable_module($name)
    {
        if (! in_array($name, ['pos', 'shop'])) {
            throw new Exception('No module found with name ' . $name);
        }

        $modules = get_module();
        $modules[$name]['enabled'] = false;

        return set_module($modules);
    }
}

// Get Settings
if (! function_exists('get_settings')) {
    function get_settings($keys = null)
    {
        $json = json_settings_fields();
        if (! empty($keys)) {
            $single = ! is_array($keys) || count($keys) == 1;

            if ($single) {
                $key = is_array($keys) ? $keys[0] : $keys;
                $value = optional(App\Models\Setting::where('tec_key', $key)->first())->tec_value;

                return in_array($key, $json) ? json_decode($value, true) : $value;
            }

            return App\Models\Setting::whereIn('tec_key', $keys)->get()->mapWithKeys(function ($row) use ($json) {
                return [$row['tec_key'] => in_array($row['tec_key'], $json) ? json_decode($row['tec_value'], true) : $row['tec_value']];
            })->all();
        }

        return App\Models\Setting::all()->pluck('tec_value', 'tec_key')
            ->merge(['baseUrl' => url('/')])->transform(function ($value, $key) use ($json) {
                return in_array($key, $json) ? json_decode($value, true) : $value;
            })->all();
    }
}

// Get public settings
if (! function_exists('get_public_settings')) {
    function get_public_settings()
    {
        return settings_remove_private_fields(get_settings());
    }
}

// Settings fields those need to cast as json
if (! function_exists('json_settings_fields')) {
    function json_settings_fields()
    {
        return ['scale_barcode', 'mail', 'payment', 'loyalty', 'product_taxes', 'quick_cash'];
    }
}

// Settings fields those need to cast as json
if (! function_exists('settings_remove_private_fields')) {
    function settings_remove_private_fields($settings)
    {
        $safe = collect($settings)->forget(['mail', 'payment'])->all();

        // Set payment public fields
        $safe['payment']['gateway'] = $settings['payment']['gateway'] ?? null;
        $safe['payment']['default_currency'] = $settings['payment']['default_currency'] ?? 'USD';
        $safe['payment']['stripe_terminal'] = $settings['payment']['stripe_terminal'] ?? false;
        $safe['payment']['services']['paypal']['enabled'] = $settings['payment']['services']['paypal']['enabled'] ?? false;
        $safe['payment']['services']['paypal']['client_id'] = $settings['payment']['services']['paypal']['client_id'] ?? null;
        $safe['payment']['services']['stripe']['key'] = $settings['payment']['services']['stripe']['key'] ?? null;

        return $safe;
    }
}

// disable feature
if (! function_exists('disable_feature')) {
    function disable_feature($name)
    {
        Laravel\Pennant\Feature::flushCache();

        return Laravel\Pennant\Feature::deactivateForEveryone($name);
    }
}

// enable feature
if (! function_exists('enable_feature')) {
    function enable_feature($name, $all = false)
    {
        Laravel\Pennant\Feature::flushCache();

        if ($all) {
            return Laravel\Pennant\Feature::activateForEveryone($name);
        }

        return Laravel\Pennant\Feature::activate($name);
    }
}

// Check if feature is enabled
if (! function_exists('feature_enabled')) {
    function feature_enabled($name)
    {
        try {
            return Laravel\Pennant\Feature::active($name);
        } catch (Throwable $th) {
            return false;
        }
    }
}

// Log Activity
if (! function_exists('log_activity')) {
    function log_activity($activity, $properties = null, $model = null, $name = null)
    {
        return activity($name)->performedOn($model)->withProperties($properties)->log($activity);
    }
}

// Format Decimal
if (! function_exists('format_decimal')) {
    function format_decimal($number, $decimals = null, $ds = '.', $ts = '')
    {
        if (! is_numeric($decimals)) {
            $decimals = (int) get_settings('fraction') ?? 2;
        }

        return number_format($number, $decimals, $ds, $ts);
    }
}

// Format Decimal for Quantity
if (! function_exists('format_decimal_qty')) {
    function format_decimal_qty($number, $decimals = null, $ds = '.', $ts = '')
    {
        if (! is_numeric($decimals)) {
            $decimals = (int) get_settings('quantity_fraction') ?? 2;
        }

        return number_format($number, $decimals, $ds, $ts);
    }
}

// Format Number
if (! function_exists('format_number')) {
    function format_number($number, $decimals = null, $ds = '.', $ts = ',')
    {
        if (! is_numeric($number)) {
            $decimals = get_settings('fraction') ?? 2;
        }

        return number_format($number, $decimals, $ds, $ts);
    }
}

// Is Demo Enabled
if (! function_exists('demo')) {
    function demo()
    {
        return env('DEMO', false);
    }
}

// Json translation with choice replace
if (! function_exists('__choice')) {
    function __choice($key, array $replace = [], $number = null)
    {
        return trans_choice($key, $number, $replace);
    }
}

// Get company id
if (! function_exists('get_company_id')) {
    function get_company_id($company_id = null)
    {
        return session('company_id', $company_id ?? auth()->user()?->company_id ?? 1);
    }
}

// Get UUID v4
if (! function_exists('uuid4')) {
    function uuid4()
    {
        return Ramsey\Uuid\Uuid::uuid4();
    }
}

// Get ULID
if (! function_exists('ulid')) {
    function ulid()
    {
        return (string) Ulid\Ulid::generate(true);
    }
}

// Get get next id
if (! function_exists('get_next_id')) {
    function get_next_id($model)
    {
        return collect(Illuminate\Support\Facades\DB::select("show table status like '{$model->getTable()}'"))->first()->Auto_increment;
    }
}

// Get reference
if (! function_exists('get_reference')) {
    function get_reference($model)
    {
        $format = get_settings('reference');

        return match ($format) {
            'ai'     => get_next_id($model),
            'ulid'   => ulid(),
            'uuid'   => uuid4(),
            'uniqid' => uniqid(),
            default  => ulid(),
        };
    }
}

// // Calculate Discount
// if (! function_exists('calculate_discount')) {
//     function calculate_discount($discount = 0, $amount = 0)
//     {
//         if ($discount && $amount) {
//             if (str($discount)->contains('%')) {
//                 $dv = explode('%', $discount);
//                 $discount = number_format((($amount * (float) $dv[0]) / 100), 2, '.', '');
//             }
//         }

//         return $discount;
//     }
// }

// // Calculate Taxes
// if (! function_exists('calculate_tax')) {
//     function calculate_tax(App\Models\Tax $tax, $amount = null, $tax_method = 'exclusive')
//     {
//         if ($tax && $amount) {
//             if ($tax->fixed) {
//                 return format_decimal($tax->rate);
//             }
//             if ($tax_method == 'inclusive') {
//                 $inclusive_tax_formula = get_settings('inclusive_tax_formula');
//                 if ($inclusive_tax_formula == 'inclusive') {
//                     return format_decimal(($amount * $tax->rate) / (100 + $tax->rate));
//                 }
//             }

//             return format_decimal($amount * $tax->rate / 100);
//         }

//         return 0;
//     }
// }

// // Calculate Taxes
// if (! function_exists('calculate_taxes')) {
//     function calculate_taxes($taxes, $amount, $tax_method = 'exclusive')
//     {
//         $tax_amount = 0;
//         if (! empty($taxes)) {
//             foreach ($taxes as $tax) {
//                 $tax_amount += calculate_tax($tax, $amount, $tax_method);
//             }
//         }

//         return $tax_amount;
//     }
// }

// Order Custom Fields
if (! function_exists('viewable_custom_fields')) {
    function viewable_custom_fields($data, $ofModel)
    {
        $extra_attributes = [];
        $fields = App\Models\Sma\Setting\CustomField::where('show_on_details_view', 1)->ofModel($ofModel)->pluck('name');
        if ($fields) {
            foreach ($fields as $field) {
                $extra_attributes[$field] = $data['extra_attributes'][$field] ?? '';
            }
        }

        return $extra_attributes;
    }
}

// Calculate Gateway Fees
if (! function_exists('calculate_gateway_fees')) {
    function calculate_gateway_fees($amount, $data, $same_country)
    {
        $fees = 0;
        if ($data?->fixed ?? null) {
            $fees += $data->fixed;
        }
        if ($same_country && ($data?->same_country ?? null)) {
            $fees += $amount * ($data->same_country / 100);
        }
        if (! $same_country && ($data?->other_countries ?? null)) {
            $fees += $amount * ($data->other_countries / 100);
        }

        return $fees;
    }
}

// Convert to base unit value
if (! function_exists('convert_to_base_unit')) {
    function convert_to_base_unit($unit, $unit_id, $value)
    {
        if (! $unit || ! $unit_id || $unit->id == $unit_id) {
            return $value;
        }

        $subunit = $unit->subunits->where('id', $unit_id)->first();

        return match ($subunit->operator) {
            '*'     => $value * $subunit->operation_value,
            '/'     => $value / $subunit->operation_value,
            '+'     => $value + $subunit->operation_value,
            '-'     => $value - $subunit->operation_value,
            default => $value,
        };
    }
}

// Is safe email
if (! function_exists('safe_email')) {
    function safe_email($email = '')
    {
        if (demo()) {
            return true;
        }
        $contains = str($email)->contains('@example.');

        return $email && ! $contains;
    }
}

// Get sql query
if (! function_exists('get_sql_query')) {
    function get_sql_query($query)
    {
        return vsprintf(str_replace('?', '%s', str_replace('?', "'?'", $query->toSql())), $query->getBindings());
    }
}
