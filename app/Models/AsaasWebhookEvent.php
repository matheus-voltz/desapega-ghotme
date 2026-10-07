<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsaasWebhookEvent extends Model
{
    protected $fillable = [
        'asaas_payment_id',
        'event_id',
        'event',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AsaasPayment::class, 'asaas_payment_id');
    }
}
