<?php

return [
    'enabled' => filter_var(env('ASAAS_ENABLED', false), FILTER_VALIDATE_BOOL),

    'environment' => env('ASAAS_ENV', 'production'),

    'base_urls' => [
        'production' => 'https://api.asaas.com/v3',
        'sandbox' => 'https://api-sandbox.asaas.com/v3',
    ],

    'api_key' => env('ASAAS_API_KEY'),
    'timeout' => (int) env('ASAAS_TIMEOUT', 15),
    'credit_card_timeout' => (int) env('ASAAS_CREDIT_CARD_TIMEOUT', 60),
    'credit_card_max_installments' => (int) env('ASAAS_CREDIT_CARD_MAX_INSTALLMENTS', 12),
    'credit_card_small_purchase_maximum' => (float) env('ASAAS_CREDIT_CARD_SMALL_PURCHASE_MAXIMUM', 100),
    'credit_card_small_purchase_installments' => (int) env('ASAAS_CREDIT_CARD_SMALL_PURCHASE_INSTALLMENTS', 5),
    'credit_card_free_installments' => (int) env('ASAAS_CREDIT_CARD_FREE_INSTALLMENTS', 5),
    'credit_card_fee_percentage' => (float) env('ASAAS_CREDIT_CARD_FEE_PERCENTAGE', 3.49),
    'credit_card_fixed_fee' => (float) env('ASAAS_CREDIT_CARD_FIXED_FEE', 0.49),
    'pix_due_days' => (int) env('ASAAS_PIX_DUE_DAYS', 1),

    // Esta URL precisa ser pública e HTTPS. Um domínio .test do Herd só funciona localmente.
    'webhook_url' => env('ASAAS_WEBHOOK_URL'),
    'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
    'webhook_email' => env('ASAAS_WEBHOOK_EMAIL'),
];
