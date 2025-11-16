<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'], // CSRF route isn't needed for Passport, but harmless if left
    'allowed_methods' => ['*'], // allow GET, POST, PUT, DELETE, etc.

    'allowed_origins' => [
        env('APP_URL', 'http://localhost:5173'),
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://localhost:3000', // local frontend during dev
        'http://localhost',      // production frontend (if using same domain for SPA or embedded)
        'https://taggo.ae',      // production frontend (if using same domain for SPA or embedded)
    ],

    'allowed_origins_patterns' => [
//        '/^https?:\/\/([a-z0-9-]+\.)?taggo\.ae$/', // allow subdomains like admin.taggo.ae
    ],

    'allowed_headers' => ['*'], // allow all headers including Authorization
    'exposed_headers' => [],    // you can add ['Authorization'] here if frontend needs to read it
    'max_age' => 0,          // cache preflight request for 1 hour
    'supports_credentials' => false, // ✅ false because you're using token-based Passport, NOT cookies
];
