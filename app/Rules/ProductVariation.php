<?php

namespace App\Rules;

use Closure;
use App\Models\Sma\Product\Product;
use App\Models\Sma\Product\Variation;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ProductVariation implements DataAwareRule, ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $checkVariation = false;
        $attribute_array = explode('.', $attribute);
        $product_field = $attribute_array[0] . '.' . $attribute_array[1] . '.product_id';
        $product_id = data_get($this->data, $product_field);

        if (! $product_id) {
            $fail(trans('validation.required', ['attribute' => $product_field]));
        }

        $product = Product::select(['id', 'has_variants'])->find($product_id);

        if ($product && $product->has_variants == 1) {
            if ($attribute_array[0] == 'items' && $attribute_array[2] == 'variations' && isset($attribute_array[3]) && ! $value) {
                $fail(trans('validation.required', ['attribute' => $attribute]));
            } elseif (isset($attribute_array[3])) {
                $checkVariation = true;
            }

            if (! $value) {
                $fail(trans('validation.required', ['attribute' => $attribute]));
            } elseif ($checkVariation && $value && Variation::where('id', $value)->where('product_id', $product_id)->doesntExist()) {
                $fail(trans('validation.exists', ['attribute' => $attribute]));
            }
        }
    }
}
