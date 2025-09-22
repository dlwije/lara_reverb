<?php

return [
    'name' => 'GiftCard',

    'otp' => [
        'enabled' => env('WALLET_OTP_ENABLED', false),
        'amount_threshold' => env('WALLET_OTP_AMOUNT_THRESHOLD', 1000), // AED
        'expiry_minutes' => env('WALLET_OTP_EXPIRY_MINUTES', 10),
        'max_daily_redemptions' => env('WALLET_OTP_MAX_DAILY_REDEMPTIONS', 5),
        'channels' => ['mail', 'sms'], // Which channels to use
    ],
];
