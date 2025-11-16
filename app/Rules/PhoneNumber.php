<?php

namespace App\Rules;

use Closure;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        // if (! $phoneUtil->isValidNumber($value)) {
        //     $fail(__('The phone number is invalid.'));
        // }
        try {
            $phoneUtil->parse($value);
        } catch (NumberParseException $e) {
            $fail($e->getMessage());
        }
    }
}
