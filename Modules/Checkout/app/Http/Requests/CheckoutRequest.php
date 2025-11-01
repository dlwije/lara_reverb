<?php

namespace Modules\Checkout\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Wallet\Services\PaymentMethodEnum;

class CheckoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'amount' => 'required|min:0',
        ];
        $paymentMethods = Arr::where(PaymentMethodEnum::values(), function ($value) {
            if($value == 'apple_pay') {
                return true;
            }
            return true;
//            else {
//                return get_payment_setting('status', $value) == 1;
//            }
        });

        $rules['payment_method'] = 'sometimes|' . Rule::in($paymentMethods);

        $addressId = $this->input('address.address_id');
        $rules['shipping_method'] = 'required|' . Rule::in(['default', '']);

        if(auth()->check()) {
            $rules['address.address_id'] = 'required_without:address.name';
            if (! $this->has('address.address_id') || $addressId === 'new') {
                $rules = array_merge($rules, $this->getCustomerAddressValidation('address.'));
            }
        }

        $billingAddressSameAsShippingAddress = false;
        $is_billing_enabled = false;
        if ($is_billing_enabled) {
            $rules['billing_address_same_as_shipping_address'] = 'nullable|' . Rule::in(['0', '1']);
            if (! $this->input('billing_address_same_as_shipping_address')) {
                $rules['billing_address'] = 'array';
                $rules = array_merge($rules, $this->getCustomerAddressValidation('billing_address.'));
            } else {
                $billingAddressSameAsShippingAddress = true;
            }
        }

        if (! auth()->check()) {
            $rules = array_merge($rules, $this->getCustomerAddressValidation('address.'));
            $rules['address.email'] = 'required|email|max:60|min:6';
            if ($billingAddressSameAsShippingAddress) {
                $rules = $this->removeRequired($rules, [
                    'address.country',
                    'address.state',
                    'address.city',
                    'address.address',
                    'address.phone',
                    'address.zip_code',
                ]);
            }
        }

        $isCreateAccount = ! auth()->check() && $this->input('create_account') == 1;
        if ($isCreateAccount) {
            $rules['password'] = 'required|min:6';
            $rules['password_confirmation'] = 'required|same:password';
            $rules['address.email'] = 'required|max:60|min:6|email|unique:users,email';
            $rules['address.name'] = 'required|min:3|max:120';
        }

        $availableMandatoryFields = $this->getEnabledMandatoryFieldsAtCheckout();
        $mandatoryFields = array_keys($this->getDefaultMandatoryFieldsAtCheckout());
        $nullableFields = array_diff($mandatoryFields, $availableMandatoryFields);
        if ($nullableFields) {
            foreach ($nullableFields as $value) {
                $key = "address.$value";

                if (! isset($rules[$key])) {
                    continue;
                }

                if (is_string($rules[$key])) {
                    $rules[$key] = str_replace('required', 'nullable', $rules[$key]);

                    continue;
                }

                if (is_array($rules[$key])) {
                    $rules[$key] = array_merge(['nullable'], array_filter($rules[$key], fn ($item) => $item !== 'required'));
                }
            }
        }
        return $rules;
    }

    public function removeRequired(array $rules, string|array $keys): array
    {
        if (! is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            if (! empty($rules[$key])) {
                $values = $rules[$key];
                if (is_string($values)) {
                    $explode = explode('|', $values);
                    if (($k = array_search('required', $explode)) !== false) {
                        unset($explode[$k]);
                    }
                    $explode[] = 'nullable';
                    $values = $explode;
                } elseif (is_array($values)) {
                    if (($k = array_search('required', $values)) !== false) {
                        unset($values[$k]);
                    }
                    $values[] = 'nullable';
                }
                $rules[$key] = $values;
            }
        }

        return $rules;
    }

    public function getCustomerAddressValidation(string|null $prefix = ''): array
    {
        $rules = [
            //$prefix . 'name' => 'string|required|min:3|max:120|regex:/^[A-Za-z][A-Za-z0-9\s]*$/',
            $prefix . 'name' => 'required|min:3|max:120',
            $prefix . 'email' => 'email|nullable|max:60|min:6',
            $prefix . 'state' => 'required|max:120',
            $prefix . 'city' => 'required|max:120',
            $prefix . 'address' => 'required|max:120',
            $prefix . 'phone' => $this->getPhoneValidationRule(),
        ];

        $availableMandatoryFields = $this->getEnabledMandatoryFieldsAtCheckout();
        $mandatoryFields = array_keys($this->getDefaultMandatoryFieldsAtCheckout());
        $nullableFields = array_diff($mandatoryFields, $availableMandatoryFields);

        if ($nullableFields) {
            foreach ($nullableFields as $key) {
                if (!isset($rules[$key])) {
                    continue;
                }

                if (is_string($rules[$key])) {
                    $rules[$key] = str_replace('required', 'nullable', $rules[$key]);

                    continue;
                }

                if (is_array($rules[$key])) {
                    $rules[$key] = array_merge(
                        ['nullable'],
                        array_filter($rules[$key], fn($item) => $item !== 'required')
                    );
                }
            }
        }

        return $rules;
    }

    public function getPhoneValidationRule(bool $asArray = false): string|array
    {
        // First taking the validation rule from config file
        $rule = config('checkout.phone_validation_rule');

        // Now check to add mandatory rules added from db
        if (!in_array('phone', $this->getEnabledMandatoryFieldsAtCheckout())) {
            return 'nullable|' . $rule;
        }

        return 'required|' . $rule;
    }

    public function getDefaultMandatoryFieldsAtCheckout(): array
    {
        // Get the language translation
        return [
            'phone' => trans('checkout::messages.phone'),
            'email' => trans('checkout::messages.email'),
            'country' => trans('checkout::messages.country'),
            'state' => trans('checkout::messages.state'),
            'city' => trans('checkout::messages.city'),
            'address' => trans('checkout::messages.address'),
        ];
    }
    public function getEnabledMandatoryFieldsAtCheckout(): array
    {
        $fields = json_encode(['phone']); // Can add the json fileds from db or something similar to that

        if(!$fields){
            return array_keys($this->getDefaultMandatoryFieldsAtCheckout());
        }
        return json_decode((string)$fields, true);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
