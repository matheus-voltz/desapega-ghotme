<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'pix_price',
        'cover_image_path',
        'active',
    ];

    protected $casts = [
        'pix_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function asaasPayments(): HasMany
    {
        return $this->hasMany(AsaasPayment::class);
    }

    public function getIndividualTotalAttribute(): float
    {
        return (float) $this->products->sum(fn ($product) => (float) $product->pix_price);
    }

    public function getSavingsAttribute(): float
    {
        return max(0, $this->individual_total - (float) $this->pix_price);
    }
}
