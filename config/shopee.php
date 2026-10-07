<?php

return [
    'enabled' => (bool) env('SHOPEE_ENABLED', false),

    'environment' => env('SHOPEE_ENV', 'production'),

    'base_urls' => [
        'production' => 'https://partner.shopeemobile.com',
        'sandbox' => 'https://partner.test-stable.shopeemobile.com',
    ],

    'partner_id' => env('SHOPEE_PARTNER_ID'),
    'partner_key' => env('SHOPEE_PARTNER_KEY'),

    // Use URLs HTTPS públicas e fixas. A URL do webhook entra na assinatura da Shopee.
    'redirect_url' => env('SHOPEE_REDIRECT_URL'),
    'webhook_url' => env('SHOPEE_WEBHOOK_URL'),

    'verify_webhook_signature' => (bool) env('SHOPEE_VERIFY_WEBHOOK_SIGNATURE', true),
    'timeout' => (int) env('SHOPEE_TIMEOUT', 15),
];
