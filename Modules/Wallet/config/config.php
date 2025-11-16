<?php

return [
    'name' => 'Wallet',
    'kyc' => [
        'enabled' => env('WALLET_KYC_ENABLED', false)
    ],
    'kyc_tier_limits' => [
        0 => 1000,   // Tier 0: AED 1,000 max balance
        1 => 5000,   // Tier 1: AED 5,000 max balance
        2 => 20000,  // Tier 2: AED 20,000 max balance
        3 => 50000   // Tier 3: AED 50,000 max balance (no limit)
    ],
    'kyc_document_requirements' => [
        0 => ['id_proof'],
        1 => ['id_proof', 'address_proof'],
        2 => ['id_proof', 'address_proof', 'income_proof'],
        3 => ['id_proof', 'address_proof', 'income_proof', 'tax_document']
    ],
    'notifications' => [
        'enabled' => env('WALLET_NOTIFICATIONS_ENABLED', true),
        'sms_enabled' => env('WALLET_SMS_NOTIFICATIONS', false),
        'channels' => [
            'transaction' => ['mail', 'database'],
            'expiry_reminder' => ['mail'],
            'promotional' => ['mail']
        ],
        'expiry_reminder_days' => [30, 7, 1],
    ],

    'max_daily_redemptions' => 5,
    'max_hourly_spend' => 10000,
    'auto_freeze_threshold' => 50000,
];
