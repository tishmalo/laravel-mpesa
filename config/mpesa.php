<?php

return [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'passkey' => env('MPESA_PASSKEY'),
    'business_short_code' => env('MPESA_BUSINESS_SHORT_CODE'),
    'callback_url' => env('MPESA_CALLBACK_URL'),
    'sandbox' => env('MPESA_SANDBOX', true),
];