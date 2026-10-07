<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'account_type',
        'public_slug',
        'password',
    ];

    protected $attributes = [
        'is_admin' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function sellerSetting(): HasOne
    {
        return $this->hasOne(SellerSetting::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(AsaasPayment::class, 'seller_id');
    }

    public function isSeller(): bool
    {
        return $this->account_type === 'seller';
    }

    public function isAdmin(): bool
    {
        $adminEmail = mb_strtolower(trim((string) config('admin.email')));
        $userEmail = mb_strtolower(trim($this->email));

        return (bool) $this->is_admin
            && $adminEmail !== ''
            && hash_equals($adminEmail, $userEmail);
    }
}
