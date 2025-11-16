<?php

namespace Modules\GiftCard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemGiftCardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'otp' => 'sometimes|required|string|max:6'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages()
    {
        return [
            'code.required' => 'Gift card code is required',
            'code.max' => 'Gift card code must not exceed 50 characters',
            'otp.required' => 'OTP verification is required for this redemption',
            'otp.max' => 'OTP must be 6 digits'
        ];
    }
}
