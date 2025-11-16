<?php

namespace Modules\GiftCard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GiftCardBatchRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'original_value' => 'required|numeric|min:0',
            'promo_rule_id' => 'nullable|exists:promo_rules,id',
            'expires_at' => 'required|date|after:today',
            'metadata' => 'nullable|array'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
