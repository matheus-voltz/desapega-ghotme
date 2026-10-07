<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsaasPayment extends Model
{
    protected $fillable = [
        'product_id',
        'bundle_id',
        'cart_id',
        'external_reference',
        'asaas_payment_id',
        'asaas_customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_postal_code',
        'customer_address_number',
        'customer_address_complement',
        'amount',
        'status',
        'pix_payload',
        'pix_encoded_image',
        'pix_expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'pix_expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(AsaasWebhookEvent::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'RECEIVED';
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['CONFIRMED', 'RECEIVED'], true);
    }
}
