<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_an_item_in_their_own_catalog(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [
                'name' => 'Câmera compacta',
                'category' => 'Eletrônicos',
                'pix_price' => 350,
                'marketplace_price' => 380,
            ])
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'seller_id' => $seller->id,
            'name' => 'Câmera compacta',
            'status' => 'available',
        ]);
    }

    public function test_seller_cannot_edit_another_sellers_item(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        $otherProduct = Product::factory()->for(User::factory()->state(['account_type' => 'seller']), 'seller')->create();

        $this->actingAs($seller)
            ->get(route('seller.products.edit', $otherProduct))
            ->assertNotFound();
    }
}
