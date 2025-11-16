<?php

namespace App\Http\Requests\Sma\Order;

use App\Rules\ExtraAttributes;
use App\Rules\ProductVariation;
use App\Services\CheckOverSelling;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Services\OrderCalculator;
use Modules\GiftCard\Models\GiftCard;

class SaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can($this->route('sale') ? 'update-sales' : 'create-sales');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date'        => 'required|date',
            'due_date'    => 'nullable|date',
            'reference'   => 'nullable|max:50|unique:sales,reference,' . $this->route('sale')?->id,
            'customer_id' => 'required|exists:customers,id',

            'details'       => 'nullable',
            'taxes'         => 'nullable|array',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'mimes:' . ((get_settings('attachment_exts') ?? null) ?: 'jpg,png,pdf,xlsx,docx,zip'),

            'items'                         => 'required|array|min:1',
            'items.*.id'                    => 'nullable',
            'items.*.discount'              => 'nullable',
            'items.*.type'                  => 'nullable',
            'items.*.expiry_date'           => 'nullable|date',
            'items.*.customer_id'           => 'nullable',
            'items.*.quantity'              => 'required|numeric|min:0',
            'items.*.unit_id'               => 'nullable|exists:units,id',
            'items.*.cost'                  => 'nullable|numeric|min:0',
            'items.*.price'                 => 'required|numeric|min:0',
            'items.*.taxes'                 => 'nullable|array',
            'items.*.comment'               => 'nullable|string|max:190',
            'items.*.old_quantity'          => 'nullable|numeric',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.variations'            => new ProductVariation,
            'items.*.variations.*.id'       => new ProductVariation,
            'items.*.variations.*.quantity' => 'required_with:items.*.variations|numeric|min:0',
            'items.*.variations.*.unit_id'  => 'nullable|exists:units,id',
            'items.*.variations.*.cost'     => 'nullable',
            'items.*.variations.*.price'    => 'nullable',
            'items.*.variations.*.discount' => 'nullable',
            'items.*.variations.*.taxes'    => 'nullable|array',

            'extra_attributes' => new ExtraAttributes('sale'),

            'tendered'        => 'nullable',
            'order_number'    => 'nullable',
            'order_reference' => 'nullable',
            'change_returned' => 'nullable',

            'payment_method'          => 'nullable|string',
            'shop'                    => 'nullable|boolean',
            'shipping'                => 'nullable|numeric',
            'address_id'              => 'nullable|exists:addresses,id',
            'shop_coupon_id'          => 'nullable|exists:shop_coupons,id',
            'shop_shipping_method_id' => 'nullable|exists:shop_shipping_methods,id',

            'payments'               => 'nullable|array',
            'payments.*.amount'      => 'nullable|numeric',
            'payments.*.method'      => 'nullable|string',
            'payments.*.method_data' => 'nullable|array',
        ];
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        if (! get_settings('overselling')) {
            $error = (new CheckOverSelling)->check($this->input('items'));
            if ($error) {
                throw ValidationException::withMessages($error);
            }
        }
    }

    /**
     * Get the validated data from the request.
     *
     * @param  array|int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        // $data = data_get($this->validator->validated(), $key, $default);

        foreach ($data['items'] as $key => &$item) {
            if (($item['type'] ?? null) == 'Gift Card' && $item['quantity'] > 0) {
                if (GiftCard::where('number', $item['code'])->exists()) {
                    throw ValidationException::withMessages(['items.' . $key . '.code' => __('Gift card already exists')]);
                }
                GiftCard::create([
                    'number'      => $item['code'],
                    'amount'      => $item['price'],
                    'expiry_date' => $item['expiry_date'],
                    'customer_id' => $item['customer_id'],
                ]);
            }
            unset($item['customer_id'], $item['expiry_date']);
        }

        return OrderCalculator::calculate($data, 'price');
    }
}
