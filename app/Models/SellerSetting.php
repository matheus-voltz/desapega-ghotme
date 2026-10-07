<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerSetting extends Model
{
    protected $fillable = ['asaas_api_key', 'asaas_webhook_token', 'pix_key', 'telegram_bot_token', 'telegram_chat_id', 'shopee_partner_id', 'shopee_partner_key', 'shopee_shop_id'];

    protected $casts = [
        'asaas_api_key' => 'encrypted',
        'asaas_webhook_token' => 'encrypted',
        'pix_key' => 'encrypted',
        'telegram_bot_token' => 'encrypted',
        'telegram_chat_id' => 'encrypted',
        'shopee_partner_id' => 'encrypted',
        'shopee_partner_key' => 'encrypted',
        'shopee_shop_id' => 'encrypted',
    ];
}
