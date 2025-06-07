<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false), // default ke false (Sandbox) jika tidak ada di .env
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),    // default ke true
    'is_3ds' => env('MIDTRANS_IS_3DS', true),                // default ke true
];