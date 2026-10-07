<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'seller_id',
        'slug',
        'category',
        'condition',
        'description',
        'pix_price',
        'marketplace_price',
        'marketplace_url',
        'shopee_item_id',
        'shopee_model_id',
        'cover_image_path',
        'cover_image_url',
        'status',
        'is_visible',
        'shopee_order_sn',
        'shopee_order_status',
        'sale_channel',
        'shopee_synced_at',
        'sort_order',
    ];

    protected $casts = [
        'pix_price' => 'decimal:2',
        'marketplace_price' => 'decimal:2',
        'shopee_synced_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function asaasPayments(): HasMany
    {
        return $this->hasMany(AsaasPayment::class);
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->image_urls[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getImagePathsAttribute(): array
    {
        $storedPaths = $this->getRawOriginal('cover_image_path');

        if (! is_string($storedPaths) || $storedPaths === '') {
            return [];
        }

        $decodedPaths = json_decode($storedPaths, true);
        $paths = is_array($decodedPaths) && array_is_list($decodedPaths)
            ? $decodedPaths
            : [$storedPaths];

        return array_values(array_filter(
            $paths,
            fn (mixed $path): bool => is_string($path) && str_starts_with($path, 'products/')
        ));
    }

    /**
     * @return list<string>
     */
    public function getImageUrlsAttribute(): array
    {
        $urls = array_map(
            fn (string $path): string => Storage::disk('public')->url($path),
            $this->image_paths
        );

        if ($urls === [] && $this->cover_image_url) {
            return [$this->cover_image_url];
        }

        return $urls;
    }

    public function getSavingsAttribute(): float
    {
        if (! $this->marketplace_price) {
            return 0;
        }

        return max(0, (float) $this->marketplace_price - (float) $this->pix_price);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'reserved' => 'Reservado',
            'sold' => 'Vendido',
            default => 'Disponível',
        };
    }
}
