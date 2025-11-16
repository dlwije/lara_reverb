<?php

return [
    'name' => 'Checkout',
    'enable_less_secure_web' => false,
    'phone_validation_rule' => env('PHONE_VALIDATION_RULE', 'min:8|max:15|regex:/^([0-9\s\-\+\(\)]*)$/'),
];
