<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeConnection extends Model
{
    protected $fillable = [
        'shop_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
