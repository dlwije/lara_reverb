<?php

namespace Modules\GiftCard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GiftCardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'code' => 'sometimes|required|string|max:50|unique:gift_cards,code',
            'original_value' => 'required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'batch_id' => 'nullable|exists:gift_card_batches,id',
            'promo_rule_id' => 'nullable|exists:promo_rules,id',
            'issued_to' => 'nullable|string|max:255',
            'expires_at' => 'required|date|after:today',
            'status' => 'sometimes|required|in:active,inactive,void'
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['code'] = 'sometimes|required|string|max:50|unique:gift_cards,code,' . $this->route('id');
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
