<?php

return [
    'enabled' => filter_var(env('TELEGRAM_ENABLED', true), FILTER_VALIDATE_BOOL),
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'chat_id' => env('TELEGRAM_CHAT_ID', ''),
    'timeout' => (int) env('TELEGRAM_TIMEOUT', 5),
];
