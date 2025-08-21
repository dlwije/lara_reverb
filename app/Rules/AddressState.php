<?php

namespace App\Rules;

use Closure;
use Nnjeim\World\Models\State;
use Illuminate\Contracts\Validation\ValidationRule;

class AddressState implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value) {
            if (State::where('id', $value)->doesntExist()) {
                $fail(trans('validation.exists', ['attribute' => __('state')]));
            }
        } elseif ($value !== '0') {
            $fail(trans('validation.required', ['attribute' => __('state')]));
        }
    }
}
