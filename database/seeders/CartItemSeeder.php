<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CartItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cart = Cart::query()->first() ?? Cart::factory()->create();
        $product = Product::query()->where('status', 'available')->first() ?? Product::factory()->create();

        $cart->items()->firstOrCreate(['product_id' => $product->id]);
    }
}
